<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('software_excepciones')
            ->whereNull('decidido_at')
            ->update(['estado_aprobacion' => 'aprobada']);
    }

    public function down(): void
    {
        DB::table('software_excepciones')
            ->whereNull('decidido_at')
            ->update(['estado_aprobacion' => 'pendiente']);
    }
};
