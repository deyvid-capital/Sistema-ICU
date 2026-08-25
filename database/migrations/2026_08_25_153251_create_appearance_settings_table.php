<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración visual del panel. Tabla de una sola fila: los valores nulos
     * se resuelven contra el tema definido en config/apariencia.php.
     */
    public function up(): void
    {
        Schema::create('appearance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tema', 50)->default('safi');
            $table->string('color_primario', 20)->nullable();
            $table->string('color_gris', 20)->nullable();
            $table->string('color_sidebar_claro', 20)->nullable();
            $table->string('color_sidebar_oscuro', 20)->nullable();
            $table->string('color_topbar_claro', 20)->nullable();
            $table->string('color_topbar_oscuro', 20)->nullable();
            $table->string('logo_claro')->nullable();
            $table->string('logo_oscuro')->nullable();
            $table->string('favicon')->nullable();
            $table->string('alto_logo', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appearance_settings');
    }
};
