<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Neutraliza inyección de fórmulas CSV: una celda que empieza con = + - @
     * (o tab/CR) es interpretada como fórmula por Excel/Sheets. Se antepone un
     * apóstrofo para forzar texto. Datos como name/email/phone vienen del registro
     * abierto y son controlados por el usuario.
     */
    private static function csvSafe($value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    /** GET /api/admin/export/users — CSV de usuarios y sus servicios. */
    public function users(): StreamedResponse
    {
        $filename = 'usuarios_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 para que Excel muestre acentos correctamente
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['ID', 'Nombre', 'Email', 'Teléfono', 'Rol', 'Activo', 'Servicios', 'Creado']);

            User::with('services:id,key,name')
                ->orderBy('name')
                ->chunk(200, function ($usuarios) use ($out) {
                    foreach ($usuarios as $u) {
                        fputcsv($out, [
                            $u->id,
                            self::csvSafe($u->name),
                            self::csvSafe($u->email),
                            self::csvSafe($u->phone),
                            $u->role,
                            $u->is_active ? 'Sí' : 'No',
                            self::csvSafe($u->services->pluck('name')->implode(', ')),
                            optional($u->created_at)->format('Y-m-d H:i'),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
