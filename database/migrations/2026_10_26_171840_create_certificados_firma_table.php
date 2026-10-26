<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Firmas digitales (.p12) asignadas a los usuarios.
     *
     * El archivo vive cifrado en el disco privado. Los datos del certificado se
     * completan al activarlo, porque sin la contraseña del .p12 no se pueden leer.
     * Ver Docs/firma-digital/02-modelo-de-datos.md.
     */
    public function up(): void
    {
        Schema::create('certificados_firma', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('archivo')->nullable();
            $table->text('contrasena_p12_envuelta')->nullable();
            $table->string('contrasena_usuario_huella', 64)->nullable();
            $table->string('huella_sha256', 64)->nullable()->index();
            $table->string('titular_cn')->nullable();
            $table->string('titular_documento', 50)->nullable();
            $table->string('titular_tipo_documento', 20)->nullable();
            $table->string('titular_cargo')->nullable();
            $table->string('titular_organizacion')->nullable();
            $table->string('emisor')->nullable();
            $table->string('numero_serie', 100)->nullable();
            $table->timestamp('vigente_desde')->nullable();
            $table->timestamp('vigente_hasta')->nullable();
            $table->string('nombre_en_sello');
            $table->string('cargo_en_sello')->nullable();
            $table->string('estado', 30)->index();
            $table->timestamp('activado_at')->nullable();
            $table->foreignId('reemplaza_a_id')->nullable()->constrained('certificados_firma')->nullOnDelete();
            $table->unsignedTinyInteger('intentos_fallidos')->default(0);
            $table->timestamp('bloqueado_hasta')->nullable();
            $table->foreignUuid('cargado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Una sola firma activa y una sola pendiente por usuario (renovación sin cortes).
        foreach (['activo', 'pendiente_activacion'] as $estado) {
            DB::statement(
                "CREATE UNIQUE INDEX certificados_firma_usuario_{$estado}_unique ON certificados_firma (usuario_id) WHERE estado = '{$estado}' AND deleted_at IS NULL"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados_firma');
    }
};
