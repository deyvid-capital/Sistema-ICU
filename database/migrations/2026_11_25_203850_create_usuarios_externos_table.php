<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios_externos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->string('nombre_contacto', 200);
            $table->string('empresa', 150);
            $table->string('cargo', 150);
            $table->string('correo', 150);
            $table->foreignUuid('responsable_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sitio_sharepoint_id')->nullable()->constrained('sitios_sharepoint')->restrictOnDelete();
            $table->string('onedrive', 500)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->string('motivo', 255);
            $table->boolean('estado')->default(true);
            $table->string('permiso', 100);
            $table->timestamp('recordatorio_enviado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'fecha_fin']);
        });

        Schema::create('pdf_usuarios_externos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('url', 500);
            $table->foreignId('usuario_externo_id')->constrained('usuarios_externos')->restrictOnDelete();
            $table->date('fecha')->nullable();
            $table->string('estado_firma', 20)->default('sin_firma')->index();
            $table->timestamp('firmado_at')->nullable();
            $table->foreignId('firma_documento_id')->nullable()->constrained('solicitud_firma_documentos')->nullOnDelete();
            $table->string('hash_sha256', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_usuarios_externos');
        Schema::dropIfExists('usuarios_externos');
    }
};
