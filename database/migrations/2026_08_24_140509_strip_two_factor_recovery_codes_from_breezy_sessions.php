<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los códigos de recuperación 2FA dejan de usarse; el admin resetea el 2FA.
     */
    public function up(): void
    {
        DB::table('breezy_sessions')->update([
            'two_factor_recovery_codes' => null,
        ]);
    }

    public function down(): void
    {
        // Irreversible: no se pueden reconstruir códigos ya eliminados.
    }
};
