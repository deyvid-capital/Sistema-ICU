<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración global de almacenamiento de archivos (una sola fila).
     */
    public function up(): void
    {
        Schema::create('media_settings', function (Blueprint $table) {
            $table->id();
            $table->string('disco', 50)->nullable();
            $table->string('prefijo', 100)->nullable();
            $table->unsignedInteger('umbral_webp_kb')->nullable();
            $table->unsignedTinyInteger('calidad_webp')->nullable();
            $table->unsignedInteger('thumb_ancho')->nullable();
            $table->unsignedInteger('max_imagen_kb')->nullable();
            $table->unsignedInteger('max_pdf_kb')->nullable();
            $table->unsignedInteger('max_logo_kb')->nullable();
            $table->unsignedInteger('max_favicon_kb')->nullable();
            $table->string('visibilidad', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_settings');
    }
};
