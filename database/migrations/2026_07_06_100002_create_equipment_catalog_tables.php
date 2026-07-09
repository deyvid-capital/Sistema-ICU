<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_laptops', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->timestamps();
        });

        Schema::create('microprocesadores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_micro', 100);
            $table->timestamps();
        });

        Schema::create('memorias_ram', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_ram', 50);
            $table->timestamps();
        });

        Schema::create('tarjetas_video', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_gpu', 100);
            $table->timestamps();
        });

        Schema::create('equipos', function (Blueprint $table) {
            $table->string('service_tag', 50)->primary();
            $table->foreignId('modelo_id')->constrained('modelos_laptops')->onDelete('cascade');
            $table->foreignId('microprocesador_id')->constrained('microprocesadores')->onDelete('cascade');
            $table->string('disco_duro', 50)->nullable();
            $table->foreignId('ram_id')->constrained('memorias_ram')->onDelete('cascade');
            $table->foreignId('gpu_id')->constrained('tarjetas_video')->onDelete('cascade');
            $table->string('proveedor', 100)->nullable();
            $table->date('inicio_garantia')->nullable();
            $table->date('venc_garantia')->nullable();
            $table->string('nombre_equipo', 50)->default('BOCS');
            $table->boolean('bloqueo_bios')->default(true);
            $table->string('codigo_activo', 100)->nullable()->unique();
            $table->string('so', 50)->default('Windows 11 PRO');
            $table->string('licencia_so', 50)->nullable();
            $table->boolean('etiquetado')->default(false);
            $table->boolean('usuario_admin')->nullable();
            $table->string('estado', 50)->default('sin asignar');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cargadores', function (Blueprint $table) {
            $table->string('serial', 50)->primary();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('estado', 50)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asignacion_cargadores', function (Blueprint $table) {
            $table->string('serial_cargador', 50)->unique();
            $table->string('service_tag', 50)->unique();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('serial_cargador')->references('serial')->on('cargadores')->onDelete('cascade');
            $table->foreign('service_tag')->references('service_tag')->on('equipos')->onDelete('cascade');
        });

        Schema::create('recibo_equipo', function (Blueprint $table) {
            $table->id();
            $table->string('equipo_id', 50);
            $table->date('fecha');
            $table->string('url', 500)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('equipo_id')->references('service_tag')->on('equipos')->onDelete('cascade');
        });

        Schema::create('mantenimiento_equipo', function (Blueprint $table) {
            $table->id();
            $table->string('service_tag', 50)->index();
            $table->date('fecha_soporte');
            $table->string('cap_antes', 500)->nullable();
            $table->string('cap_despues', 500)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('service_tag')->references('service_tag')->on('equipos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_equipo');
        Schema::dropIfExists('recibo_equipo');
        Schema::dropIfExists('asignacion_cargadores');
        Schema::dropIfExists('cargadores');
        Schema::dropIfExists('equipos');
        Schema::dropIfExists('tarjetas_video');
        Schema::dropIfExists('memorias_ram');
        Schema::dropIfExists('microprocesadores');
        Schema::dropIfExists('modelos_laptops');
    }
};
