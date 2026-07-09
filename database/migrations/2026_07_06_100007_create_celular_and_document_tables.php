<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asig_celular', function (Blueprint $table) {
            $table->id();
            $table->string('celular_id', 50);
            $table->foreignUuid('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha_ini')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('celular_id')->references('id')->on('celulares')->cascadeOnDelete();
        });

        Schema::create('entrega_celulares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_cel_id')->constrained('asig_celular')->cascadeOnDelete();
            $table->string('numero', 50);
            $table->date('fecha');
            $table->text('nota')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('recepcion_celulares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_cel_id')->constrained('asig_celular')->cascadeOnDelete();
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

        Schema::create('firmantes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha');
            $table->morphs('documento');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmantes');
        Schema::dropIfExists('pdf_celulares');
        Schema::dropIfExists('recepcion_celulares');
        Schema::dropIfExists('entrega_celulares');
        Schema::dropIfExists('asig_celular');
    }
};
