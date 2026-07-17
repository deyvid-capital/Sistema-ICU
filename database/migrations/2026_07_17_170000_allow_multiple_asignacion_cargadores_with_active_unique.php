<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('asignacion_cargadores'))
            ->pluck('name')
            ->all();

        Schema::table('asignacion_cargadores', function (Blueprint $table) use ($indexes) {
            if (in_array('asignacion_cargadores_serial_cargador_unique', $indexes, true)) {
                $table->dropUnique('asignacion_cargadores_serial_cargador_unique');
            }

            if (in_array('asignacion_cargadores_service_tag_unique', $indexes, true)) {
                $table->dropUnique('asignacion_cargadores_service_tag_unique');
            }
        });

        // Historial permitido: solo una fila ACTIVA (fecha_fin null) por cargador/equipo.
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS asignacion_cargadores_serial_activo_unique
            ON asignacion_cargadores (serial_cargador)
            WHERE deleted_at IS NULL AND fecha_fin IS NULL
        ');

        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS asignacion_cargadores_equipo_activo_unique
            ON asignacion_cargadores (service_tag)
            WHERE deleted_at IS NULL AND fecha_fin IS NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS asignacion_cargadores_serial_activo_unique');
        DB::statement('DROP INDEX IF EXISTS asignacion_cargadores_equipo_activo_unique');

        Schema::table('asignacion_cargadores', function (Blueprint $table) {
            $table->unique('serial_cargador');
            $table->unique('service_tag');
        });
    }
};
