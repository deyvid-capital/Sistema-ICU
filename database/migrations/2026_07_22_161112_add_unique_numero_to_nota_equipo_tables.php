<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nota_salida_equipos', function (Blueprint $table) {
            $table->unique('numero');
        });

        Schema::table('nota_recepcion_equipos', function (Blueprint $table) {
            $table->unique('numero');
        });
    }

    public function down(): void
    {
        Schema::table('nota_salida_equipos', function (Blueprint $table) {
            $table->dropUnique(['numero']);
        });

        Schema::table('nota_recepcion_equipos', function (Blueprint $table) {
            $table->dropUnique(['numero']);
        });
    }
};
