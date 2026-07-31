<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo')->nullable();
            $table->string('nombre', 150)->nullable();
            $table->string('url', 500)->nullable();
            $table->foreignId('movimiento_id')->constrained('movimientos')->restrictOnDelete();
            $table->date('fecha')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_movimientos');
    }
};
