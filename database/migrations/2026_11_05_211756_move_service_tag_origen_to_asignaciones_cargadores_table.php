<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `service_tag_origen` (equipo dueño de un cargador prestado) pertenece a
     * asignaciones_cargadores, pero al consolidar las migraciones quedó por error en
     * asignaciones_equipos, donde nada lo usa. Se crea donde corresponde y se quita
     * la copia equivocada solo si está vacía.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('asignaciones_cargadores', 'service_tag_origen')) {
            Schema::table('asignaciones_cargadores', function (Blueprint $table) {
                $table->string('service_tag_origen', 50)->nullable()->after('service_tag');
                $table->foreign('service_tag_origen')
                    ->references('service_tag')
                    ->on('equipos')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('asignaciones_equipos', 'service_tag_origen')
            && ! DB::table('asignaciones_equipos')->whereNotNull('service_tag_origen')->exists()) {
            Schema::table('asignaciones_equipos', function (Blueprint $table) {
                $table->dropForeign(['service_tag_origen']);
                $table->dropColumn('service_tag_origen');
            });
        }
    }

    /**
     * Solo deshace el arreglo en asignaciones_cargadores; no recrea la columna errónea.
     */
    public function down(): void
    {
        if (Schema::hasColumn('asignaciones_cargadores', 'service_tag_origen')) {
            Schema::table('asignaciones_cargadores', function (Blueprint $table) {
                $table->dropForeign(['service_tag_origen']);
                $table->dropColumn('service_tag_origen');
            });
        }
    }
};
