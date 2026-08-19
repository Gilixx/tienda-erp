<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Api\Inventory\Concerns\AuthorizesAlmacen;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Inventory\ProductStock;
use App\Models\Product;
use App\Services\Inventory\VerificarStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    use AuthorizesAlmacen;

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
        $sample = ['BEB-001', 'Coca Cola 600ml', 'Bebidas', '18.00', '12.00', '50', '5', 'Refresco 600ml'];

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
        $validated = $request->validate([
            'almacen_id' => 'required|integer|exists:almacenes,id',
            'file' => [
                'required',
                'file',
                'max:5120', // 5 MB
                'mimes:csv,txt',
            ],
        ]);

        // Los productos se importan hacia el almacén seleccionado.
        $this->authorizeAlmacen($validated['almacen_id']);
        $almacenId = (int) $validated['almacen_id'];

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
        $required = ['sku', 'nombre', 'precio'];
        $missing = array_diff($required, $headerRow);
        if ($missing) {
            fclose($handle);

            return response()->json([
                'message' => 'Faltan columnas requeridas: '.implode(', ', $missing),
            ], 422);
        }

        $idx = array_flip($headerRow);
        $userId = $request->user()->id;

        // ── 1) Parsear y validar TODAS las filas en memoria (sin tocar la BD) ──
        $errors = [];
        $rowNum = 1;
        $rows = []; // sku => datos (última fila con el mismo SKU gana)

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if ($rowNum - 1 > self::MAX_ROWS) {
                fclose($handle);

                return response()->json([
                    'message' => 'Excedido límite de '.self::MAX_ROWS.' filas.',
                ], 422);
            }

            // Saltar filas vacías
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $sku = trim((string) ($row[$idx['sku']] ?? ''));
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

            $catName = '';
            if (isset($idx['categoria'])) {
                $catName = mb_substr(trim((string) ($row[$idx['categoria']] ?? '')), 0, 255);
            }

            $rows[$sku] = [
                'sku' => $sku,
                'name' => $nombre,
                'price' => max(0, (float) $precio),
                'cost' => isset($idx['costo']) ? max(0, (float) ($row[$idx['costo']] ?? 0)) : 0,
                'min_stock' => isset($idx['stock_minimo']) ? max(0, (int) ($row[$idx['stock_minimo']] ?? 5)) : 5,
                'description' => isset($idx['descripcion']) ? mb_substr(trim((string) ($row[$idx['descripcion']] ?? '')), 0, 1000) : null,
                'stock' => isset($idx['stock']) ? max(0, (int) ($row[$idx['stock']] ?? 0)) : 0,
                'cat' => $catName !== '' ? $catName : null,
            ];
        }
        fclose($handle);

        $rows = array_values($rows);

        if (empty($rows)) {
            return response()->json([
                'message' => 'Importación completada.',
                'created' => 0, 'updated' => 0,
                'errors' => array_slice($errors, 0, 50), 'error_count' => count($errors),
            ]);
        }

        $now = now();
        $created = 0;
        $updated = 0;
        $touched = [];

        DB::beginTransaction();
        try {
            // ── 2) Categorías del almacén: buscar existentes e insertar faltantes en bloque ──
            $catNames = collect($rows)->pluck('cat')->filter()->unique()->values();
            $catMap = [];
            if ($catNames->isNotEmpty()) {
                $catMap = Category::where('almacen_id', $almacenId)
                    ->whereIn('name', $catNames)->pluck('id', 'name')->all();

                $faltantes = $catNames->reject(fn ($n) => isset($catMap[$n]))->values();
                if ($faltantes->isNotEmpty()) {
                    Category::insert($faltantes->map(fn ($n) => [
                        'name' => $n, 'almacen_id' => $almacenId, 'created_by' => $userId,
                        'created_at' => $now, 'updated_at' => $now,
                    ])->all());

                    $catMap = Category::where('almacen_id', $almacenId)
                        ->whereIn('name', $catNames)->pluck('id', 'name')->all();
                }
            }

            // ── 3) Productos: upsert en bloque por (created_by, sku) ──
            $skus = array_column($rows, 'sku');
            $existentes = Product::where('created_by', $userId)->whereIn('sku', $skus)->pluck('id', 'sku');

            $prodUpsert = [];
            foreach ($rows as $r) {
                $existentes->has($r['sku']) ? $updated++ : $created++;
                $prodUpsert[] = [
                    'sku' => $r['sku'], 'created_by' => $userId,
                    'name' => $r['name'], 'price' => $r['price'], 'cost' => $r['cost'],
                    'min_stock' => $r['min_stock'], 'description' => $r['description'], 'is_active' => true,
                    'stock' => 0, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            foreach (array_chunk($prodUpsert, 500) as $chunk) {
                // 'stock' se excluye del update: se recalcula abajo desde product_stock.
                Product::upsert($chunk, ['created_by', 'sku'],
                    ['name', 'price', 'cost', 'min_stock', 'description', 'is_active', 'updated_at']);
            }

            // IDs finales por SKU (existentes + recién creados).
            $prodMap = Product::where('created_by', $userId)->whereIn('sku', $skus)->pluck('id', 'sku');

            // ── 4) Stock por almacén: upsert en bloque (separando con/sin categoría) ──
            $conCat = [];
            $sinCat = [];
            foreach ($rows as $r) {
                $pid = $prodMap[$r['sku']];
                $touched[] = $pid;
                if ($r['cat'] !== null && isset($catMap[$r['cat']])) {
                    $conCat[] = ['product_id' => $pid, 'almacen_id' => $almacenId, 'cantidad' => $r['stock'], 'category_id' => $catMap[$r['cat']], 'updated_at' => $now];
                } else {
                    $sinCat[] = ['product_id' => $pid, 'almacen_id' => $almacenId, 'cantidad' => $r['stock'], 'updated_at' => $now];
                }
            }
            foreach (array_chunk($conCat, 500) as $chunk) {
                ProductStock::upsert($chunk, ['product_id', 'almacen_id'], ['cantidad', 'category_id', 'updated_at']);
            }
            // Sin categoría: no se toca category_id de filas existentes.
            foreach (array_chunk($sinCat, 500) as $chunk) {
                ProductStock::upsert($chunk, ['product_id', 'almacen_id'], ['cantidad', 'updated_at']);
            }

            // ── 5) Sincronizar products.stock (total global): una lectura agregada +
            //     un UPDATE ... CASE por lote (evita un UPDATE por producto). ──
            $sumas = ProductStock::whereIn('product_id', $touched)
                ->groupBy('product_id')
                ->selectRaw('product_id, SUM(cantidad) as total')
                ->pluck('total', 'product_id')
                ->all();

            foreach (array_chunk($sumas, 200, true) as $lote) {
                $cases = '';
                $bindings = [];
                foreach ($lote as $pid => $total) {
                    $cases .= ' WHEN ? THEN ?';
                    $bindings[] = $pid;
                    $bindings[] = (int) $total;
                }
                $ids = array_keys($lote);
                $in = implode(',', array_fill(0, count($ids), '?'));

                DB::update(
                    "UPDATE products SET stock = CASE id{$cases} END WHERE id IN ({$in})",
                    array_merge($bindings, $ids)
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al procesar: '.$e->getMessage(),
            ], 500);
        }

        // Refresco de alertas acotado a los productos tocados (no rescan del almacén).
        app(VerificarStockService::class)->verificar($almacenId, $touched);

        return response()->json([
            'message' => 'Importación completada.',
            'created' => $created,
            'updated' => $updated,
            'errors' => array_slice($errors, 0, 50),
            'error_count' => count($errors),
        ]);
    }
}
