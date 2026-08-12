<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accesos_virtual', function (Blueprint $table) {
            $table->boolean('estado')->default(true)->after('vpn_id');
        });
    }

    public function down(): void
    {
        Schema::table('accesos_virtual', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
