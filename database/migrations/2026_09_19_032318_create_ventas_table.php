<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->string('id_venta', 20)->primary();
            $table->date('fecha');
            $table->string('vendedor')->nullable();
            $table->string('region', 100);
            $table->string('producto', 150);
            $table->decimal('monto', 12, 2);
            $table->string('estatus', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
