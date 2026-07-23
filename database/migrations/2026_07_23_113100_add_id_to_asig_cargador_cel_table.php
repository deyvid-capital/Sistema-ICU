<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asig_cargador_cel', function (Blueprint $table) {
            $table->dropForeign(['celulares_id']);
            $table->dropForeign(['cargador_serial']);
        });

        Schema::table('asig_cargador_cel', function (Blueprint $table) {
            $table->dropPrimary(['celulares_id', 'cargador_serial']);
        });

        DB::statement('ALTER TABLE asig_cargador_cel ADD COLUMN id BIGSERIAL PRIMARY KEY');

        Schema::table('asig_cargador_cel', function (Blueprint $table) {
            $table->unique(['celulares_id', 'cargador_serial'], 'asig_cargador_cel_celular_cargador_unique');
            $table->foreign('celulares_id')->references('id')->on('celulares')->restrictOnDelete();
            $table->foreign('cargador_serial')->references('serial')->on('cargador_cel')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asig_cargador_cel', function (Blueprint $table) {
            $table->dropForeign(['celulares_id']);
            $table->dropForeign(['cargador_serial']);
            $table->dropUnique('asig_cargador_cel_celular_cargador_unique');
        });

        DB::statement('ALTER TABLE asig_cargador_cel DROP COLUMN id');

        Schema::table('asig_cargador_cel', function (Blueprint $table) {
            $table->primary(['celulares_id', 'cargador_serial']);
            $table->foreign('celulares_id')->references('id')->on('celulares')->restrictOnDelete();
            $table->foreign('cargador_serial')->references('serial')->on('cargador_cel')->restrictOnDelete();
        });
    }
};
