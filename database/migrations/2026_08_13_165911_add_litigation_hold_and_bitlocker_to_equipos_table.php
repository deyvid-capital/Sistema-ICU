<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->boolean('litigation_hold')->default(false)->after('usuario_admin');
            $table->boolean('bitlocker')->default(false)->after('litigation_hold');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['litigation_hold', 'bitlocker']);
        });
    }
};
