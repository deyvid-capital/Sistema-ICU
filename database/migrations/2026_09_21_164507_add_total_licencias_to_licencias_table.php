<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->unsignedInteger('total_licencias')
                ->default(0)
                ->after('estado');
        });

        $asignadas = DB::table('licencias_usuarios')
            ->select('licencia_id', DB::raw('count(*) as total'))
            ->where('estado', true)
            ->whereNull('deleted_at')
            ->groupBy('licencia_id')
            ->pluck('total', 'licencia_id');

        foreach ($asignadas as $licenciaId => $total) {
            DB::table('licencias')
                ->where('id', $licenciaId)
                ->update(['total_licencias' => $total]);
        }
    }

    public function down(): void
    {
        Schema::table('licencias', function (Blueprint $table) {
            $table->dropColumn('total_licencias');
        });
    }
};
