<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::with('moneda')->orderBy('nombre');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('nombre', 'like', "%{$s}%")
                  ->orWhere('rfc', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        return response()->json(Proveedor::create($validated)->load('moneda'), 201);
    }

    public function show(string $id)
    {
        $proveedor = Proveedor::with(['moneda', 'cuentasPorPagar' => fn ($q) => $q->whereIn('estado', ['pendiente', 'parcial', 'vencida'])])
            ->findOrFail($id);

        return response()->json($proveedor);
    }

    public function update(Request $request, string $id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $validated = $this->validateData($request);
        $proveedor->update($validated);
        return response()->json($proveedor->load('moneda'));
    }

    public function destroy(string $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        if ($proveedor->compras()->exists()) {
            return response()->json(['error' => 'No se puede eliminar: tiene compras registradas.'], 422);
        }

        $proveedor->delete();
        return response()->json(['message' => 'Proveedor eliminado.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'nombre'       => 'required|string|max:150',
            'rfc'          => 'nullable|string|max:20',
            'contacto'     => 'nullable|string|max:100',
            'telefono'     => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:150',
            'direccion'    => 'nullable|string|max:500',
            'moneda_id'    => 'nullable|exists:monedas,id',
            'dias_credito' => 'nullable|integer|min:0|max:365',
            'notas'        => 'nullable|string|max:1000',
            'activo'       => 'boolean',
        ]);
    }
}
