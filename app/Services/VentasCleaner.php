<?php

namespace App\Services;

class VentasCleaner
{
    /**
     * Limpia un registro. Devuelve el registro normalizado, o false/null si es inválido y se debe descartar.
     */
    public function clean(array $row): ?array
    {
        // 1. Validar región vacía
        $region = trim($row['region'] ?? '');
        if ($region === '') {
            return null; // descartar
        }
        // Normalizar región
        $region = mb_convert_case(preg_replace('/\s+/', ' ', $region), MB_CASE_TITLE, 'UTF-8');

        // 2. Estatus
        $estatus = trim($row['estatus'] ?? '');
        $estatusLower = mb_strtolower($estatus, 'UTF-8');
        if (in_array($estatusLower, ['cerrado', 'cerrada'])) {
            $estatus = 'cerrada';
        } elseif (in_array($estatusLower, ['abierto', 'abierta'])) {
            $estatus = 'abierta';
        } elseif (in_array($estatusLower, ['cancelado', 'cancelada'])) {
            $estatus = 'cancelada';
        } else {
            $estatus = mb_convert_case(preg_replace('/\s+/', ' ', $estatusLower), MB_CASE_TITLE, 'UTF-8');
        }

        // 3. Vendedor (conservar aunque esté vacío, solo limpiar espacios)
        $vendedor = trim($row['vendedor'] ?? '');
        $vendedor = preg_replace('/\s+/', ' ', $vendedor);

        // 4. Producto (limpiar espacios)
        $producto = trim($row['producto'] ?? '');
        $producto = preg_replace('/\s+/', ' ', $producto);
        if ($producto === '') {
            // El documento no dice qué hacer si el producto está vacío explícitamente,
            // pero el ejemplo dice "Vendedor vacío -> se conserva, Región vacía -> se descarta".
            // Asumimos que se conserva el producto, aunque si está vacío lo dejamos vacío.
        }

        // 5. Fecha
        $fecha = trim($row['fecha'] ?? '');
        if ($fecha === '') {
            return null; // Fecha vacía -> descartar
        }
        $fechaNormalizada = $this->parseDate($fecha);
        if (! $fechaNormalizada) {
            return null; // No se puede interpretar o día no existe -> descartar
        }

        // 6. Monto
        $montoStr = trim($row['monto'] ?? '');
        if ($montoStr === '') {
            return null; // descartar
        }
        $montoNormalizado = $this->parseMonto($montoStr);
        if ($montoNormalizado === null || $montoNormalizado < 0) {
            return null; // descartar si no numérico o negativo
        }

        return [
            'id_venta' => trim($row['id_venta']),
            'fecha' => $fechaNormalizada,
            'vendedor' => $vendedor,
            'region' => $region,
            'producto' => $producto,
            'monto' => $montoNormalizado,
            'estatus' => $estatus,
        ];
    }

    private function parseDate(string $date): ?string
    {
        // 2026-03-04 y 2026-03-04 00:00:00 -> año-mes-día
        // 04-03-2026 y 04/03/2026 -> día-mes-año

        // Quitar la hora si viene con formato 00:00:00 o cualquier hora
        $date = preg_replace('/\s+\d{2}:\d{2}:\d{2}$/', '', $date);

        // Patrón año-mes-día (4 dígitos, guion, 2 dígitos, guion, 2 dígitos)
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $date, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }

            return null;
        }

        // Patrón día-mes-año o día/mes/año
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $date, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }

            return null;
        }

        // Patrón año/mes/día
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }

            return null;
        }

        return null;
    }

    private function parseMonto(string $monto): ?float
    {
        // Quitar $ y espacios
        $monto = str_replace(['$', ' '], '', $monto);

        if ($monto === '' || strtolower($monto) === 'n/a') {
            return null;
        }

        // Check if point and comma are present
        $hasPoint = str_contains($monto, '.');
        $hasComma = str_contains($monto, ',');

        if ($hasPoint && $hasComma) {
            // "punto y coma" -> coma es separador de miles
            $monto = str_replace(',', '', $monto);
        } elseif ($hasComma && ! $hasPoint) {
            // "solo coma" -> coma es el separador decimal
            $monto = str_replace(',', '.', $monto);
        }

        if (! is_numeric($monto)) {
            return null;
        }

        return (float) $monto;
    }
}
