<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foreign keys that reference a catalog/actor (not a true composition
     * detail record). Cascading a delete through these would silently wipe
     * unrelated modules' history. Left as CASCADE: entrega_equipos,
     * recepcion_equipos, entrega_celulares, recepcion_celulares
     * (asignacion_id / asignacion_cel_id) and accesos_virtual.internet_id —
     * those are true sub-documents of their single owner.
     *
     * @var array<string, array<int, array{0: string, 1: string, 2: string}>>
     */
    private array $foreignKeys = [
        'cargos' => [
            ['area_id', 'areas', 'id_area'],
        ],
        'users' => [
            ['id_area', 'areas', 'id_area'],
            ['id_cargo', 'cargos', 'id_cargo'],
        ],
        'equipos' => [
            ['modelo_id', 'modelos_laptops', 'id'],
            ['microprocesador_id', 'microprocesadores', 'id'],
            ['ram_id', 'memorias_ram', 'id'],
            ['gpu_id', 'tarjetas_video', 'id'],
        ],
        'asignacion_cargadores' => [
            ['serial_cargador', 'cargadores', 'serial'],
            ['service_tag', 'equipos', 'service_tag'],
        ],
        'recibo_equipo' => [
            ['equipo_id', 'equipos', 'service_tag'],
        ],
        'mantenimiento_equipo' => [
            ['service_tag', 'equipos', 'service_tag'],
        ],
        'asig_cargador_cel' => [
            ['celulares_id', 'celulares', 'id'],
            ['cargador_serial', 'cargador_cel', 'serial'],
        ],
        'software_areas' => [
            ['software_id', 'softwares', 'id_software'],
            ['area_id', 'areas', 'id_area'],
        ],
        'movimientos' => [
            ['usuario_id', 'users', 'id'],
            ['cargo_id', 'cargos', 'id_cargo'],
        ],
        'accesos_sharepoint' => [
            ['usuario_id', 'users', 'id'],
            ['sitio_id', 'sitios_sharepoint', 'id'],
        ],
        'accesos_sistemas' => [
            ['usuario_id', 'users', 'id'],
            ['id_sistema', 'sistemas', 'id_sistema'],
        ],
        'accesos_daza' => [
            ['usuario_id', 'users', 'id'],
            ['fondo_id', 'fondos', 'id_fondo'],
        ],
        'software_exc' => [
            ['usuario_id', 'users', 'id'],
            ['software_id', 'softwares', 'id_software'],
        ],
        'internet' => [
            ['usuario_id', 'users', 'id'],
        ],
        'licencias_usuarios' => [
            ['usuario_id', 'users', 'id'],
            ['licencia_id', 'licencias', 'id'],
        ],
        'software_usuarios' => [
            ['usuario_id', 'users', 'id'],
            ['id_software', 'softwares', 'id_software'],
        ],
        'azure_ad' => [
            ['usuario_id', 'users', 'id'],
        ],
        'acc_ud_compartidas' => [
            ['usuario_id', 'users', 'id'],
        ],
        'accesos_virtual' => [
            ['virtual_id', 'virtuales', 'id'],
            ['vpn_id', 'recursos_vpn', 'id'],
        ],
        'asignacion_equipos' => [
            ['usuario_id', 'users', 'id'],
            ['service_tag', 'equipos', 'service_tag'],
        ],
        'nota_salida_equipos' => [
            ['usuario_externo_id', 'usuarios_externos', 'id'],
            ['equipo_id', 'equipos', 'service_tag'],
        ],
        'nota_recepcion_equipos' => [
            ['usuarios_externo_id', 'usuarios_externos', 'id'],
            ['equipo_id', 'equipos', 'service_tag'],
        ],
        'asig_celular' => [
            ['usuario_id', 'users', 'id'],
            ['celular_id', 'celulares', 'id'],
        ],
        'firmantes' => [
            ['usuario_id', 'users', 'id'],
        ],
    ];

    public function up(): void
    {
        $this->rebuild(onDelete: 'restrict');
    }

    public function down(): void
    {
        $this->rebuild(onDelete: 'cascade');
    }

    private function rebuild(string $onDelete): void
    {
        foreach ($this->foreignKeys as $table => $definitions) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $definitions, $onDelete): void {
                foreach ($definitions as [$column, $refTable, $refColumn]) {
                    $blueprint->dropForeign("{$table}_{$column}_foreign");
                    $blueprint->foreign($column)
                        ->references($refColumn)
                        ->on($refTable)
                        ->onDelete($onDelete);
                }
            });
        }
    }
};
