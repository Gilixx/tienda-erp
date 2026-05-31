<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\ActivoFijo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivoFijoController extends Controller
{
    /** GET /api/finance/activos-fijos */
    public function index(Request $request): JsonResponse
    {
        $query = ActivoFijo::with(['proveedor:id,nombre', 'cuentaContable:id,codigo,nombre', 'user:id,name'])
            ->latest();

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->input('categoria'));
        }

        $perPage = min(100, max(10, $request->integer('per_page', 25)));
        return response()->json($query->paginate($perPage));
    }

    /** GET /api/finance/activos-fijos/{id} */
    public function show(string $id): JsonResponse
    {
        $activo = ActivoFijo::with([
            'proveedor:id,nombre', 'cuentaContable',
            'user:id,name', 'depreciaciones' => fn($q) => $q->latest('periodo_anio')->latest('periodo_mes'),
        ])->findOrFail($id);

        $activo->valor_libro_actual        = $activo->valorLibroActual();
        $activo->depreciacion_mensual_calc = $activo->depreciacionMensualLineal();

        return response()->json($activo);
    }

    /** POST /api/finance/activos-fijos */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'               => 'required|string|max:200',
            'categoria'            => 'nullable|string|max:50',
            'cuenta_contable_id'   => 'nullable|exists:cuentas_contables,id',
            'proveedor_id'         => 'nullable|exists:proveedores,id',
            'costo_adquisicion'    => 'required|numeric|min:0',
            'valor_residual'       => 'nullable|numeric|min:0',
            'fecha_adquisicion'    => 'required|date',
            'vida_util_meses'      => 'required|integer|min:1',
            'metodo_depreciacion'  => 'required|in:lineal,acelerado',
            'numero_serie'         => 'nullable|string|max:100',
            'ubicacion'            => 'nullable|string|max:200',
            'notas'                => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = auth()->id();

        $activo = ActivoFijo::create($validated);
        return response()->json($activo, 201);
    }

    /** PUT /api/finance/activos-fijos/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $activo = ActivoFijo::findOrFail($id);

        $validated = $request->validate([
            'nombre'             => 'sometimes|string|max:200',
            'categoria'          => 'nullable|string|max:50',
            'cuenta_contable_id' => 'nullable|exists:cuentas_contables,id',
            'estado'             => 'sometimes|in:activo,vendido,dado_de_baja',
            'numero_serie'       => 'nullable|string|max:100',
            'ubicacion'          => 'nullable|string|max:200',
            'notas'              => 'nullable|string|max:1000',
        ]);

        $activo->update($validated);
        return response()->json($activo);
    }
}
