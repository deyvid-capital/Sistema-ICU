<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuración global de correo SMTP (una sola fila).
     * La contraseña se cifra en la aplicación (cast encrypted); la columna es TEXT.
     */
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('activo')->default(false);
            $table->string('mailer', 50)->nullable();
            $table->string('host', 255)->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('scheme', 20)->nullable();
            $table->string('encryption', 20)->nullable();
            $table->boolean('require_tls')->nullable();
            $table->string('username', 255)->nullable();
            $table->text('password')->nullable();
            $table->string('from_address', 255)->nullable();
            $table->string('from_name', 255)->nullable();
            $table->string('ehlo_domain', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
