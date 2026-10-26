<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Envíos a firmar: un envío (solicitud) incluye uno o varios documentos y uno o
     * varios firmantes; cada firmante tiene un recuadro (firma) en cada documento.
     * Ver Docs/firma-digital/02-modelo-de-datos.md.
     */
    public function up(): void
    {
        Schema::create('solicitudes_firma', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->nullable()->unique();
            $table->string('asunto');
            $table->text('mensaje')->nullable();
            $table->boolean('orden_obligatorio')->default(false);
            $table->string('estado', 20)->index();
            $table->date('fecha_limite')->nullable();
            $table->unsignedSmallInteger('recordatorio_cada_horas')->nullable();
            $table->foreignUuid('creado_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('enviada_at')->nullable();
            $table->timestamp('finalizada_at')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->foreignUuid('cancelada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('solicitud_firma_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_firma_id')->constrained('solicitudes_firma')->cascadeOnDelete();
            $table->string('nombre');
            $table->nullableMorphs('origen');
            $table->string('archivo_original');
            $table->string('archivo_actual');
            $table->string('hash_actual', 64);
            $table->unsignedInteger('version')->default(0);
            $table->json('paginas');
            $table->string('estado', 20)->index();
            $table->timestamp('completado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('solicitud_firma_firmantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_firma_id')->constrained('solicitudes_firma')->cascadeOnDelete();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('orden');
            $table->timestamp('ultimo_recordatorio_at')->nullable();
            $table->timestamps();

            $table->unique(['solicitud_firma_id', 'usuario_id']);
        });

        Schema::create('solicitud_firma_firmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('solicitud_firma_documentos')->cascadeOnDelete();
            $table->foreignId('firmante_id')->constrained('solicitud_firma_firmantes')->cascadeOnDelete();
            $table->string('campo', 50);
            $table->unsignedSmallInteger('pagina');
            $table->decimal('x', 8, 2);
            $table->decimal('y', 8, 2);
            $table->decimal('ancho', 8, 2);
            $table->decimal('alto', 8, 2);
            $table->string('estado', 20)->index();
            $table->foreignId('certificado_firma_id')->nullable()->constrained('certificados_firma')->nullOnDelete();
            $table->timestamp('firmado_at')->nullable();
            $table->text('motivo_rechazo')->nullable();
            $table->timestamps();

            $table->unique(['documento_id', 'firmante_id']);
        });

        Schema::create('solicitud_firma_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('solicitud_firma_documentos')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('archivo');
            $table->string('hash_sha256', 64);
            $table->foreignId('firma_id')->nullable()->constrained('solicitud_firma_firmas')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['documento_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_firma_versiones');
        Schema::dropIfExists('solicitud_firma_firmas');
        Schema::dropIfExists('solicitud_firma_firmantes');
        Schema::dropIfExists('solicitud_firma_documentos');
        Schema::dropIfExists('solicitudes_firma');
    }
};
