<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\Software;
use App\Models\SoftwareArea;
use App\Models\SoftwareExc;
use App\Models\SoftwareUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;

class SoftwareLicenciasSeeder extends Seeder
{
    public function run(): void
    {
        $softwares = collect([
            ['categoria' => 'Productividad', 'nombre_software' => 'Microsoft 365', 'estado' => true, 'fecha_autorizacion' => now()->subMonths(12)->toDateString(), 'descripcion' => 'Suite de productividad empresarial.'],
            ['categoria' => 'Comunicacion', 'nombre_software' => 'Microsoft Teams', 'estado' => true, 'fecha_autorizacion' => now()->subMonths(10)->toDateString(), 'descripcion' => 'Comunicacion interna y reuniones.'],
            ['categoria' => 'Diseno', 'nombre_software' => 'Adobe Acrobat', 'estado' => true, 'fecha_autorizacion' => now()->subMonths(8)->toDateString(), 'descripcion' => 'Gestion y firma de documentos PDF.'],
            ['categoria' => 'Seguridad', 'nombre_software' => 'FortiClient VPN', 'estado' => true, 'fecha_autorizacion' => now()->subMonths(14)->toDateString(), 'descripcion' => 'Acceso remoto seguro para usuarios.'],
            ['categoria' => 'Desarrollo', 'nombre_software' => 'Docker Desktop', 'estado' => true, 'fecha_autorizacion' => now()->subMonths(6)->toDateString(), 'descripcion' => 'Entorno de contenedores para desarrollo.'],
            ['categoria' => 'Productividad', 'nombre_software' => 'Notion', 'estado' => false, 'fecha_autorizacion' => now()->subMonths(4)->toDateString(), 'descripcion' => 'Plataforma de gestion documental y tareas.'],
            ['categoria' => 'Seguridad', 'nombre_software' => 'Windows Defender', 'estado' => true, 'fecha_autorizacion' => now()->subMonths(18)->toDateString(), 'descripcion' => 'Proteccion antivirus y antimalware.'],
            ['categoria' => 'Diseno', 'nombre_software' => 'Canva Pro', 'estado' => false, 'fecha_autorizacion' => now()->subMonths(3)->toDateString(), 'descripcion' => 'Diseno rapido para piezas graficas.'],
        ])->map(fn (array $software): Software => Software::query()->updateOrCreate(
            ['nombre_software' => $software['nombre_software']],
            $software,
        ));

        $licencias = collect([
            ['nombre_licencia' => 'Microsoft 365 E3', 'categoria' => 'MICROSOFT', 'descripcion' => 'Licencia corporativa M365.', 'estado' => true],
            ['nombre_licencia' => 'Windows Pro', 'categoria' => 'MICROSOFT', 'descripcion' => 'Licencia de sistema operativo Windows Pro.', 'estado' => true],
            ['nombre_licencia' => 'Adobe Creative Cloud', 'categoria' => 'ADOBE', 'descripcion' => 'Suite Adobe para diseno y edicion.', 'estado' => true],
            ['nombre_licencia' => 'ESET Endpoint', 'categoria' => 'ANTIVIRUS', 'descripcion' => 'Proteccion antivirus para estaciones.', 'estado' => true],
            ['nombre_licencia' => 'FortiToken VPN', 'categoria' => 'VPN', 'descripcion' => 'Autenticacion para acceso VPN.', 'estado' => true],
            ['nombre_licencia' => 'Licencia Legacy', 'categoria' => 'OTRO', 'descripcion' => 'Licencia heredada pendiente de migracion.', 'estado' => false],
        ])->map(fn (array $licencia): Licencia => Licencia::query()->updateOrCreate(
            ['nombre_licencia' => $licencia['nombre_licencia']],
            $licencia,
        ));

        $areas = Area::query()->inRandomOrder()->take(5)->get();
        $usuarios = User::query()->inRandomOrder()->take(8)->get();

        foreach ($softwares->take(5) as $software) {
            foreach ($areas->random(min(2, $areas->count())) as $area) {
                SoftwareArea::query()->updateOrCreate([
                    'software_id' => $software->getKey(),
                    'area_id' => $area->id_area,
                ]);
            }
        }

        foreach ($usuarios as $usuario) {
            $software = $softwares->random();
            SoftwareUsuario::query()->updateOrCreate(
                ['usuario_id' => $usuario->id, 'id_software' => $software->getKey()],
                ['estado' => (bool) random_int(0, 1)],
            );

            $licencia = $licencias->random();
            LicenciaUsuario::query()->updateOrCreate(
                ['usuario_id' => $usuario->id, 'licencia_id' => $licencia->id],
                ['fecha' => now()->subDays(random_int(1, 120))->toDateString(), 'estado' => (bool) random_int(0, 1)],
            );
        }

        foreach ($usuarios->take(3) as $usuario) {
            $software = $softwares->random();
            SoftwareExc::query()->updateOrCreate(
                [
                    'usuario_id' => $usuario->id,
                    'software_id' => $software->getKey(),
                    'tipo_excepcion' => 'Temporal',
                ],
                [
                    'motivo' => 'Acceso temporal autorizado para atencion de incidencia.',
                    'fecha_inicio' => now()->subDays(random_int(1, 30))->toDateString(),
                    'fecha_fin' => now()->addDays(random_int(7, 30))->toDateString(),
                ],
            );
        }
    }
}
