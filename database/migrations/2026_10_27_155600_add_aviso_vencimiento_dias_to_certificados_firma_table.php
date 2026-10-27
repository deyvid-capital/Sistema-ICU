<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Último aviso de vencimiento enviado (30, 15 o 7 días), para no repetirlo.
     */
    public function up(): void
    {
        Schema::table('certificados_firma', function (Blueprint $table) {
            $table->unsignedTinyInteger('aviso_vencimiento_dias')->nullable()->after('vigente_hasta');
        });
    }

    public function down(): void
    {
        Schema::table('certificados_firma', function (Blueprint $table) {
            $table->dropColumn('aviso_vencimiento_dias');
        });
    }
};
