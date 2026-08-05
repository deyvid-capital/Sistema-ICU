<?php

namespace Database\Seeders;

use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\MantenimientoEquipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\ReciboEquipo;
use App\Models\TarjetaVideo;
use App\Models\User;
use Illuminate\Database\Seeder;

class EquiposSeeder extends Seeder
{
    public function run(): void
    {
        $modelos = collect([
            'Latitude 5540',
            'Latitude 7440',
            'Precision 5680',
            'ThinkPad T14 Gen 4',
            'EliteBook 840 G10',
        ])->map(fn (string $nombre): ModeloLaptop => ModeloLaptop::query()->updateOrCreate(
            ['nombre' => $nombre],
            ['nombre' => $nombre],
        ));

        $micros = collect([
            'Intel Core i5-1335U',
            'Intel Core i7-1365U',
            'Intel Core i7-13700H',
            'AMD Ryzen 7 7730U',
            'Intel Core Ultra 7 155H',
        ])->map(fn (string $nombre): Microprocesador => Microprocesador::query()->updateOrCreate(
            ['nombre_micro' => $nombre],
            ['nombre_micro' => $nombre],
        ));

        $rams = collect([
            '8GB DDR4',
            '16GB DDR4',
            '32GB DDR4',
            '16GB DDR5',
            '32GB DDR5',
        ])->map(fn (string $tipo): MemoriaRam => MemoriaRam::query()->updateOrCreate(
            ['tipo_ram' => $tipo],
            ['tipo_ram' => $tipo],
        ));

        $gpus = collect([
            'Intel Iris Xe',
            'Intel Arc Graphics',
            'NVIDIA RTX 4050',
            'AMD Radeon 780M',
            'Integrada',
        ])->map(fn (string $nombre): TarjetaVideo => TarjetaVideo::query()->updateOrCreate(
            ['nombre_gpu' => $nombre],
            ['nombre_gpu' => $nombre],
        ));

        $equipos = collect([
            ['service_tag' => 'ST100001', 'nombre_equipo' => 'BOCS-001', 'estado' => 'asignado'],
            ['service_tag' => 'ST100002', 'nombre_equipo' => 'BOCS-002', 'estado' => 'sin asignar'],
            ['service_tag' => 'ST100003', 'nombre_equipo' => 'BOCS-003', 'estado' => 'asignado'],
            ['service_tag' => 'ST100004', 'nombre_equipo' => 'LAPTOP-004', 'estado' => 'en mantenimiento'],
            ['service_tag' => 'ST100005', 'nombre_equipo' => 'BOCS-005', 'estado' => 'asignado'],
            ['service_tag' => 'ST100006', 'nombre_equipo' => 'BOCS-006', 'estado' => 'sin asignar'],
        ])->map(function (array $data, int $index) use ($modelos, $micros, $rams, $gpus): Equipo {
            return Equipo::query()->updateOrCreate(
                ['service_tag' => $data['service_tag']],
                [
                    'service_tag' => $data['service_tag'],
                    'nombre_equipo' => $data['nombre_equipo'],
                    'estado' => $data['estado'],
                    'modelo_id' => $modelos->get($index % $modelos->count())->id,
                    'microprocesador_id' => $micros->get($index % $micros->count())->id,
                    'ram_id' => $rams->get($index % $rams->count())->id,
                    'gpu_id' => $gpus->get($index % $gpus->count())->id,
                    'disco_duro' => '512GB SSD',
                    'proveedor' => 'Dell Technologies',
                    'so' => 'Windows 11 PRO',
                    'bloqueo_bios' => true,
                    'etiquetado' => true,
                    'codigo_activo' => 'ACT-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'inicio_garantia' => now()->subYear()->toDateString(),
                    'venc_garantia' => now()->addYears(2)->toDateString(),
                ],
            );
        });

        $cargadores = collect([
            ['serial' => 'CHG-DELL0001', 'marca' => 'Dell', 'estado' => 'asignado'],
            ['serial' => 'CHG-DELL0002', 'marca' => 'Dell', 'estado' => 'disponible'],
            ['serial' => 'CHG-HP0003', 'marca' => 'HP', 'estado' => 'asignado'],
            ['serial' => 'CHG-LEN0004', 'marca' => 'Lenovo', 'estado' => 'disponible'],
            ['serial' => 'CHG-DELL0005', 'marca' => 'Dell', 'estado' => 'asignado'],
        ])->map(fn (array $data): Cargador => Cargador::query()->updateOrCreate(
            ['serial' => $data['serial']],
            [
                'serial' => $data['serial'],
                'marca' => $data['marca'],
                'modelo' => '65W USB-C',
                'estado' => $data['estado'],
                'observaciones' => null,
            ],
        ));

        $usuarios = User::query()->inRandomOrder()->take(5)->get();

        if ($usuarios->isNotEmpty()) {
            foreach ($equipos->take(4) as $index => $equipo) {
                $usuario = $usuarios->get($index % $usuarios->count());

                AsignacionEquipo::query()->updateOrCreate(
                    [
                        'service_tag' => $equipo->service_tag,
                        'usuario_id' => $usuario->id,
                        'fecha_inicio' => now()->subMonths($index + 1)->toDateString(),
                    ],
                    [
                        'service_tag' => $equipo->service_tag,
                        'usuario_id' => $usuario->id,
                        'fecha_inicio' => now()->subMonths($index + 1)->toDateString(),
                        'fecha_fin' => null,
                        'motivo' => 'Asignación operativa',
                        'observaciones' => 'Registro de demostración.',
                    ],
                );
            }
        }

        foreach ($cargadores->take(3) as $index => $cargador) {
            $equipo = $equipos->get($index);

            AsignacionCargador::query()->updateOrCreate(
                ['serial_cargador' => $cargador->serial],
                [
                    'serial_cargador' => $cargador->serial,
                    'service_tag' => $equipo->service_tag,
                    'fecha_inicio' => now()->subMonths($index + 1)->toDateString(),
                    'fecha_fin' => null,
                ],
            );
        }

        foreach ($equipos->take(3) as $index => $equipo) {
            ReciboEquipo::query()->updateOrCreate(
                [
                    'equipo_id' => $equipo->service_tag,
                    'fecha' => now()->subDays(($index + 1) * 10)->toDateString(),
                ],
                [
                    'equipo_id' => $equipo->service_tag,
                    'fecha' => now()->subDays(($index + 1) * 10)->toDateString(),
                    'url' => 'https://example.com/recibos/'.$equipo->service_tag,
                    'observaciones' => 'Recibo de ingreso al inventario.',
                ],
            );

            MantenimientoEquipo::query()->updateOrCreate(
                [
                    'service_tag' => $equipo->service_tag,
                    'fecha_soporte' => now()->subDays(($index + 1) * 15)->toDateString(),
                ],
                [
                    'service_tag' => $equipo->service_tag,
                    'fecha_soporte' => now()->subDays(($index + 1) * 15)->toDateString(),
                    'cap_antes' => null,
                    'cap_despues' => null,
                    'observaciones' => 'Mantenimiento preventivo.',
                ],
            );
        }

        Equipo::query()->where('service_tag', 'ST100006')->first()?->delete();
        Cargador::query()->where('serial', 'CHG-LEN0004')->first()?->delete();
        ReciboEquipo::query()->latest('id')->first()?->delete();
    }
}
