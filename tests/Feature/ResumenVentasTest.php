<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ResumenVentasTest extends TestCase
{
    public function test_resumen_sin_filtros()
    {
        // Limpiamos base e insertamos datos controlados
        \App\Models\Venta::truncate();
        
        \App\Models\Venta::create([
            'id_venta' => 'V1',
            'fecha' => '2026-01-01',
            'vendedor' => 'V1',
            'region' => 'Norte',
            'producto' => 'P1',
            'monto' => 100.50,
            'estatus' => 'cerrada'
        ]);
        
        \App\Models\Venta::create([
            'id_venta' => 'V2',
            'fecha' => '2026-02-01',
            'vendedor' => 'V2',
            'region' => 'Sur',
            'producto' => 'P1',
            'monto' => 200.00,
            'estatus' => 'cerrada'
        ]);

        \App\Models\Venta::create([
            'id_venta' => 'V3',
            'fecha' => '2026-01-15',
            'vendedor' => 'V3',
            'region' => 'Norte',
            'producto' => 'P1',
            'monto' => 50.00,
            'estatus' => 'abierta' // No debe sumarse
        ]);

        $response = $this->getJson('/api/ventas/resumen');

        $response->assertStatus(200)
                 ->assertJson([
                     'total_ventas' => 300.50,
                     'numero_ventas' => 2,
                     'por_region' => [
                         ['region' => 'Sur', 'total' => 200.00],
                         ['region' => 'Norte', 'total' => 100.50],
                     ]
                 ]);
    }

    public function test_resumen_con_filtros()
    {
        \App\Models\Venta::truncate();
        
        \App\Models\Venta::create(['id_venta' => 'V1', 'fecha' => '2026-01-01', 'vendedor' => 'V', 'region' => 'Norte', 'producto' => 'P', 'monto' => 100, 'estatus' => 'cerrada']);
        \App\Models\Venta::create(['id_venta' => 'V2', 'fecha' => '2026-02-01', 'vendedor' => 'V', 'region' => 'Norte', 'producto' => 'P', 'monto' => 200, 'estatus' => 'cerrada']);
        \App\Models\Venta::create(['id_venta' => 'V3', 'fecha' => '2026-03-01', 'vendedor' => 'V', 'region' => 'Norte', 'producto' => 'P', 'monto' => 300, 'estatus' => 'cerrada']);

        // Solo enero
        $response = $this->getJson('/api/ventas/resumen?fecha_inicio=2026-01-01&fecha_fin=2026-01-31');
        $response->assertStatus(200)
                 ->assertJson(['total_ventas' => 100, 'numero_ventas' => 1]);

        // Hasta febrero
        $response2 = $this->getJson('/api/ventas/resumen?fecha_fin=2026-02-28');
        $response2->assertStatus(200)
                  ->assertJson(['total_ventas' => 300, 'numero_ventas' => 2]);
    }

    public function test_periodo_sin_ventas_retorna_ceros()
    {
        \App\Models\Venta::truncate();
        $response = $this->getJson('/api/ventas/resumen');
        $response->assertStatus(200)
                 ->assertExactJson([
                     'total_ventas' => 0,
                     'numero_ventas' => 0,
                     'por_region' => []
                 ]);
    }

    public function test_parametros_invalidos_retorna_400()
    {
        $response = $this->getJson('/api/ventas/resumen?fecha_inicio=hola');
        $response->assertStatus(400)
                 ->assertJsonPath('error', 'Parámetros inválidos.');

        $response2 = $this->getJson('/api/ventas/resumen?fecha_inicio=2026-12-31&fecha_fin=2026-01-01');
        $response2->assertStatus(400)
                  ->assertJsonPath('error', 'La fecha de inicio no puede ser posterior a la fecha de fin.');
    }
}
