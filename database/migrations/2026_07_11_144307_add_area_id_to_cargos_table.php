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
        if (Schema::hasColumn('cargos', 'area_id')) {
            return;
        }

        Schema::table('cargos', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('id_cargo')->constrained('areas', 'id_area')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('cargos', 'area_id')) {
            return;
        }

        Schema::table('cargos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
        });
    }
};
