<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo de cuentas contables basado en NIF México (IMCP).
 * Estructura de 5 niveles para PYMES.
 */
class CuentasContablesSeeder extends Seeder
{
    public function run(): void
    {
        $cuentas = $this->getCatalogo();
        $idMap   = []; // codigo => id

        foreach ($cuentas as $c) {
            $parentId = null;
            if ($c['parent_codigo'] ?? null) {
                $parentId = $idMap[$c['parent_codigo']] ?? null;
            }

            $id = DB::table('cuentas_contables')->insertGetId([
                'codigo'      => $c['codigo'],
                'nombre'      => $c['nombre'],
                'tipo'        => $c['tipo'],
                'subtipo'     => $c['subtipo'] ?? null,
                'nivel'       => $c['nivel'],
                'parent_id'   => $parentId,
                'es_auxiliar' => $c['es_auxiliar'] ?? true,
                'activa'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $idMap[$c['codigo']] = $id;
        }
    }

    private function getCatalogo(): array
    {
        return [
            // ══════ ACTIVO ═��════
            ['codigo'=>'1',   'nombre'=>'ACTIVO',              'tipo'=>'activo',  'nivel'=>1, 'es_auxiliar'=>false],
            ['codigo'=>'1.1', 'nombre'=>'Activo Circulante',   'tipo'=>'activo',  'nivel'=>2, 'es_auxiliar'=>false, 'parent_codigo'=>'1'],
            ['codigo'=>'1.1.01','nombre'=>'Caja',              'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'1.1'],
            ['codigo'=>'1.1.02','nombre'=>'Bancos',            'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'1.1'],
            ['codigo'=>'1.1.03','nombre'=>'Clientes',          'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'1.1'],
            ['codigo'=>'1.1.04','nombre'=>'Documentos por Cobrar','tipo'=>'activo','nivel'=>3,'subtipo'=>'circulante','parent_codigo'=>'1.1'],
            ['codigo'=>'1.1.05','nombre'=>'IVA Acreditable',   'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'1.1'],
            ['codigo'=>'1.1.06','nombre'=>'IVA por Acreditar', 'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'1.1'],
            ['codigo'=>'1.1.07','nombre'=>'Inventarios',       'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'1.1'],
            ['codigo'=>'1.1.08','nombre'=>'Almacén de Mercancías','tipo'=>'activo','nivel'=>3,'subtipo'=>'circulante','parent_codigo'=>'1.1'],
            ['codigo'=>'1.1.09','nombre'=>'Anticipo a Proveedores','tipo'=>'activo','nivel'=>3,'subtipo'=>'circulante','parent_codigo'=>'1.1'],
            ['codigo'=>'1.1.10','nombre'=>'Deudores Diversos', 'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'1.1'],

            ['codigo'=>'1.2', 'nombre'=>'Activo No Circulante','tipo'=>'activo',  'nivel'=>2, 'es_auxiliar'=>false, 'parent_codigo'=>'1'],
            ['codigo'=>'1.2.01','nombre'=>'Terrenos',          'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'fijo', 'parent_codigo'=>'1.2'],
            ['codigo'=>'1.2.02','nombre'=>'Edificios',         'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'fijo', 'parent_codigo'=>'1.2'],
            ['codigo'=>'1.2.03','nombre'=>'Dep. Acum. Edificios','tipo'=>'activo','nivel'=>3,'subtipo'=>'fijo', 'parent_codigo'=>'1.2'],
            ['codigo'=>'1.2.04','nombre'=>'Mobiliario y Equipo','tipo'=>'activo', 'nivel'=>3, 'subtipo'=>'fijo', 'parent_codigo'=>'1.2'],
            ['codigo'=>'1.2.05','nombre'=>'Dep. Acum. Mob. y Equipo','tipo'=>'activo','nivel'=>3,'subtipo'=>'fijo','parent_codigo'=>'1.2'],
            ['codigo'=>'1.2.06','nombre'=>'Equipo de Cómputo', 'tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'fijo', 'parent_codigo'=>'1.2'],
            ['codigo'=>'1.2.07','nombre'=>'Dep. Acum. Cómputo','tipo'=>'activo',  'nivel'=>3, 'subtipo'=>'fijo', 'parent_codigo'=>'1.2'],
            ['codigo'=>'1.2.08','nombre'=>'Equipo de Transporte','tipo'=>'activo','nivel'=>3,'subtipo'=>'fijo', 'parent_codigo'=>'1.2'],
            ['codigo'=>'1.2.09','nombre'=>'Dep. Acum. Transporte','tipo'=>'activo','nivel'=>3,'subtipo'=>'fijo','parent_codigo'=>'1.2'],

            // ══════ PASIVO ══════
            ['codigo'=>'2',   'nombre'=>'PASIVO',              'tipo'=>'pasivo',  'nivel'=>1, 'es_auxiliar'=>false],
            ['codigo'=>'2.1', 'nombre'=>'Pasivo Circulante',   'tipo'=>'pasivo',  'nivel'=>2, 'es_auxiliar'=>false, 'parent_codigo'=>'2'],
            ['codigo'=>'2.1.01','nombre'=>'Proveedores',       'tipo'=>'pasivo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'2.1'],
            ['codigo'=>'2.1.02','nombre'=>'Documentos por Pagar','tipo'=>'pasivo','nivel'=>3,'subtipo'=>'circulante','parent_codigo'=>'2.1'],
            ['codigo'=>'2.1.03','nombre'=>'Acreedores Diversos','tipo'=>'pasivo', 'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'2.1'],
            ['codigo'=>'2.1.04','nombre'=>'IVA Trasladado',    'tipo'=>'pasivo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'2.1'],
            ['codigo'=>'2.1.05','nombre'=>'IVA por Trasladar', 'tipo'=>'pasivo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'2.1'],
            ['codigo'=>'2.1.06','nombre'=>'ISR por Pagar',     'tipo'=>'pasivo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'2.1'],
            ['codigo'=>'2.1.07','nombre'=>'Anticipo de Clientes','tipo'=>'pasivo','nivel'=>3,'subtipo'=>'circulante','parent_codigo'=>'2.1'],
            ['codigo'=>'2.1.08','nombre'=>'Sueldos por Pagar', 'tipo'=>'pasivo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'2.1'],
            ['codigo'=>'2.1.09','nombre'=>'IMSS por Pagar',    'tipo'=>'pasivo',  'nivel'=>3, 'subtipo'=>'circulante', 'parent_codigo'=>'2.1'],
            ['codigo'=>'2.1.10','nombre'=>'Retenciones por Pagar','tipo'=>'pasivo','nivel'=>3,'subtipo'=>'circulante','parent_codigo'=>'2.1'],

            ['codigo'=>'2.2', 'nombre'=>'Pasivo No Circulante','tipo'=>'pasivo',  'nivel'=>2, 'es_auxiliar'=>false, 'parent_codigo'=>'2'],
            ['codigo'=>'2.2.01','nombre'=>'Préstamos Bancarios L/P','tipo'=>'pasivo','nivel'=>3,'subtipo'=>'largo_plazo','parent_codigo'=>'2.2'],
            ['codigo'=>'2.2.02','nombre'=>'Hipotecas por Pagar','tipo'=>'pasivo', 'nivel'=>3, 'subtipo'=>'largo_plazo', 'parent_codigo'=>'2.2'],

            // ══════ CAPITAL ══════
            ['codigo'=>'3',   'nombre'=>'CAPITAL CONTABLE',   'tipo'=>'capital', 'nivel'=>1, 'es_auxiliar'=>false],
            ['codigo'=>'3.1.01','nombre'=>'Capital Social',   'tipo'=>'capital', 'nivel'=>3, 'subtipo'=>'capital', 'parent_codigo'=>'3'],
            ['codigo'=>'3.1.02','nombre'=>'Utilidad del Ejercicio','tipo'=>'capital','nivel'=>3,'subtipo'=>'capital','parent_codigo'=>'3'],
            ['codigo'=>'3.1.03','nombre'=>'Pérdida del Ejercicio','tipo'=>'capital','nivel'=>3,'subtipo'=>'capital','parent_codigo'=>'3'],
            ['codigo'=>'3.1.04','nombre'=>'Reserva Legal',    'tipo'=>'capital', 'nivel'=>3, 'subtipo'=>'capital', 'parent_codigo'=>'3'],
            ['codigo'=>'3.1.05','nombre'=>'Utilidades Retenidas','tipo'=>'capital','nivel'=>3,'subtipo'=>'capital','parent_codigo'=>'3'],

            // ══════ INGRESOS ══════
            ['codigo'=>'4',   'nombre'=>'INGRESOS',           'tipo'=>'ingreso', 'nivel'=>1, 'es_auxiliar'=>false],
            ['codigo'=>'4.1.01','nombre'=>'Ventas',           'tipo'=>'ingreso', 'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'4'],
            ['codigo'=>'4.1.02','nombre'=>'Devoluciones sobre Ventas','tipo'=>'ingreso','nivel'=>3,'subtipo'=>'operacion','parent_codigo'=>'4'],
            ['codigo'=>'4.1.03','nombre'=>'Descuentos sobre Ventas','tipo'=>'ingreso','nivel'=>3,'subtipo'=>'operacion','parent_codigo'=>'4'],
            ['codigo'=>'4.2.01','nombre'=>'Ingresos Financieros','tipo'=>'ingreso','nivel'=>3,'subtipo'=>'no_operacion','parent_codigo'=>'4'],
            ['codigo'=>'4.2.02','nombre'=>'Utilidad en Cambios','tipo'=>'ingreso','nivel'=>3,'subtipo'=>'no_operacion','parent_codigo'=>'4'],
            ['codigo'=>'4.2.03','nombre'=>'Otros Ingresos',   'tipo'=>'ingreso', 'nivel'=>3, 'subtipo'=>'no_operacion', 'parent_codigo'=>'4'],

            // ══════ COSTOS ══════
            ['codigo'=>'5',   'nombre'=>'COSTOS',             'tipo'=>'costo',   'nivel'=>1, 'es_auxiliar'=>false],
            ['codigo'=>'5.1.01','nombre'=>'Costo de Ventas',  'tipo'=>'costo',   'nivel'=>3, 'subtipo'=>'mercancia', 'parent_codigo'=>'5'],
            ['codigo'=>'5.1.02','nombre'=>'Costo de Producción','tipo'=>'costo', 'nivel'=>3, 'subtipo'=>'produccion', 'parent_codigo'=>'5'],

            // ══════ EGRESOS / GASTOS ══════
            ['codigo'=>'6',   'nombre'=>'GASTOS',             'tipo'=>'egreso',  'nivel'=>1, 'es_auxiliar'=>false],
            ['codigo'=>'6.1', 'nombre'=>'Gastos de Operación','tipo'=>'egreso',  'nivel'=>2, 'es_auxiliar'=>false, 'parent_codigo'=>'6'],
            ['codigo'=>'6.1.01','nombre'=>'Sueldos y Salarios','tipo'=>'egreso', 'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'6.1'],
            ['codigo'=>'6.1.02','nombre'=>'IMSS / INFONAVIT', 'tipo'=>'egreso',  'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'6.1'],
            ['codigo'=>'6.1.03','nombre'=>'Renta',            'tipo'=>'egreso',  'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'6.1'],
            ['codigo'=>'6.1.04','nombre'=>'Servicios (Luz, Agua, Tel.)','tipo'=>'egreso','nivel'=>3,'subtipo'=>'operacion','parent_codigo'=>'6.1'],
            ['codigo'=>'6.1.05','nombre'=>'Papelería y Útiles','tipo'=>'egreso', 'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'6.1'],
            ['codigo'=>'6.1.06','nombre'=>'Mantenimiento',    'tipo'=>'egreso',  'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'6.1'],
            ['codigo'=>'6.1.07','nombre'=>'Viáticos',         'tipo'=>'egreso',  'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'6.1'],
            ['codigo'=>'6.1.08','nombre'=>'Publicidad',       'tipo'=>'egreso',  'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'6.1'],
            ['codigo'=>'6.1.09','nombre'=>'Depreciaciones',   'tipo'=>'egreso',  'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'6.1'],
            ['codigo'=>'6.1.10','nombre'=>'Seguros',          'tipo'=>'egreso',  'nivel'=>3, 'subtipo'=>'operacion', 'parent_codigo'=>'6.1'],
            ['codigo'=>'6.2', 'nombre'=>'Gastos Financieros', 'tipo'=>'egreso',  'nivel'=>2, 'es_auxiliar'=>false, 'parent_codigo'=>'6'],
            ['codigo'=>'6.2.01','nombre'=>'Intereses Pagados','tipo'=>'egreso',  'nivel'=>3, 'subtipo'=>'financiero', 'parent_codigo'=>'6.2'],
            ['codigo'=>'6.2.02','nombre'=>'Pérdida en Cambios','tipo'=>'egreso', 'nivel'=>3, 'subtipo'=>'financiero', 'parent_codigo'=>'6.2'],
            ['codigo'=>'6.2.03','nombre'=>'Comisiones Bancarias','tipo'=>'egreso','nivel'=>3,'subtipo'=>'financiero','parent_codigo'=>'6.2'],
        ];
    }
}
