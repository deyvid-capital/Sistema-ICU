<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_celulares', function (Blueprint $table) {
            $table->id();
            $table->string('celular_id', 50);
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha_ini')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('celular_id')->references('id')->on('celulares')->restrictOnDelete();
        });

        Schema::create('entregas_celulares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_cel_id')->constrained('asignaciones_celulares')->cascadeOnDelete();
            $table->string('numero', 50);
            $table->date('fecha');
            $table->text('nota')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recepciones_celulares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_cel_id')->constrained('asignaciones_celulares')->cascadeOnDelete();
            $table->string('numero', 50);
            $table->date('fecha')->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pdf_celulares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->morphs('documento');
            $table->date('fecha')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('pdf_movimientos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo')->nullable();
            $table->string('nombre', 150)->nullable();
            $table->string('url', 500)->nullable();
            $table->foreignId('movimiento_id')->constrained('movimientos')->restrictOnDelete();
            $table->date('fecha')->nullable();
        });

        Schema::create('firmantes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->date('fecha');
            $table->string('posicion', 20)->default('izquierda');
            $table->morphs('documento');
            $table->timestamps();
            $table->softDeletes();
        });

        // Una sola firma por posición (izquierda/derecha/centro) en cada documento.
        DB::statement('CREATE UNIQUE INDEX firmantes_documento_posicion_unique ON firmantes (documento_type, documento_id, posicion) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('firmantes');
        Schema::dropIfExists('pdf_movimientos');
        Schema::dropIfExists('pdf_celulares');
        Schema::dropIfExists('recepciones_celulares');
        Schema::dropIfExists('entregas_celulares');
        Schema::dropIfExists('asignaciones_celulares');
    }
};
