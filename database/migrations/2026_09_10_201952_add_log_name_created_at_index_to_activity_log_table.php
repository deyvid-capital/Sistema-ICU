<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La auditoría de accesos siempre filtra por `log_name` y ordena por fecha, y
 * los eventos de seguridad crecen mucho más rápido que los de negocio (cada
 * intento fallido deja una fila). El índice suelto de `log_name` que trae
 * Spatie no cubre la ordenación.
 */
return new class extends Migration
{
    private const INDICE = 'activity_log_log_name_created_at_index';

    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->index(['log_name', 'created_at'], self::INDICE);
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropIndex(self::INDICE);
        });
    }
};
