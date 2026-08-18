<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_equipos', function (Blueprint $table) {
            $table->id();
            $table->string('service_tag', 50);
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('service_tag')->references('service_tag')->on('equipos')->restrictOnDelete();
        });

        Schema::create('entregas_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones_equipos')->cascadeOnDelete();
            $table->string('numero', 20)->nullable()->unique();
            $table->date('fecha');
            $table->boolean('mouse')->default(false);
            $table->boolean('cooler')->default(false);
            $table->boolean('audifonos')->default(false);
            $table->text('observaciones')->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recepciones_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_id')->constrained('asignaciones_equipos')->cascadeOnDelete();
            $table->string('numero', 20)->unique();
            $table->date('fecha');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pdf_equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->morphs('documento');
            $table->date('fecha')->nullable();
            $table->string('url', 500)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('notas_salida_equipos', function (Blueprint $table) {
            $table->id();
            $table->string('equipo_id', 50);
            $table->foreignId('usuario_externo_id')->constrained('usuarios_externos')->restrictOnDelete();
            $table->string('numero', 20)->unique();
            $table->date('fecha');
            $table->text('nota')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('equipo_id')->references('service_tag')->on('equipos')->restrictOnDelete();
        });

        Schema::create('notas_recepcion_equipos', function (Blueprint $table) {
            $table->id();
            $table->string('equipo_id', 50);
            $table->foreignId('usuario_externo_id')->constrained('usuarios_externos')->restrictOnDelete();
            $table->string('numero', 20)->unique();
            $table->date('fecha');
            $table->text('nota')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('equipo_id')->references('service_tag')->on('equipos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_recepcion_equipos');
        Schema::dropIfExists('notas_salida_equipos');
        Schema::dropIfExists('pdf_equipos');
        Schema::dropIfExists('recepciones_equipos');
        Schema::dropIfExists('entregas_equipos');
        Schema::dropIfExists('asignaciones_equipos');
    }
};
