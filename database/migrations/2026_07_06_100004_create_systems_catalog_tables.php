<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sistemas', function (Blueprint $table) {
            $table->id('id_sistema');
            $table->string('nombre_sistema', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('fondos', function (Blueprint $table) {
            $table->id('id_fondo');
            $table->string('codigo', 20)->unique();
            $table->string('nombre_completo', 200)->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('sitios_sharepoint', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_sitio', 200);
            $table->string('url', 500);
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(false);
            $table->timestamps();
        });

        Schema::create('recursos_vpn', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_vpn', 200);
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(false);
            $table->timestamps();
        });

        Schema::create('virtuales', function (Blueprint $table) {
            $table->id();
            $table->string('maquina_virtual', 100);
            $table->boolean('estado')->default(false);
            $table->timestamps();
        });

        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_licencia', 150);
            $table->string('categoria', 50)->default('MICROSOFT');
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->nullable()->default(true);
            $table->timestamps();
        });

        Schema::create('softwares', function (Blueprint $table) {
            $table->id('id_software');
            $table->string('categoria', 100)->nullable();
            $table->string('nombre_software', 150);
            $table->boolean('estado');
            $table->date('fecha_autorizacion')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('software_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->nullable()->constrained('softwares', 'id_software')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas', 'id_area')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['software_id', 'area_id']);
        });

        Schema::create('usuarios_externos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->string('carnet', 50);
            $table->string('empresa', 150)->nullable();
            $table->string('cargo', 150);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios_externos');
        Schema::dropIfExists('software_areas');
        Schema::dropIfExists('softwares');
        Schema::dropIfExists('licencias');
        Schema::dropIfExists('virtuales');
        Schema::dropIfExists('recursos_vpn');
        Schema::dropIfExists('sitios_sharepoint');
        Schema::dropIfExists('fondos');
        Schema::dropIfExists('sistemas');
    }
};
