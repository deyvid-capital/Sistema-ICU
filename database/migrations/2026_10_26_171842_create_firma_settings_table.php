<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración global del módulo de firma digital (una sola fila).
     */
    public function up(): void
    {
        Schema::create('firma_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('requiere_2fa_al_firmar')->default(false);
            $table->unsignedTinyInteger('intentos_maximos')->default(5);
            $table->unsignedSmallInteger('minutos_bloqueo')->default(15);
            $table->unsignedSmallInteger('plazo_dias_por_defecto')->default(3);
            $table->unsignedSmallInteger('recordatorio_horas_por_defecto')->default(24);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firma_settings');
    }
};
