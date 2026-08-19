<?php

namespace App\Http\Controllers\Api\Inventory\Concerns;

use App\Models\Inventory\Almacen;
use App\Models\Product;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait AuthorizesAlmacen
{
    /**
     * Aborta con 403 si el producto no es accesible para el usuario actual.
     * Evita que, teniendo acceso a un almacén, se creen stock/movimientos/ventas
     * sobre productos de otro tenant (que quedarían "accesibles" al tener stock).
     */
    protected function assertProductoAccesible($productId): void
    {
        $accesible = Product::accesiblesPara(request()->user())
            ->whereKey($productId)
            ->exists();

        if (! $accesible) {
            throw new HttpException(403, 'No tienes acceso a este producto.');
        }
    }

    /**
     * Carga el almacén y aborta con 403 si el usuario actual no tiene acceso.
     * Devuelve el modelo para reutilizarlo en el controlador.
     */
    protected function authorizeAlmacen($almacenId): Almacen
    {
        $almacen = Almacen::findOrFail($almacenId);

        if (! $almacen->accesiblePara(request()->user())) {
            throw new HttpException(403, 'No tienes acceso a este almacén.');
        }

        return $almacen;
    }

    /** IDs de los almacenes accesibles para el usuario actual, para filtrar listados. */
    protected function accesibleAlmacenIds()
    {
        return Almacen::accesiblesPara(request()->user())->pluck('id');
    }
}
