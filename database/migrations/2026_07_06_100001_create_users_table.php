<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->index()->after('id');
        });

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 200);
            $table->string('nombres')->nullable();
            $table->string('apellidos')->nullable();
            $table->string('carnet', 50)->nullable()->index();
            $table->foreignId('id_area')->nullable()->constrained('areas', 'id_area')->restrictOnDelete();
            $table->foreignId('id_cargo')->nullable()->constrained('cargos', 'id_cargo')->restrictOnDelete();
            $table->string('email', 100)->unique();
            $table->string('telefono', 30)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            // Control de acceso al panel (gestionado por super-admin).
            $table->boolean('acceso_habilitado')->default(false)->index();
            $table->timestamp('acceso_expira_at')->nullable()->index();
            $table->boolean('requiere_2fa')->default(false);
            // Bloqueo por intentos de inicio de sesión fallidos.
            $table->unsignedSmallInteger('intentos_fallidos')->default(0);
            $table->timestamp('bloqueado_at')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->index()->after('id');
        });
    }
};
