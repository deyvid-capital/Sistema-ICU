<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado de firma digital en los PDF de origen (ver Docs/firma-digital/09-documentos-firmados-en-origen.md).
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private const TABLAS = ['pdf_equipos', 'pdf_celulares', 'pdf_movimientos'];

    public function up(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->string('estado_firma', 20)->default('sin_firma')->index();
                $table->timestamp('firmado_at')->nullable();
                $table->foreignId('firma_documento_id')->nullable()->constrained('solicitud_firma_documentos')->nullOnDelete();
                $table->string('hash_sha256', 64)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLAS as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropConstrainedForeignId('firma_documento_id');
                $table->dropColumn(['estado_firma', 'firmado_at', 'hash_sha256']);
            });
        }
    }
};
