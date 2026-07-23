<?php

namespace Database\Seeders;

use App\Enums\FirmantePosicion;
use App\Models\AsigCargadorCel;
use App\Models\AsigCelular;
use App\Models\CargadorCel;
use App\Models\Celular;
use App\Models\EntregaCelular;
use App\Models\Firmante;
use App\Models\RecepcionCelular;
use App\Models\User;
use Illuminate\Database\Seeder;

class CelularesSeeder extends Seeder
{
    public function run(): void
    {
        $celulares = collect([
            [
                'id' => 'CEL-SAM-A54-01',
                'marca' => 'Samsung',
                'modelo' => 'Galaxy A54',
                'imei' => '356789101234501',
                'procesador' => 'Exynos 1380',
                'disco' => '128 GB',
                'ram' => '8 GB',
                'bateria' => '5000 mAh',
                'color' => 'Negro',
                'precio' => 2800,
            ],
            [
                'id' => 'CEL-APL-IP15-01',
                'marca' => 'Apple',
                'modelo' => 'iPhone 15',
                'imei' => '356789101234502',
                'procesador' => 'A16 Bionic',
                'disco' => '128 GB',
                'ram' => '6 GB',
                'bateria' => '3349 mAh',
                'color' => 'Azul',
                'precio' => 5200,
            ],
            [
                'id' => 'CEL-XIA-N13-01',
                'marca' => 'Xiaomi',
                'modelo' => 'Redmi Note 13',
                'imei' => '356789101234503',
                'procesador' => 'Snapdragon 685',
                'disco' => '256 GB',
                'ram' => '8 GB',
                'bateria' => '5000 mAh',
                'color' => 'Verde',
                'precio' => 1900,
            ],
            [
                'id' => 'CEL-MOT-G84-01',
                'marca' => 'Motorola',
                'modelo' => 'Moto G84',
                'imei' => '356789101234504',
                'procesador' => 'Snapdragon 695',
                'disco' => '256 GB',
                'ram' => '12 GB',
                'bateria' => '5000 mAh',
                'color' => 'Violeta',
                'precio' => 2100,
            ],
            [
                'id' => 'CEL-SAM-S23-01',
                'marca' => 'Samsung',
                'modelo' => 'Galaxy S23',
                'imei' => '356789101234505',
                'procesador' => 'Snapdragon 8 Gen 2',
                'disco' => '256 GB',
                'ram' => '8 GB',
                'bateria' => '3900 mAh',
                'color' => 'Crema',
                'precio' => 4500,
            ],
        ])->map(fn (array $data): Celular => Celular::query()->updateOrCreate(
            ['id' => $data['id']],
            [
                ...$data,
                'fecha_compra' => now()->subMonths(rand(3, 18))->toDateString(),
            ],
        ));

        $cargadores = collect([
            ['serial' => 'CCEL-SAM-0001', 'modelo' => 'Samsung 25W USB-C'],
            ['serial' => 'CCEL-APL-0002', 'modelo' => 'Apple 20W USB-C'],
            ['serial' => 'CCEL-XIA-0003', 'modelo' => 'Xiaomi 33W'],
            ['serial' => 'CCEL-MOT-0004', 'modelo' => 'Motorola 30W'],
            ['serial' => 'CCEL-SAM-0005', 'modelo' => 'Samsung 45W USB-C'],
        ])->map(fn (array $data): CargadorCel => CargadorCel::query()->updateOrCreate(
            ['serial' => $data['serial']],
            [
                'serial' => $data['serial'],
                'modelo' => $data['modelo'],
                'observacion' => null,
            ],
        ));

        foreach ($celulares->take(4) as $index => $celular) {
            $cargador = $cargadores->get($index);

            AsigCargadorCel::query()->updateOrCreate(
                [
                    'celulares_id' => $celular->id,
                    'cargador_serial' => $cargador->serial,
                ],
                [
                    'motivo' => 'Asignación inicial de cargador',
                    'fecha' => now()->subMonths(2)->toDateString(),
                ],
            );
        }

        $usuarios = User::query()->orderBy('name')->take(5)->get();

        if ($usuarios->isEmpty()) {
            $this->command?->warn('CelularesSeeder: no hay usuarios. Se omiten asignaciones/formularios.');

            return;
        }

        $asignaciones = collect();

        foreach ($celulares->take(4) as $index => $celular) {
            $usuario = $usuarios->get($index % $usuarios->count());

            $asignacion = AsigCelular::query()
                ->where('celular_id', $celular->id)
                ->whereNull('fecha_fin')
                ->first();

            if ($asignacion === null) {
                $asignacion = AsigCelular::query()->create([
                    'celular_id' => $celular->id,
                    'usuario_id' => $usuario->id,
                    'fecha_ini' => now()->subMonths(1)->toDateString(),
                    'fecha_fin' => null,
                ]);
            }

            $asignaciones->push($asignacion);
        }

        $tiUser = $usuarios->first();
        $adminUser = $usuarios->get(1) ?? $tiUser;

        foreach ($asignaciones->take(3) as $index => $asignacion) {
            $numero = str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT).'-'.now()->format('Y');

            $entrega = EntregaCelular::query()->updateOrCreate(
                ['asignacion_cel_id' => $asignacion->id],
                [
                    'numero' => $numero,
                    'fecha' => now()->subDays(10 - $index)->toDateString(),
                    'observaciones' => 'Celular entregado en buen estado.',
                    'nota' => null,
                ],
            );

            $this->seedFirmantes(
                $entrega,
                izquierda: $tiUser,
                centro: $adminUser,
                derecha: $asignacion->usuario ?? $usuarios->last(),
            );
        }

        $asignacionRecepcion = $asignaciones->first();

        if ($asignacionRecepcion !== null) {
            $recepcion = RecepcionCelular::query()->updateOrCreate(
                ['asignacion_cel_id' => $asignacionRecepcion->id],
                [
                    'numero' => '101-'.now()->format('Y'),
                    'fecha' => now()->subDays(2)->toDateString(),
                    'nota' => 'Recepción de prueba sin observaciones críticas.',
                ],
            );

            $this->seedFirmantes(
                $recepcion,
                izquierda: $tiUser,
                centro: $adminUser,
                derecha: $asignacionRecepcion->usuario ?? $usuarios->last(),
            );
        }
    }

    private function seedFirmantes(
        EntregaCelular|RecepcionCelular $documento,
        User $izquierda,
        User $centro,
        User $derecha,
    ): void {
        $map = [
            FirmantePosicion::Izquierda->value => $izquierda,
            FirmantePosicion::Centro->value => $centro,
            FirmantePosicion::Derecha->value => $derecha,
        ];

        foreach ($map as $posicion => $usuario) {
            Firmante::query()->updateOrCreate(
                [
                    'documento_type' => $documento::class,
                    'documento_id' => $documento->id,
                    'posicion' => $posicion,
                ],
                [
                    'usuario_id' => $usuario->id,
                    'fecha' => $documento->fecha?->toDateString() ?? now()->toDateString(),
                ],
            );
        }
    }
}
