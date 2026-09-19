<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportVentas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ventas {file : Path al archivo CSV}';
    protected $description = 'Importa y limpia el archivo CSV de ventas';

    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file) || !is_readable($file)) {
            $this->error("El archivo no existe o no es legible: {$file}");
            return 1;
        }

        $cleaner = new \App\Services\VentasCleaner();
        
        $leidas = 0;
        $invalidas = 0;
        $duplicadas = 0;
        $insertadas = 0;
        $existentes = 0;

        $handle = fopen($file, 'r');
        
        // Leer encabezados (ignoramos el BOM si lo hubiera)
        $headers = fgetcsv($handle);
        if ($headers && count($headers) > 0) {
            // Eliminar BOM del primer encabezado si existe
            $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);
        }
        
        $expectedHeaders = ['id_venta', 'fecha', 'vendedor', 'region', 'producto', 'monto', 'estatus'];
        // Verificamos de forma simple, aunque los datos reales pueden tener variaciones menores en el BOM
        
        $validRows = [];
        
        // Leer y limpiar
        while (($row = fgetcsv($handle)) !== false) {
            $leidas++;
            
            // Mapear con los encabezados esperados (asegurando el número de columnas)
            if (count($row) < count($expectedHeaders)) {
                $row = array_pad($row, count($expectedHeaders), '');
            }
            $mapped = array_combine($expectedHeaders, array_slice($row, 0, count($expectedHeaders)));
            
            $cleaned = $cleaner->clean($mapped);
            
            if ($cleaned === null) {
                $invalidas++;
                // $this->warn("Fila inválida descartada: " . json_encode($mapped, JSON_UNESCAPED_UNICODE));
                continue;
            }
            
            $id = $cleaned['id_venta'];
            if (isset($validRows[$id])) {
                $duplicadas++;
                // $this->warn("Fila duplicada en archivo descartada: {$id}");
                continue;
            }
            
            $validRows[$id] = $cleaned;
        }
        fclose($handle);

        // Insertar en BD transaccionalmente
        \Illuminate\Support\Facades\DB::transaction(function () use ($validRows, &$insertadas, &$existentes) {
            foreach ($validRows as $row) {
                // Check if exists
                if (\App\Models\Venta::where('id_venta', $row['id_venta'])->exists()) {
                    $existentes++;
                    continue;
                }
                
                \App\Models\Venta::create($row);
                $insertadas++;
            }
        });

        $this->info("Importación completada.");
        $this->info("Filas leídas (sin encabezado): {$leidas}");
        $this->info("Filas inválidas: {$invalidas}");
        $this->info("Repeticiones válidas eliminadas del archivo: {$duplicadas}");
        $this->info("Ventas únicas conservadas (intentadas): " . count($validRows));
        $this->info("Insertadas: {$insertadas}");
        $this->info("Ya existentes en BD: {$existentes}");

        return 0;
    }
}
