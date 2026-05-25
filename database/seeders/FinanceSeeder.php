<?php

namespace Database\Seeders;

use App\Models\Finance\CategoriaFinanza;
use App\Models\Finance\Cuenta;
use App\Models\Finance\Impuesto;
use App\Models\Finance\Moneda;
use App\Models\Finance\TipoCambio;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $mxn = Moneda::create([
            'codigo'    => 'MXN',
            'nombre'    => 'Peso Mexicano',
            'simbolo'   => '$',
            'decimales' => 2,
            'es_base'   => true,
            'activa'    => true,
        ]);

        $usd = Moneda::create([
            'codigo'    => 'USD',
            'nombre'    => 'Dólar Estadounidense',
            'simbolo'   => 'US$',
            'decimales' => 2,
            'es_base'   => false,
            'activa'    => true,
        ]);

        $eur = Moneda::create([
            'codigo'    => 'EUR',
            'nombre'    => 'Euro',
            'simbolo'   => '€',
            'decimales' => 2,
            'es_base'   => false,
            'activa'    => true,
        ]);

        $hoy = now()->toDateString();

        TipoCambio::create([
            'moneda_origen_id'  => $usd->id,
            'moneda_destino_id' => $mxn->id,
            'tasa'              => 17.50,
            'fecha'             => $hoy,
            'fuente'            => 'inicial',
        ]);

        TipoCambio::create([
            'moneda_origen_id'  => $eur->id,
            'moneda_destino_id' => $mxn->id,
            'tasa'              => 19.00,
            'fecha'             => $hoy,
            'fuente'            => 'inicial',
        ]);

        Impuesto::insert([
            ['codigo' => 'IVA16',   'nombre' => 'IVA 16%',           'tipo' => 'iva',       'tasa' => 16.0000, 'aplicacion' => 'traslado',  'incluido_en_precio' => false, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'IVA8',    'nombre' => 'IVA 8% Frontera',   'tipo' => 'iva',       'tasa' => 8.0000,  'aplicacion' => 'traslado',  'incluido_en_precio' => false, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'IVA0',    'nombre' => 'IVA 0%',            'tipo' => 'iva',       'tasa' => 0.0000,  'aplicacion' => 'traslado',  'incluido_en_precio' => false, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'EXENTO',  'nombre' => 'Exento',            'tipo' => 'iva',       'tasa' => 0.0000,  'aplicacion' => 'traslado',  'incluido_en_precio' => false, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'IEPS8',   'nombre' => 'IEPS 8%',           'tipo' => 'ieps',      'tasa' => 8.0000,  'aplicacion' => 'traslado',  'incluido_en_precio' => false, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'RET_IVA', 'nombre' => 'Retención IVA 4%',  'tipo' => 'retencion', 'tasa' => 4.0000,  'aplicacion' => 'retencion', 'incluido_en_precio' => false, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'RET_ISR', 'nombre' => 'Retención ISR 10%', 'tipo' => 'retencion', 'tasa' => 10.0000, 'aplicacion' => 'retencion', 'incluido_en_precio' => false, 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $catsIngreso = [
            ['nombre' => 'Ventas',             'color' => '#10b981', 'icono' => 'sales'],
            ['nombre' => 'Servicios',          'color' => '#14b8a6', 'icono' => 'service'],
            ['nombre' => 'Intereses',          'color' => '#06b6d4', 'icono' => 'interest'],
            ['nombre' => 'Otros ingresos',     'color' => '#22c55e', 'icono' => 'other'],
        ];
        foreach ($catsIngreso as $c) {
            CategoriaFinanza::create($c + ['tipo' => 'ingreso']);
        }

        $catsEgreso = [
            ['nombre' => 'Compras de inventario', 'color' => '#ef4444', 'icono' => 'inventory'],
            ['nombre' => 'Nómina',                'color' => '#f97316', 'icono' => 'payroll'],
            ['nombre' => 'Renta',                 'color' => '#a855f7', 'icono' => 'rent'],
            ['nombre' => 'Servicios (luz, agua)', 'color' => '#eab308', 'icono' => 'utility'],
            ['nombre' => 'Marketing',             'color' => '#ec4899', 'icono' => 'marketing'],
            ['nombre' => 'Impuestos pagados',     'color' => '#64748b', 'icono' => 'tax'],
            ['nombre' => 'Otros egresos',         'color' => '#94a3b8', 'icono' => 'other'],
        ];
        foreach ($catsEgreso as $c) {
            CategoriaFinanza::create($c + ['tipo' => 'egreso']);
        }

        Cuenta::create([
            'nombre'        => 'Caja General',
            'tipo'          => 'caja',
            'moneda_id'     => $mxn->id,
            'saldo_inicial' => 0,
            'saldo_actual'  => 0,
            'activa'        => true,
        ]);
    }
}
