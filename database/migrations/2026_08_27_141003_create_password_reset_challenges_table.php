<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Desafío OTP de recuperación de contraseña: un registro activo por correo.
     */
    public function up(): void
    {
        Schema::create('password_reset_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('intentos_fallidos')->default(0);
            $table->timestamp('bloqueado_hasta')->nullable();
            $table->timestamp('enviado_at');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_challenges');
    }
};
