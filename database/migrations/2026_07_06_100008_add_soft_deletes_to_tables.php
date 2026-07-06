<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tablas con eliminado suave: personas, activos, accesos y
     * documentos/actas. Los catálogos simples quedan fuera a propósito.
     */
    private array $tablas = [
        // Personas
        'users',
        'usuarios_externos',

        // Activos
        'equipos',
        'celulares',
        'cargadores',
        'cargador_cel',

        // Accesos
        'accesos_sistemas',
        'accesos_sharepoint',
        'accesos_daza',
        'accesos_virtual',
        'azure_ad',
        'internet',
        'acc_ud_compartidas',
        'licencias_usuarios',
        'software_usuarios',
        'software_exc',

        // Documentos / actas
        'movimientos',
        'asignacion_equipos',
        'entrega_equipos',
        'recepcion_equipos',
        'recibo_equipo',
        'nota_salida_equipos',
        'nota_recepcion_equipos',
        'mantenimiento_equipo',
        'asignacion_cargadores',
        'asig_celular',
        'entrega_celulares',
        'recepcion_celulares',
        'asig_cargador_cel',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
