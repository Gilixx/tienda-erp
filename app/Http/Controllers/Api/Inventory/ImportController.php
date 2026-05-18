<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    private const MAX_ROWS = 5000;
    private const ALLOWED_MIMES = [
        'text/csv',
        'text/plain',
        'application/csv',
        'application/vnd.ms-excel',
        'application/octet-stream',
    ];

    /**
     * Descarga plantilla CSV con encabezados.
     */
    public function template(): StreamedResponse
    {
        $headers = ['sku', 'nombre', 'categoria', 'precio', 'costo', 'stock', 'stock_minimo', 'descripcion'];
        $sample  = ['BEB-001', 'Coca Cola 600ml', 'Bebidas', '18.00', '12.00', '50', '5', 'Refresco 600ml'];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            // BOM para que Excel respete UTF-8
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers);
            fputcsv($out, $sample);
            fclose($out);
        }, 'plantilla_catalogo.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Importa productos desde CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:5120', // 5 MB
                'mimes:csv,txt',
            ],
        ]);

        $file = $request->file('file');

        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            return response()->json([
                'message' => 'Tipo de archivo no permitido. Solo CSV.',
            ], 422);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json(['message' => 'No se pudo leer el archivo.'], 422);
        }

        // Detectar y descartar BOM
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }

        $headerRow = fgetcsv($handle);
        if (! $headerRow) {
            fclose($handle);
            return response()->json(['message' => 'CSV vacío o malformado.'], 422);
        }

        $headerRow = array_map(fn ($h) => strtolower(trim((string) $h)), $headerRow);
        $required  = ['sku', 'nombre', 'precio'];
        $missing   = array_diff($required, $headerRow);
        if ($missing) {
            fclose($handle);
            return response()->json([
                'message' => 'Faltan columnas requeridas: ' . implode(', ', $missing),
            ], 422);
        }

        $idx = array_flip($headerRow);

        $created = 0;
        $updated = 0;
        $errors  = [];
        $rowNum  = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;

                if ($rowNum - 1 > self::MAX_ROWS) {
                    fclose($handle);
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Excedido límite de ' . self::MAX_ROWS . ' filas.',
                    ], 422);
                }

                // Saltar filas vacías
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $sku    = trim((string) ($row[$idx['sku']] ?? ''));
                $nombre = trim((string) ($row[$idx['nombre']] ?? ''));
                $precio = $row[$idx['precio']] ?? null;

                if ($sku === '' || $nombre === '' || ! is_numeric($precio)) {
                    $errors[] = "Fila {$rowNum}: SKU, nombre o precio inválido.";
                    continue;
                }

                if (mb_strlen($sku) > 100 || mb_strlen($nombre) > 255) {
                    $errors[] = "Fila {$rowNum}: longitud excedida.";
                    continue;
                }

                $catId = null;
                if (isset($idx['categoria'])) {
                    $catName = trim((string) ($row[$idx['categoria']] ?? ''));
                    if ($catName !== '') {
                        $catId = Category::firstOrCreate(['name' => mb_substr($catName, 0, 255)])->id;
                    }
                }

                $data = [
                    'category_id' => $catId,
                    'name'        => $nombre,
                    'price'       => max(0, (float) $precio),
                    'cost'        => isset($idx['costo'])        ? max(0, (float) ($row[$idx['costo']] ?? 0))         : 0,
                    'stock'       => isset($idx['stock'])        ? max(0, (int)   ($row[$idx['stock']] ?? 0))         : 0,
                    'min_stock'   => isset($idx['stock_minimo']) ? max(0, (int)   ($row[$idx['stock_minimo']] ?? 5))  : 5,
                    'description' => isset($idx['descripcion']) ? mb_substr(trim((string) ($row[$idx['descripcion']] ?? '')), 0, 1000) : null,
                    'is_active'   => true,
                ];

                $existing = Product::where('sku', $sku)->first();
                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    Product::create($data + ['sku' => $sku]);
                    $created++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return response()->json([
                'message' => 'Error al procesar: ' . $e->getMessage(),
            ], 500);
        }

        fclose($handle);

        return response()->json([
            'message' => 'Importación completada.',
            'created' => $created,
            'updated' => $updated,
            'errors'  => array_slice($errors, 0, 50),
            'error_count' => count($errors),
        ]);
    }
}
