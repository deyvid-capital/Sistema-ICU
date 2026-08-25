<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los logos corporativos suelen ser oscuros sobre fondo transparente, así que
     * sobre un sidebar oscuro necesitan una placa clara detrás.
     * Valores: auto | claro | ninguno.
     */
    public function up(): void
    {
        Schema::table('appearance_settings', function (Blueprint $table) {
            $table->string('fondo_logo', 20)->nullable()->after('alto_logo');
        });
    }

    public function down(): void
    {
        Schema::table('appearance_settings', function (Blueprint $table) {
            $table->dropColumn('fondo_logo');
        });
    }
};
