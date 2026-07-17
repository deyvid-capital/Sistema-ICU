<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('estado');
        });

        Schema::table('asignacion_cargadores', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('fecha_fin');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });

        Schema::table('asignacion_cargadores', function (Blueprint $table) {
            $table->dropColumn('observaciones');
        });
    }
};
