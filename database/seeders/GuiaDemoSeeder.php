<?php

namespace Database\Seeders;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Models\Area;
use App\Models\AsigCargadorCel;
use App\Models\AsigCelular;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Cargador;
use App\Models\CargadorCel;
use App\Models\Cargo;
use App\Models\Celular;
use App\Models\EntregaCelular;
use App\Models\EntregaEquipo;
use App\Models\Equipo;
use App\Models\MantenimientoEquipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\Movimiento;
use App\Models\NotaRecepcionEquipo;
use App\Models\NotaSalidaEquipo;
use App\Models\RecepcionCelular;
use App\Models\RecepcionEquipo;
use App\Models\ReciboEquipo;
use App\Models\TarjetaVideo;
use App\Models\User;
use App\Models\UsuarioExterno;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Datos ficticios para las capturas de la guía de usuario (`guia:capturar`).
 *
 * Corre SOLO sobre la base de demostración: se niega a sembrar cualquier
 * otra. Nombres, equipos y fechas son inventados y fijos, para que las
 * capturas no muestren datos reales y salgan iguales cada vez.
 */
class GuiaDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public const BASE_DE_DATOS = 'guia_demo';

    public const EMAIL_ADMIN = 'guia.demo@example.com';

    public const PASSWORD_ADMIN = 'guia-demo-2026';

    /**
     * "Hoy" de la base de demostración: todas las fechas parten de aquí.
     */
    private const HOY = '2026-06-15';

    public function run(): void
    {
        if (config('database.connections.'.config('database.default').'.database') !== self::BASE_DE_DATOS) {
            throw new RuntimeException('GuiaDemoSeeder solo corre sobre la base ['.self::BASE_DE_DATOS.'].');
        }

        fake()->seed(20260615);

        $cargos = $this->areasYCargos();
        $admin = $this->administrador($cargos['Oficial de Infraestructura']);

        $this->call(RolesPermissionsSeeder::class);
        $admin->assignRole(config('filament-shield.super_admin.name', 'super_admin'));

        $usuarios = $this->usuarios($cargos);
        $this->sembrarMovimientosDeUsuarios($usuarios);
        $catalogo = $this->sembrarInventario();
        $this->equipos($usuarios, $catalogo);
        $this->sembrarRecibosDeEquipos();
        $this->sembrarAsignacionDeEquipos();
        $this->sembrarCelulares($usuarios);
    }

    /**
     * Catálogo de componentes (modelos, micros, RAM, GPU) para el capítulo Inventario
     * y para armar los equipos de demostración.
     *
     * @return array{modelos: array<string, int|string>, micros: array<string, int|string>, rams: array<string, int|string>, gpu: int|string}
     */
    private function sembrarInventario(): array
    {
        $modelos = collect(['Latitude 5440', 'Latitude 7450', 'ThinkPad T14', 'EliteBook 840'])
            ->mapWithKeys(fn (string $nombre): array => [$nombre => ModeloLaptop::query()->create(['nombre' => $nombre])->getKey()]);
        $micros = collect(['Intel Core i5-1345U', 'Intel Core i7-1365U'])
            ->mapWithKeys(fn (string $nombre): array => [$nombre => Microprocesador::query()->create(['nombre_micro' => $nombre])->getKey()]);
        $rams = collect(['16 GB DDR4', '32 GB DDR5'])
            ->mapWithKeys(fn (string $nombre): array => [$nombre => MemoriaRam::query()->create(['tipo_ram' => $nombre])->getKey()]);
        $gpu = TarjetaVideo::query()->create(['nombre_gpu' => 'Intel Iris Xe'])->getKey();

        return [
            'modelos' => $modelos->all(),
            'micros' => $micros->all(),
            'rams' => $rams->all(),
            'gpu' => $gpu,
        ];
    }

    /**
     * @return array<string, Cargo> cargo => modelo
     */
    private function areasYCargos(): array
    {
        $estructura = [
            'Tecnología de la Información' => ['Oficial de Infraestructura', 'Analista de Soporte'],
            'Administración y Finanzas' => ['Contador General', 'Asistente Administrativo'],
            'Inversiones' => ['Analista de Inversiones', 'Gerente de Inversiones'],
            'Auditoría Interna' => ['Auditor Senior'],
        ];

        $cargos = [];

        foreach ($estructura as $nombreArea => $nombresDeCargo) {
            $area = Area::query()->create(['area' => $nombreArea, 'descripcion' => "Área de {$nombreArea}."]);

            foreach ($nombresDeCargo as $nombre) {
                $cargos[$nombre] = Cargo::query()->create([
                    'area_id' => $area->id_area,
                    'cargo' => $nombre,
                    'descripcion' => null,
                ]);
            }
        }

        return $cargos;
    }

    private function administrador(Cargo $cargo): User
    {
        return User::factory()->create([
            'name' => 'Administrador Demo',
            'nombres' => 'Administrador',
            'apellidos' => 'Demo',
            'carnet' => '1000',
            'telefono' => '70000000',
            'email' => self::EMAIL_ADMIN,
            'password' => self::PASSWORD_ADMIN,
            'id_area' => $cargo->area_id,
            'id_cargo' => $cargo->id_cargo,
            'acceso_habilitado' => true,
            'requiere_2fa' => false,
        ]);
    }

    /**
     * @param  array<string, Cargo>  $cargos
     * @return array<string, User> nombre => modelo
     */
    private function usuarios(array $cargos): array
    {
        $personas = [
            ['Lucía Fernández Rojas', 'Lucía', 'Fernández Rojas', 'Analista de Soporte'],
            ['Martín Quiroga Paz', 'Martín', 'Quiroga Paz', 'Contador General'],
            ['Valeria Montaño Ríos', 'Valeria', 'Montaño Ríos', 'Asistente Administrativo'],
            ['Diego Salvatierra Luna', 'Diego', 'Salvatierra Luna', 'Analista de Inversiones'],
            ['Camila Arce Villarroel', 'Camila', 'Arce Villarroel', 'Gerente de Inversiones'],
            ['Andrés Peredo Suárez', 'Andrés', 'Peredo Suárez', 'Auditor Senior'],
        ];

        $usuarios = [];

        foreach ($personas as $indice => [$nombre, $nombres, $apellidos, $cargo]) {
            $usuarios[$nombre] = User::factory()->create([
                'name' => $nombre,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'carnet' => (string) (1001 + $indice),
                'email' => 'usuario'.($indice + 1).'@example.com',
                'telefono' => '7000000'.($indice + 1),
                'id_area' => $cargos[$cargo]->area_id,
                'id_cargo' => $cargos[$cargo]->id_cargo,
                'acceso_habilitado' => true,
            ]);
        }

        return $usuarios;
    }

    /**
     * Altas de demostración para el capítulo Usuarios (listado de movimientos).
     *
     * @param  array<string, User>  $usuarios
     */
    private function sembrarMovimientosDeUsuarios(array $usuarios): void
    {
        $hoy = Carbon::parse(self::HOY);

        foreach (array_values($usuarios) as $indice => $usuario) {
            Movimiento::query()->create([
                'usuario_id' => $usuario->getKey(),
                'tipo' => 'Alta',
                'fecha' => $hoy->copy()->subMonths(8 - min($indice, 6)),
                'cargo_id' => $usuario->id_cargo,
            ]);
        }
    }

    /**
     * @param  array<string, User>  $usuarios
     * @param  array{modelos: array<string, int|string>, micros: array<string, int|string>, rams: array<string, int|string>, gpu: int|string}  $catalogo
     */
    private function equipos(array $usuarios, array $catalogo): void
    {
        $modelos = $catalogo['modelos'];
        $micros = $catalogo['micros'];
        $rams = $catalogo['rams'];
        $gpu = $catalogo['gpu'];

        $hoy = Carbon::parse(self::HOY);

        // service tag, modelo, micro, RAM, estado, asignado a, meses de garantía restantes
        $equipos = [
            ['DM4K7P2', 'Latitude 5440', 'Intel Core i5-1345U', '16 GB DDR4', EstadoEquipo::Asignado, 'Lucía Fernández Rojas', 14],
            ['DM9T3L8', 'Latitude 5440', 'Intel Core i5-1345U', '16 GB DDR4', EstadoEquipo::Asignado, 'Martín Quiroga Paz', 2],
            ['DM2H6R5', 'Latitude 7450', 'Intel Core i7-1365U', '32 GB DDR5', EstadoEquipo::Asignado, 'Camila Arce Villarroel', 20],
            ['TP5N8W1', 'ThinkPad T14', 'Intel Core i5-1345U', '16 GB DDR4', EstadoEquipo::Asignado, 'Diego Salvatierra Luna', 1],
            ['TP7C2X9', 'ThinkPad T14', 'Intel Core i7-1365U', '32 GB DDR5', EstadoEquipo::Asignado, 'Andrés Peredo Suárez', 9],
            ['EB3V6M4', 'EliteBook 840', 'Intel Core i5-1345U', '16 GB DDR4', EstadoEquipo::SinAsignar, null, 22],
            ['EB8J1Q7', 'EliteBook 840', 'Intel Core i5-1345U', '16 GB DDR4', EstadoEquipo::SinAsignar, null, 22],
            ['DM6F4Z3', 'Latitude 7450', 'Intel Core i7-1365U', '32 GB DDR5', EstadoEquipo::SinAsignar, null, 18],
            ['TP1B9K6', 'ThinkPad T14', 'Intel Core i5-1345U', '16 GB DDR4', EstadoEquipo::EnMantenimiento, null, 5],
            ['DM5S2Y8', 'Latitude 5440', 'Intel Core i5-1345U', '16 GB DDR4', EstadoEquipo::Resguardo, null, -3],
            ['EB4G7D2', 'EliteBook 840', 'Intel Core i5-1345U', '16 GB DDR4', EstadoEquipo::BajaTemporal, null, -8],
            ['TP3R5H1', 'ThinkPad T14', 'Intel Core i5-1345U', '16 GB DDR4', EstadoEquipo::BajaDefinitiva, null, -20],
        ];

        foreach ($equipos as $indice => [$serviceTag, $modelo, $micro, $ram, $estado, $asignadoA, $mesesDeGarantia]) {
            $vencimiento = $hoy->copy()->addMonths($mesesDeGarantia);

            Equipo::query()->create([
                'service_tag' => $serviceTag,
                'modelo_id' => $modelos[$modelo],
                'microprocesador_id' => $micros[$micro],
                'ram_id' => $rams[$ram],
                'gpu_id' => $gpu,
                'disco_duro' => '512GB SSD',
                'proveedor' => 'Proveedor Demo S.R.L.',
                'inicio_garantia' => $vencimiento->copy()->subYears(3),
                'venc_garantia' => $vencimiento,
                'nombre_equipo' => 'BOCS-'.str_pad((string) ($indice + 1), 3, '0', STR_PAD_LEFT),
                'codigo_activo' => 'ACT-'.str_pad((string) ($indice + 101), 4, '0', STR_PAD_LEFT),
                'so' => 'Windows 11 PRO',
                'licencia_so' => null,
                'bloqueo_bios' => true,
                'etiquetado' => $indice % 4 !== 3,
                'usuario_admin' => false,
                'litigation_hold' => false,
                'bitlocker' => $indice % 3 === 0,
                'estado' => $estado,
                'observaciones' => null,
            ]);

            if ($asignadoA !== null) {
                AsignacionEquipo::query()->create([
                    'service_tag' => $serviceTag,
                    'usuario_id' => $usuarios[$asignadoA]->getKey(),
                    'fecha_inicio' => $hoy->copy()->subMonths(6 + $indice),
                    'fecha_fin' => null,
                    'motivo' => 'Asignación inicial',
                    'observaciones' => null,
                ]);
            }
        }
    }

    /**
     * Recibos PDF ficticios para el capítulo Equipos (pestaña Recibos de equipos).
     */
    private function sembrarRecibosDeEquipos(): void
    {
        $hoy = Carbon::parse(self::HOY);
        $pdfMinimo = "%PDF-1.1\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";
        $disco = ReciboEquipo::documentDisk();
        $directorio = ReciboEquipo::documentDirectory();

        $recibos = [
            ['DM4K7P2', $hoy->copy()->subMonths(5), 'Entrega inicial a Lucía Fernández Rojas.'],
            ['DM9T3L8', $hoy->copy()->subMonths(4), 'Conformidad de recepción del equipo.'],
            ['DM2H6R5', $hoy->copy()->subMonths(3), null],
            ['TP5N8W1', $hoy->copy()->subMonths(2), 'Recibo firmado en papel y digitalizado.'],
        ];

        foreach ($recibos as [$serviceTag, $fecha, $observaciones]) {
            $ruta = $directorio.'/'.$serviceTag.'_'.$fecha->format('Y-m-d').'.pdf';
            Storage::disk($disco)->put($ruta, $pdfMinimo);

            ReciboEquipo::query()->create([
                'equipo_id' => $serviceTag,
                'fecha' => $fecha,
                'url' => $ruta,
                'observaciones' => $observaciones,
            ]);
        }
    }

    /**
     * Cargadores, entregas, recepciones, notas y mantenimiento para el capítulo
     * Asignación de equipos. Usa los equipos ya creados en equipos().
     */
    private function sembrarAsignacionDeEquipos(): void
    {
        $hoy = Carbon::parse(self::HOY);

        $cargadores = [
            ['CHG-DEMO-001', 'Dell', '65W USB-C', EstadoCargador::Asignado],
            ['CHG-DEMO-002', 'Lenovo', '65W USB-C', EstadoCargador::Asignado],
            ['CHG-DEMO-003', 'HP', '65W USB-C', EstadoCargador::Disponible],
            ['CHG-DEMO-004', 'Dell', '90W USB-C', EstadoCargador::Disponible],
        ];

        foreach ($cargadores as [$serial, $marca, $modelo, $estado]) {
            Cargador::query()->create([
                'serial' => $serial,
                'marca' => $marca,
                'modelo' => $modelo,
                'estado' => $estado,
                'observaciones' => null,
            ]);
        }

        AsignacionCargador::query()->create([
            'serial_cargador' => 'CHG-DEMO-001',
            'service_tag' => 'DM4K7P2',
            'service_tag_origen' => null,
            'fecha_inicio' => $hoy->copy()->subMonths(5),
            'fecha_fin' => null,
            'observaciones' => null,
        ]);

        AsignacionCargador::query()->create([
            'serial_cargador' => 'CHG-DEMO-002',
            'service_tag' => 'DM9T3L8',
            'service_tag_origen' => null,
            'fecha_inicio' => $hoy->copy()->subMonths(4),
            'fecha_fin' => null,
            'observaciones' => null,
        ]);

        // Equipo Sin asignar con cargador: sirve para «Usar cargador de otro equipo».
        Cargador::query()->create([
            'serial' => 'CHG-DEMO-005',
            'marca' => 'Dell',
            'modelo' => '65W USB-C',
            'estado' => EstadoCargador::Asignado,
            'observaciones' => null,
        ]);

        AsignacionCargador::query()->create([
            'serial_cargador' => 'CHG-DEMO-005',
            'service_tag' => 'DM6F4Z3',
            'service_tag_origen' => null,
            'fecha_inicio' => $hoy->copy()->subMonths(1),
            'fecha_fin' => null,
            'observaciones' => null,
        ]);

        $asignacionLucia = AsignacionEquipo::query()
            ->where('service_tag', 'DM4K7P2')
            ->whereNull('fecha_fin')
            ->firstOrFail();

        EntregaEquipo::query()->create([
            'asignacion_id' => $asignacionLucia->getKey(),
            'numero' => '001-2026',
            'fecha' => $hoy->copy()->subMonths(5)->addDays(2),
            'mouse' => true,
            'cooler' => false,
            'audifonos' => false,
            'observaciones' => null,
            'nota' => null,
        ]);

        $asignacionCerrada = AsignacionEquipo::query()->create([
            'service_tag' => 'EB3V6M4',
            'usuario_id' => User::query()->where('email', '!=', self::EMAIL_ADMIN)->value('id'),
            'fecha_inicio' => $hoy->copy()->subMonths(10),
            'fecha_fin' => $hoy->copy()->subMonths(2),
            'motivo' => 'Préstamo temporal',
            'observaciones' => null,
        ]);

        // El equipo debe seguir Sin asignar (ya lo está en equipos()); no tocamos estado.
        EntregaEquipo::query()->create([
            'asignacion_id' => $asignacionCerrada->getKey(),
            'numero' => '002-2026',
            'fecha' => $hoy->copy()->subMonths(10)->addDay(),
            'mouse' => false,
            'cooler' => false,
            'audifonos' => false,
            'observaciones' => null,
            'nota' => null,
        ]);

        RecepcionEquipo::query()->create([
            'asignacion_id' => $asignacionCerrada->getKey(),
            'numero' => '001-2026',
            'fecha' => $hoy->copy()->subMonths(2),
            'observaciones' => null,
        ]);

        $externo = UsuarioExterno::query()->create([
            'nombre' => 'Patricia Mendoza Cruz',
            'carnet' => '7894561',
            'empresa' => 'Consultora Andina S.R.L.',
            'cargo' => 'Auditora',
        ]);

        $externo2 = UsuarioExterno::query()->create([
            'nombre' => 'Jorge Ríos Almanza',
            'carnet' => '6543210',
            'empresa' => 'Servicios del Altiplano',
            'cargo' => 'Técnico',
        ]);

        foreach ([
            ['EB8J1Q7', $externo, '001-2026', 40, 'Salida temporal para auditoría.'],
            ['EB3V6M4', $externo, '002-2026', 35, 'Préstamo corto a consultora.'],
            ['DM6F4Z3', $externo2, '003-2026', 28, 'Revisión externa de hardware.'],
            ['EB8J1Q7', $externo2, '004-2026', 18, 'Segunda visita de soporte.'],
            ['EB3V6M4', $externo2, '005-2026', 12, 'Calibración en taller externo.'],
        ] as [$equipoId, $quien, $numero, $diasAtras, $nota]) {
            NotaSalidaEquipo::query()->create([
                'equipo_id' => $equipoId,
                'usuario_externo_id' => $quien->getKey(),
                'numero' => $numero,
                'fecha' => $hoy->copy()->subDays($diasAtras),
                'nota' => $nota,
            ]);
        }

        foreach ([
            ['EB8J1Q7', $externo, '001-2026', 5, 'Equipo devuelto sin observaciones.'],
            ['EB3V6M4', $externo, '002-2026', 8, 'Devuelto con funda.'],
            ['DM6F4Z3', $externo2, '003-2026', 10, 'Revisión concluida.'],
            ['EB8J1Q7', $externo2, '004-2026', 3, 'Sin novedades.'],
        ] as [$equipoId, $quien, $numero, $diasAtras, $nota]) {
            NotaRecepcionEquipo::query()->create([
                'equipo_id' => $equipoId,
                'usuario_externo_id' => $quien->getKey(),
                'numero' => $numero,
                'fecha' => $hoy->copy()->subDays($diasAtras),
                'nota' => $nota,
            ]);
        }

        MantenimientoEquipo::query()->create([
            'service_tag' => 'TP1B9K6',
            'fecha_soporte' => $hoy->copy()->subDays(7),
            'observaciones' => 'Revisión de ventilación y actualización de drivers.',
        ]);
    }

    /**
     * Celulares, cargadores, asignaciones, entrega y recepción para el capítulo Celulares.
     *
     * @param  array<string, User>  $usuarios
     */
    private function sembrarCelulares(array $usuarios): void
    {
        $hoy = Carbon::parse(self::HOY);

        $celulares = [
            ['CEL-DEMO-01', 'Samsung', 'Galaxy A54', '356789012345001'],
            ['CEL-DEMO-02', 'Samsung', 'Galaxy A54', '356789012345002'],
            ['CEL-DEMO-03', 'Xiaomi', 'Redmi Note 13', '356789012345003'],
            ['CEL-DEMO-04', 'Apple', 'iPhone 13', '356789012345004'],
        ];

        foreach ($celulares as [$id, $marca, $modelo, $imei]) {
            Celular::query()->create([
                'id' => $id,
                'marca' => $marca,
                'modelo' => $modelo,
                'imei' => $imei,
                'fecha_compra' => $hoy->copy()->subYears(1),
                'precio' => 2500,
                'procesador' => 'Octa-core',
                'disco' => '128 GB',
                'ram' => '6 GB',
                'bateria' => '5000 mAh',
                'color' => 'Negro',
            ]);
        }

        foreach ([
            ['CHG-CEL-001', 'Cargador USB-C 25W'],
            ['CHG-CEL-002', 'Cargador USB-C 25W'],
            ['CHG-CEL-003', 'Cargador Lightning 20W'],
        ] as [$serial, $modelo]) {
            CargadorCel::query()->create([
                'serial' => $serial,
                'modelo' => $modelo,
                'observacion' => null,
            ]);
        }

        $asignacionLucia = AsigCelular::query()->create([
            'celular_id' => 'CEL-DEMO-01',
            'usuario_id' => $usuarios['Lucía Fernández Rojas']->getKey(),
            'fecha_ini' => $hoy->copy()->subMonths(4),
            'fecha_fin' => null,
        ]);

        AsigCelular::query()->create([
            'celular_id' => 'CEL-DEMO-02',
            'usuario_id' => $usuarios['Martín Quiroga Paz']->getKey(),
            'fecha_ini' => $hoy->copy()->subMonths(3),
            'fecha_fin' => null,
        ]);

        $asignacionCerrada = AsigCelular::query()->create([
            'celular_id' => 'CEL-DEMO-03',
            'usuario_id' => $usuarios['Camila Arce Villarroel']->getKey(),
            'fecha_ini' => $hoy->copy()->subMonths(8),
            'fecha_fin' => $hoy->copy()->subMonths(1),
        ]);

        AsigCargadorCel::query()->create([
            'celulares_id' => 'CEL-DEMO-01',
            'cargador_serial' => 'CHG-CEL-001',
            'fecha' => $hoy->copy()->subMonths(4),
            'motivo' => 'Entrega junto con el celular',
        ]);

        EntregaCelular::query()->create([
            'asignacion_cel_id' => $asignacionLucia->getKey(),
            'numero' => '001-2026',
            'fecha' => $hoy->copy()->subMonths(4)->addDay(),
            'observaciones' => null,
            'nota' => null,
        ]);

        EntregaCelular::query()->create([
            'asignacion_cel_id' => $asignacionCerrada->getKey(),
            'numero' => '002-2026',
            'fecha' => $hoy->copy()->subMonths(8)->addDay(),
            'observaciones' => null,
            'nota' => null,
        ]);

        RecepcionCelular::query()->create([
            'asignacion_cel_id' => $asignacionCerrada->getKey(),
            'numero' => '001-2026',
            'fecha' => $hoy->copy()->subMonths(1),
            'nota' => 'Devuelto en buen estado.',
        ]);
    }
}
