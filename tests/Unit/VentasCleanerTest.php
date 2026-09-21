<?php

namespace Tests\Unit;

use App\Services\VentasCleaner;
use PHPUnit\Framework\TestCase;

class VentasCleanerTest extends TestCase
{
    public function test_valid_row_is_cleaned_correctly(): void
    {
        $cleaner = new VentasCleaner;
        $row = [
            'id_venta' => 'V001',
            'fecha' => '04-03-2026',
            'vendedor' => '  Juan  Pérez  ',
            'region' => ' bAjÍo ',
            'producto' => 'Licencia',
            'monto' => '$ 12,500.50',
            'estatus' => 'Cerrado ',
        ];

        $cleaned = $cleaner->clean($row);

        $this->assertIsArray($cleaned);
        $this->assertEquals('V001', $cleaned['id_venta']);
        $this->assertEquals('2026-03-04', $cleaned['fecha']);
        $this->assertEquals('Juan Pérez', $cleaned['vendedor']);
        $this->assertEquals('Bajío', $cleaned['region']);
        $this->assertEquals('12500.50', $cleaned['monto']);
        $this->assertEquals('cerrada', $cleaned['estatus']);
    }

    public function test_invalid_date_is_discarded(): void
    {
        $cleaner = new VentasCleaner;
        $row = ['id_venta' => '1', 'fecha' => '32-01-2026', 'vendedor' => 'A', 'region' => 'B', 'producto' => 'C', 'monto' => '10', 'estatus' => 'cerrada'];
        $this->assertNull($cleaner->clean($row));

        $row['fecha'] = '';
        $this->assertNull($cleaner->clean($row));
    }

    public function test_amount_formats(): void
    {
        $cleaner = new VentasCleaner;
        $row = ['id_venta' => '1', 'fecha' => '2026-01-01', 'vendedor' => 'A', 'region' => 'B', 'producto' => 'C', 'monto' => '1000,50', 'estatus' => 'cerrada'];
        $cleaned = $cleaner->clean($row);
        $this->assertEquals(1000.50, $cleaned['monto']);

        $row['monto'] = '-100';
        $this->assertNull($cleaner->clean($row)); // negativo

        $row['monto'] = 'abc';
        $this->assertNull($cleaner->clean($row)); // no numerico

        $row['monto'] = '0';
        $this->assertNotNull($cleaner->clean($row)); // cero es valido
    }

    public function test_empty_region_is_discarded(): void
    {
        $cleaner = new VentasCleaner;
        $row = ['id_venta' => '1', 'fecha' => '2026-01-01', 'vendedor' => 'A', 'region' => '   ', 'producto' => 'C', 'monto' => '10', 'estatus' => 'cerrada'];
        $this->assertNull($cleaner->clean($row));
    }

    public function test_empty_seller_is_conserved(): void
    {
        $cleaner = new VentasCleaner;
        $row = ['id_venta' => '1', 'fecha' => '2026-01-01', 'vendedor' => '', 'region' => 'Sur', 'producto' => 'C', 'monto' => '10', 'estatus' => 'abierta'];
        $cleaned = $cleaner->clean($row);
        $this->assertNotNull($cleaned);
        $this->assertEquals('', $cleaned['vendedor']);
    }
}
