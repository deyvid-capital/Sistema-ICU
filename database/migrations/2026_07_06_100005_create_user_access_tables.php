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
            $table->string('usuario_id', 500)->nullable();
            $table->string('tipo', 50);
            $table->date('fecha');
            $table->foreignId('cargo_id')->nullable()->constrained('cargos', 'id_cargo');
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users');
            $table->index(['usuario_id', 'fecha']);
        });

        Schema::create('accesos_sharepoint', function (Blueprint $table) {
            $table->string('usuario_id', 500);
            $table->foreignId('sitio_id')->constrained('sitios_sharepoint');
            $table->boolean('estado')->default(false);
            $table->timestamps();

            $table->primary(['usuario_id', 'sitio_id']);
            $table->foreign('usuario_id')->references('id')->on('users');
        });

        Schema::create('accesos_sistemas', function (Blueprint $table) {
            $table->id();
            $table->string('usuario_id', 500);
            $table->foreignId('id_sistema')->constrained('sistemas', 'id_sistema');
            $table->string('usuario_sistema', 50);
            $table->string('nivel_acceso', 100);
            $table->boolean('estado')->default(false);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users');
        });

        Schema::create('accesos_daza', function (Blueprint $table) {
            $table->id();
            $table->string('usuario_id', 500);
            $table->string('usuario_daza', 50);
            $table->string('nivel_acceso', 50);
            $table->foreignId('fondo_id')->constrained('fondos', 'id_fondo');
            $table->string('ambiente', 50);
            $table->boolean('estado')->nullable();
            $table->date('fecha')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users');
        });

        Schema::create('software_exc', function (Blueprint $table) {
            $table->id();
            $table->string('usuario_id', 500);
            $table->foreignId('software_id')->constrained('softwares', 'id_software');
            $table->string('tipo_excepcion', 50)->nullable();
            $table->text('motivo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users');
        });

        Schema::create('internet', function (Blueprint $table) {
            $table->id();
            $table->string('usuario_id', 500);
            $table->string('usuario_red', 50);
            $table->string('nivel_acceso', 50);
            $table->string('fortitoken', 50)->nullable();
            $table->boolean('estado');
            $table->date('fecha')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users');
        });

        Schema::create('licencias_usuarios', function (Blueprint $table) {
            $table->string('usuario_id', 500);
            $table->foreignId('licencia_id')->constrained('licencias');
            $table->date('fecha')->nullable();
            $table->boolean('estado');
            $table->timestamps();

            $table->primary(['usuario_id', 'licencia_id']);
            $table->foreign('usuario_id')->references('id')->on('users');
        });

        Schema::create('software_usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('usuario_id', 500);
            $table->foreignId('id_software')->constrained('softwares', 'id_software');
            $table->boolean('estado')->default(false);
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users');
            $table->unique(['usuario_id', 'id_software']);
        });

        Schema::create('azure_ad', function (Blueprint $table) {
            $table->id();
            $table->string('usuario_id', 500);
            $table->string('iniciales', 10)->nullable();
            $table->string('sincronizacion', 50)->nullable();
            $table->boolean('mfa')->nullable();
            $table->boolean('estado')->default(false);
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users');
        });

        Schema::create('acc_ud_compartidas', function (Blueprint $table) {
            $table->id();
            $table->string('usuario_id', 500);
            $table->string('unidad_compartida', 100);
            $table->boolean('estado')->default(false);
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('users');
        });

        Schema::create('accesos_virtual', function (Blueprint $table) {
            $table->foreignId('internet_id')->constrained('internet');
            $table->integer('virtual_id');
            $table->foreignId('vpn_id')->constrained('recursos_vpn');
            $table->timestamps();

            $table->primary(['internet_id', 'virtual_id', 'vpn_id']);
            $table->foreign('virtual_id')->references('id')->on('virtuales');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accesos_virtual');
        Schema::dropIfExists('acc_ud_compartidas');
        Schema::dropIfExists('azure_ad');
        Schema::dropIfExists('software_usuarios');
        Schema::dropIfExists('licencias_usuarios');
        Schema::dropIfExists('internet');
        Schema::dropIfExists('software_exc');
        Schema::dropIfExists('accesos_daza');
        Schema::dropIfExists('accesos_sistemas');
        Schema::dropIfExists('accesos_sharepoint');
        Schema::dropIfExists('movimientos');
    }
};
