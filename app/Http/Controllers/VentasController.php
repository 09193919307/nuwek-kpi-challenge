<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VentasController extends Controller
{
    public function resumen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fecha_inicio' => 'nullable|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Parámetros inválidos.',
                'mensajes' => $validator->errors(),
            ], 400);
        }

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        if ($fechaInicio && $fechaFin && $fechaInicio > $fechaFin) {
            return response()->json([
                'error' => 'La fecha de inicio no puede ser posterior a la fecha de fin.',
            ], 400);
        }

        $query = Venta::where('estatus', 'cerrada');

        if ($fechaInicio) {
            $query->where('fecha', '>=', $fechaInicio);
        }

        if ($fechaFin) {
            $query->where('fecha', '<=', $fechaFin);
        }

        try {
            $totalVentas = (float) $query->sum('monto');
            $numeroVentas = $query->count();

            $porRegion = (clone $query)
                ->select('region', DB::raw('SUM(monto) as total'))
                ->groupBy('region')
                ->orderBy('total', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'region' => $item->region,
                        'total' => (float) $item->total,
                    ];
                });

            return response()->json([
                'total_ventas' => $totalVentas,
                'numero_ventas' => $numeroVentas,
                'por_region' => $porRegion,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en /api/ventas/resumen: '.$e->getMessage());

            return response()->json([
                'error' => 'Error interno del servidor.',
            ], 500);
        }
    }
}
