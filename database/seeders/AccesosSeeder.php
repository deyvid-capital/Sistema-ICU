<?php

namespace Database\Seeders;

use App\Models\AccesoDaza;
use App\Models\AccesoInternet;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\Fondo;
use App\Models\RecursoVpn;
use App\Models\Sistema;
use App\Models\SitioSharepoint;
use App\Models\User;
use App\Models\Virtual;
use Illuminate\Database\Seeder;

class AccesosSeeder extends Seeder
{
    public function run(): void
    {
        $fondos = collect([
            ['codigo' => 'F001', 'nombre_completo' => 'Fondo General', 'descripcion' => 'Fondo principal de operaciones.'],
            ['codigo' => 'F002', 'nombre_completo' => 'Fondo Inversiones', 'descripcion' => 'Fondo de inversiones institucionales.'],
            ['codigo' => 'F003', 'nombre_completo' => 'Fondo Pensiones', 'descripcion' => 'Fondo de pensiones y beneficios.'],
            ['codigo' => 'F004', 'nombre_completo' => 'Fondo Reserva', 'descripcion' => 'Reserva estratégica.'],
        ])->map(fn (array $fondo): Fondo => Fondo::query()->updateOrCreate(
            ['codigo' => $fondo['codigo']],
            $fondo,
        ));

        $sistemas = collect([
            ['nombre_sistema' => 'SAP ERP', 'descripcion' => 'Sistema de planificación de recursos empresariales.'],
            ['nombre_sistema' => 'Active Directory', 'descripcion' => 'Directorio activo corporativo.'],
            ['nombre_sistema' => 'Intranet ICU', 'descripcion' => 'Portal interno de la institución.'],
            ['nombre_sistema' => 'Sistema Contable', 'descripcion' => 'Gestión financiera y contable.'],
            ['nombre_sistema' => 'Help Desk', 'descripcion' => 'Mesa de ayuda y tickets.'],
        ])->map(fn (array $sistema): Sistema => Sistema::query()->updateOrCreate(
            ['nombre_sistema' => $sistema['nombre_sistema']],
            $sistema,
        ));

        $sitios = collect([
            ['nombre_sitio' => 'Documentos Corporativos', 'url' => 'https://sharepoint.example.com/sites/corporativo', 'descripcion' => 'Repositorio de documentos institucionales.', 'estado' => true],
            ['nombre_sitio' => 'Recursos Humanos', 'url' => 'https://sharepoint.example.com/sites/rrhh', 'descripcion' => 'Sitio del área de RRHH.', 'estado' => true],
            ['nombre_sitio' => 'Proyectos TI', 'url' => 'https://sharepoint.example.com/sites/ti', 'descripcion' => 'Gestión de proyectos tecnológicos.', 'estado' => true],
            ['nombre_sitio' => 'Archivo Histórico', 'url' => 'https://sharepoint.example.com/sites/archivo', 'descripcion' => 'Archivo histórico (inactivo).', 'estado' => false],
        ])->map(fn (array $sitio): SitioSharepoint => SitioSharepoint::query()->updateOrCreate(
            ['nombre_sitio' => $sitio['nombre_sitio']],
            $sitio,
        ));

        $vpns = collect([
            ['nombre_vpn' => 'VPN Corporativa SSL', 'descripcion' => 'Acceso remoto principal.', 'estado' => true],
            ['nombre_vpn' => 'VPN Desarrollo', 'descripcion' => 'Entorno de desarrollo remoto.', 'estado' => true],
            ['nombre_vpn' => 'VPN Proveedores', 'descripcion' => 'Acceso para proveedores externos.', 'estado' => false],
        ])->map(fn (array $vpn): RecursoVpn => RecursoVpn::query()->updateOrCreate(
            ['nombre_vpn' => $vpn['nombre_vpn']],
            $vpn,
        ));

        $virtuales = collect([
            ['maquina_virtual' => 'VM-APP-01', 'estado' => true],
            ['maquina_virtual' => 'VM-DB-01', 'estado' => true],
            ['maquina_virtual' => 'VM-DEV-01', 'estado' => true],
            ['maquina_virtual' => 'VM-LEGACY-01', 'estado' => false],
        ])->map(fn (array $virtual): Virtual => Virtual::query()->updateOrCreate(
            ['maquina_virtual' => $virtual['maquina_virtual']],
            $virtual,
        ));

        $usuarios = User::query()->inRandomOrder()->take(6)->get();

        if ($usuarios->isEmpty()) {
            return;
        }

        foreach ($usuarios as $index => $usuario) {
            $fondo = $fondos->get($index % $fondos->count());
            AccesoDaza::query()->updateOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'fondo_id' => $fondo->getKey(),
                    'usuario_daza' => strtolower(str_replace(' ', '.', $usuario->name)),
                ],
                [
                    'nivel_acceso' => ['Lectura', 'Escritura', 'Admin'][$index % 3],
                    'ambiente' => ['Produccion', 'Pruebas', 'Desarrollo'][$index % 3],
                    'estado' => true,
                    'fecha' => now()->subDays(random_int(1, 90))->toDateString(),
                ],
            );

