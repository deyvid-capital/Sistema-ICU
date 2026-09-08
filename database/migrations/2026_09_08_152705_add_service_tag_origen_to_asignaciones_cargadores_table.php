<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asignaciones_cargadores', function (Blueprint $table) {
            $table->string('service_tag_origen', 50)->nullable()->after('service_tag');
            $table->foreign('service_tag_origen')
                ->references('service_tag')
                ->on('equipos')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignaciones_cargadores', function (Blueprint $table) {
            $table->dropForeign(['service_tag_origen']);
            $table->dropColumn('service_tag_origen');
        });
    }
};
