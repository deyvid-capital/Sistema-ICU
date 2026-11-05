<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contraseña del .p12 que escribe el admin al activar, cifrada con
     * FIRMA_ENCRYPTION_KEY. Con ella la firma se vincula (o se reactiva) con la
     * contraseña del sistema del titular sin pedirle la del .p12.
     */
    public function up(): void
    {
        Schema::table('certificados_firma', function (Blueprint $table) {
            $table->text('contrasena_p12_custodia')->nullable()->after('contrasena_p12_envuelta');
        });
    }

    public function down(): void
    {
        Schema::table('certificados_firma', function (Blueprint $table) {
            $table->dropColumn('contrasena_p12_custodia');
        });
    }
};
