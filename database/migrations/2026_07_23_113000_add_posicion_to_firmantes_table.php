<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmantes', function (Blueprint $table) {
            $table->string('posicion', 20)->default('izquierda')->after('fecha');
        });

        $documentos = DB::table('firmantes')
            ->select('documento_type', 'documento_id')
            ->whereNull('deleted_at')
            ->groupBy('documento_type', 'documento_id')
            ->get();

        foreach ($documentos as $documento) {
            $firmantes = DB::table('firmantes')
                ->where('documento_type', $documento->documento_type)
                ->where('documento_id', $documento->documento_id)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get();

            $posiciones = ['izquierda', 'derecha', 'centro'];

            foreach ($firmantes as $index => $firmante) {
                DB::table('firmantes')
                    ->where('id', $firmante->id)
                    ->update([
                        'posicion' => $posiciones[$index] ?? 'centro',
                    ]);
            }
        }

        DB::statement('CREATE UNIQUE INDEX firmantes_documento_posicion_unique ON firmantes (documento_type, documento_id, posicion) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS firmantes_documento_posicion_unique');

        Schema::table('firmantes', function (Blueprint $table) {
            $table->dropColumn('posicion');
        });
    }
};
