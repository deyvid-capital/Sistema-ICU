<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('celulares', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->date('fecha_compra')->nullable();
            $table->integer('precio')->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('imei', 20)->nullable()->unique();
            $table->string('procesador', 100)->nullable();
            $table->string('disco', 50)->nullable();
            $table->string('ram', 50)->nullable();
            $table->string('bateria', 50)->nullable();
            $table->string('color', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cargadores_celulares', function (Blueprint $table) {
            $table->string('serial', 50)->primary();
            $table->string('modelo', 100)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asignaciones_cargadores_celulares', function (Blueprint $table) {
            $table->id();
            $table->string('celulares_id', 50);
            $table->string('cargador_serial', 50);
            $table->text('motivo')->nullable();
            $table->date('fecha')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['celulares_id', 'cargador_serial'], 'asignaciones_cargadores_celulares_par_unique');
            $table->foreign('celulares_id')->references('id')->on('celulares')->restrictOnDelete();
            $table->foreign('cargador_serial')->references('serial')->on('cargadores_celulares')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_cargadores_celulares');
        Schema::dropIfExists('cargadores_celulares');
        Schema::dropIfExists('celulares');
    }
};
