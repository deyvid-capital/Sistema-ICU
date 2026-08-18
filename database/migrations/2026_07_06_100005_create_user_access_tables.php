<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('tipo', 50);
            $table->date('fecha');
            $table->foreignId('cargo_id')->nullable()->constrained('cargos', 'id_cargo')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['usuario_id', 'fecha']);
        });

        Schema::create('accesos_sharepoint', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sitio_id')->constrained('sitios_sharepoint')->restrictOnDelete();
            $table->boolean('estado')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['usuario_id', 'sitio_id']);
        });

        Schema::create('accesos_sistemas', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('id_sistema')->constrained('sistemas', 'id_sistema')->restrictOnDelete();
            $table->string('usuario_sistema', 50);
            $table->string('nivel_acceso', 100);
            $table->boolean('estado')->default(false);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accesos_daza', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('usuario_daza', 50)->nullable();
            $table->string('nivel_acceso', 50)->nullable();
            $table->foreignId('fondo_id')->constrained('fondos', 'id_fondo')->restrictOnDelete();
            $table->string('ambiente', 50)->nullable();
            $table->boolean('estado')->nullable();
            $table->date('fecha')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('software_excepciones', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('software_id')->constrained('softwares', 'id_software')->restrictOnDelete();
            $table->string('tipo_excepcion', 50)->nullable();
            $table->text('motivo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accesos_wifi', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('usuario_red', 50);
            $table->string('nivel_acceso', 50);
            $table->string('fortitoken', 50)->nullable();
            $table->boolean('estado');
            $table->date('fecha')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('licencias_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('licencia_id')->constrained('licencias')->restrictOnDelete();
            $table->date('fecha')->nullable();
            $table->boolean('estado');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['usuario_id', 'licencia_id']);
        });

        Schema::create('software_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('id_software')->constrained('softwares', 'id_software')->restrictOnDelete();
            $table->boolean('estado')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['usuario_id', 'id_software']);
        });

        Schema::create('cuentas_azure_ad', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('iniciales', 10)->nullable();
            $table->string('sincronizacion', 50)->nullable();
            $table->boolean('mfa')->nullable();
            $table->boolean('estado')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accesos_unidades_compartidas', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('unidad_compartida', 100);
            $table->boolean('estado')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accesos_virtuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internet_id')->constrained('accesos_wifi')->cascadeOnDelete();
            $table->foreignId('virtual_id')->constrained('virtuales')->restrictOnDelete();
            $table->foreignId('vpn_id')->constrained('recursos_vpn')->restrictOnDelete();
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['internet_id', 'virtual_id', 'vpn_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_virtuales');
        Schema::dropIfExists('accesos_unidades_compartidas');
        Schema::dropIfExists('cuentas_azure_ad');
        Schema::dropIfExists('software_usuarios');
        Schema::dropIfExists('licencias_usuarios');
        Schema::dropIfExists('accesos_wifi');
        Schema::dropIfExists('software_excepciones');
        Schema::dropIfExists('accesos_daza');
        Schema::dropIfExists('accesos_sistemas');
        Schema::dropIfExists('accesos_sharepoint');
        Schema::dropIfExists('movimientos');
    }
};