            $sistema = $sistemas->random();
            AccesoSistema::query()->updateOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'id_sistema' => $sistema->getKey(),
                ],
                [
                    'usuario_sistema' => strtolower(explode(' ', $usuario->name)[0]),
                    'nivel_acceso' => 'Operador',
                    'estado' => true,
                    'fecha_inicio' => now()->subMonths(3)->toDateString(),
                    'fecha_fin' => null,
                    'observaciones' => 'Acceso asignado por seeder de demostración.',
                ],
            );

            $sitio = $sitios->where('estado', true)->random();
            AccesoSharepoint::query()->updateOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'sitio_id' => $sitio->id,
                ],
                ['estado' => true],
            );

            $internet = AccesoInternet::query()->updateOrCreate(
                ['usuario_id' => $usuario->id],
                [
                    'usuario_red' => strtolower(explode(' ', $usuario->name)[0]).'.red',
                    'nivel_acceso' => 'Estándar',
                    'fortitoken' => 'FTK-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'estado' => true,
                    'fecha' => now()->subDays(random_int(1, 60))->toDateString(),
                    'observaciones' => 'Acceso VPN corporativo.',
                ],
            );

            if ($index < 3) {
                AccesoVirtual::query()->updateOrCreate(
                    [
                        'internet_id' => $internet->id,
                        'virtual_id' => $virtuales->where('estado', true)->random()->id,
                        'vpn_id' => $vpns->where('estado', true)->random()->id,
                    ],
                    [],
                );
            }

            AccUdCompartida::query()->updateOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'unidad_compartida' => '\\\\servidor\\compartida-'.($index + 1),
                ],
                ['estado' => (bool) random_int(0, 1)],
            );
        }

        $deletedUser = $usuarios->first();
        if ($deletedUser) {
            AccesoDaza::query()->updateOrCreate(
                [
                    'usuario_id' => $deletedUser->id,
                    'fondo_id' => $fondos->last()->getKey(),
                    'usuario_daza' => 'eliminado.daza',
                ],
                [
                    'nivel_acceso' => 'Lectura',
                    'ambiente' => 'Produccion',
                    'estado' => false,
                    'fecha' => now()->subYear()->toDateString(),
                ],
            )->delete();

            AccesoInternet::query()->updateOrCreate(
                [
                    'usuario_id' => $usuarios->skip(1)->first()?->id ?? $deletedUser->id,
                    'usuario_red' => 'acceso.eliminado',
                ],
                [
                    'nivel_acceso' => 'Básico',
                    'estado' => false,
                    'fecha' => now()->subMonths(6)->toDateString(),
                ],
            )->delete();
        }
    }
}
