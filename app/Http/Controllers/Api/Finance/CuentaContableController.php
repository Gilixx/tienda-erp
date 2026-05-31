<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\AsientoContable;
use App\Models\Finance\AsientoLinea;
use App\Models\Finance\CuentaContable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CuentaContableController extends Controller
{
    // ─────────────────────────────────────────────
    // Catálogo de Cuentas
    // ─────────────────────────────────────────────

    /** GET /api/finance/cuentas-contables */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'cuentas_contables_' . ($request->input('tipo', 'todas'));

        $data = Cache::remember($cacheKey, 3600, function () use ($request) {
            $query = CuentaContable::with('hijos')
                ->where('activa', true)
                ->whereNull('parent_id') // solo raíz; hijos se cargan con eager loading
                ->orderBy('codigo');

            if ($request->filled('tipo')) {
                $query->where('tipo', $request->input('tipo'));
            }

            return $query->get();
        });

        return response()->json($data);
    }

    /** GET /api/finance/cuentas-contables/flat — lista plana para selects */
    public function flat(Request $request): JsonResponse
    {
        $query = CuentaContable::where('activa', true)
            ->when($request->filled('tipo'), fn($q) => $q->where('tipo', $request->input('tipo')))
            ->when($request->filled('auxiliar'), fn($q) => $q->where('es_auxiliar', true))
            ->orderBy('codigo')
            ->get(['id', 'codigo', 'nombre', 'tipo', 'nivel']);

        return response()->json($query);
    }

    /** POST /api/finance/cuentas-contables */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'codigo'    => 'required|string|max:20|unique:cuentas_contables,codigo',
            'nombre'    => 'required|string|max:150',
            'tipo'      => 'required|in:activo,pasivo,capital,ingreso,egreso,costo',
            'subtipo'   => 'nullable|string|max:50',
            'nivel'     => 'required|integer|min:1|max:5',
            'parent_id' => 'nullable|exists:cuentas_contables,id',
            'es_auxiliar' => 'boolean',
        ]);

        $cuenta = CuentaContable::create($validated);
        Cache::forget('cuentas_contables_todas');
        Cache::forget('cuentas_contables_' . $cuenta->tipo);

        return response()->json($cuenta, 201);
    }

    /** PUT /api/finance/cuentas-contables/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $cuenta = CuentaContable::findOrFail($id);

        $validated = $request->validate([
            'codigo'    => 'sometimes|string|max:20|unique:cuentas_contables,codigo,' . $cuenta->id,
            'nombre'    => 'sometimes|string|max:150',
            'subtipo'   => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:cuentas_contables,id',
            'activa'    => 'boolean',
        ]);

        $cuenta->update($validated);
        Cache::forget('cuentas_contables_todas');

        return response()->json($cuenta);
    }

    // ─────────────────────────────────────────────
    // Asientos Contables
    // ─────────────────────────────────────────────

    /** GET /api/finance/asientos */
    public function indexAsientos(Request $request): JsonResponse
    {
        $query = AsientoContable::with(['user:id,name'])
            ->withSum('lineas as total_cargo', 'cargo')
            ->latest('fecha')->latest('id');

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }
        if ($request->filled('desde')) {
            $query->where('fecha', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta')) {
            $query->where('fecha', '<=', $request->input('hasta'));
        }

        $perPage = min(100, max(10, $request->integer('per_page', 25)));

        return response()->json($query->paginate($perPage));
    }

    /** GET /api/finance/asientos/{id} */
    public function showAsiento(string $id): JsonResponse
    {
        $asiento = AsientoContable::with([
            'user:id,name',
            'lineas.cuentaContable:id,codigo,nombre,tipo',
        ])->findOrFail($id);

        return response()->json($asiento);
    }

    /** POST /api/finance/asientos */
    public function storeAsiento(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'descripcion'               => 'required|string|max:300',
            'fecha'                     => 'required|date',
            'referencia'                => 'nullable|string|max:100',
            'lineas'                    => 'required|array|min:2',
            'lineas.*.cuenta_contable_id' => 'required|exists:cuentas_contables,id',
            'lineas.*.descripcion'      => 'nullable|string|max:200',
            'lineas.*.cargo'            => 'required|numeric|min:0',
            'lineas.*.abono'            => 'required|numeric|min:0',
        ]);

        // Verificar que cada línea tiene solo cargo o solo abono
        foreach ($validated['lineas'] as $linea) {
            if ($linea['cargo'] > 0 && $linea['abono'] > 0) {
                return response()->json(['error' => 'Una línea no puede tener cargo y abono al mismo tiempo.'], 422);
            }
        }

        // Verificar balanceo
        $totalCargo = array_sum(array_column($validated['lineas'], 'cargo'));
        $totalAbono = array_sum(array_column($validated['lineas'], 'abono'));

        if (abs($totalCargo - $totalAbono) > 0.001) {
            return response()->json([
                'error'       => 'El asiento no está balanceado.',
                'total_cargo' => round($totalCargo, 4),
                'total_abono' => round($totalAbono, 4),
                'diferencia'  => round(abs($totalCargo - $totalAbono), 4),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($validated) {
                $asiento = AsientoContable::create([
                    'tipo'        => 'manual',
                    'descripcion' => $validated['descripcion'],
                    'fecha'       => $validated['fecha'],
                    'referencia'  => $validated['referencia'] ?? null,
                    'user_id'     => auth()->id(),
                    'estado'      => 'publicado', // manual → publicado directamente
                ]);

                foreach ($validated['lineas'] as $linea) {
                    $asiento->lineas()->create([
                        'cuenta_contable_id' => $linea['cuenta_contable_id'],
                        'descripcion'        => $linea['descripcion'] ?? null,
                        'cargo'              => $linea['cargo'],
                        'abono'              => $linea['abono'],
                    ]);
                }

                return response()->json(
                    $asiento->load(['lineas.cuentaContable', 'user:id,name']),
                    201
                );
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Error al crear el asiento: ' . $e->getMessage()], 500);
        }
    }

    /** DELETE /api/finance/asientos/{id} — solo borradores */
    public function destroyAsiento(string $id): JsonResponse
    {
        $asiento = AsientoContable::findOrFail($id);

        if ($asiento->estado === 'publicado') {
            return response()->json(['error' => 'No se puede eliminar un asiento publicado. Cancélalo primero.'], 422);
        }

        $asiento->delete();

        return response()->json(['message' => 'Asiento eliminado.']);
    }

    /** POST /api/finance/asientos/{id}/cancelar */
    public function cancelarAsiento(string $id): JsonResponse
    {
        $asiento = AsientoContable::findOrFail($id);

        if ($asiento->estado === 'cancelado') {
            return response()->json(['error' => 'El asiento ya está cancelado.'], 422);
        }

        $asiento->update(['estado' => 'cancelado']);

        return response()->json($asiento);
    }
}
