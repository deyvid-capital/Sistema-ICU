<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un colaborador puede tener varios FortiToken (producción, contingencia...),
     * distinguidos por `nombre_token`. Los que ya existían quedan como "Producción".
     */
    public function up(): void
    {
        Schema::table('usuario_fortitokens', function (Blueprint $table) {
            $table->dropUnique(['usuario_id']);
            $table->index('usuario_id');
            $table->string('nombre_token', 100)->nullable()->after('usuario_id');
        });

        DB::table('usuario_fortitokens')->update(['nombre_token' => 'Producción']);

        Schema::table('usuario_fortitokens', function (Blueprint $table) {
            $table->string('nombre_token', 100)->nullable(false)->change();
        });

        // El nombre no se repite entre los tokens vigentes de un mismo colaborador.
        DB::statement(
            'CREATE UNIQUE INDEX usuario_fortitokens_usuario_nombre_unique ON usuario_fortitokens (usuario_id, nombre_token) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Vuelve a uno por colaborador: solo sobrevive el token más reciente.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS usuario_fortitokens_usuario_nombre_unique');

        DB::table('usuario_fortitokens')
            ->whereNotIn('id', DB::table('usuario_fortitokens')->selectRaw('max(id)')->groupBy('usuario_id'))
            ->delete();

        Schema::table('usuario_fortitokens', function (Blueprint $table) {
            $table->dropIndex(['usuario_id']);
            $table->dropColumn('nombre_token');
            $table->unique('usuario_id');
        });
    }
};
