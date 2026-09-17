<?php

namespace Tests\Feature;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Models\Area;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Cargador;
use App\Models\Cargo;
use App\Models\Celular;
use App\Models\Equipo;
use App\Models\MantenimientoEquipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\ReciboEquipo;
use App\Models\Software;
use App\Models\TarjetaVideo;
use App\Models\User;
use Database\Seeders\CatalogoPersonalSeeder;
use Database\Seeders\EquiposSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvCatalogSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogo_personal_seeder_carga_areas_cargos_y_usuarios_del_csv(): void
    {
        $this->seed(CatalogoPersonalSeeder::class);

        $this->assertSame(11, Area::query()->count());
        $this->assertSame(39, Cargo::query()->count());
        $this->assertTrue(User::query()->where('email', 'deyvid@capitalsafi.com')->exists());

        $usuario = User::query()->where('email', 'deyvid@capitalsafi.com')->first();
        $this->assertSame('Deyvid Alberto', $usuario?->nombres);
        $this->assertSame('Paredes Ramos', $usuario?->apellidos);
        $this->assertSame('Tecnología de la Información', $usuario?->area?->area);
        $this->assertSame('Pasante IT', $usuario?->cargo?->cargo);
        $this->assertSame('Tecnología de la Información', $usuario?->cargo?->area?->area);
    }

    public function test_equipos_seeder_carga_catalogos_equipos_y_cargadores_sin_asignaciones(): void
    {
        $this->seed(EquiposSeeder::class);

        $this->assertSame(13, ModeloLaptop::query()->count());
        $this->assertSame(13, Microprocesador::query()->count());
        $this->assertSame(9, MemoriaRam::query()->count());
        $this->assertSame(11, TarjetaVideo::query()->count());
        $this->assertSame(49, Equipo::query()->count());
        $this->assertSame(49, Cargador::query()->count());

        $equipo = Equipo::query()->find('15F41X2');
        $this->assertNotNull($equipo);
        $this->assertSame('Latitude 5591', $equipo->modelo->nombre);
        $this->assertSame('i7 8850H (2.60Ghz - 6 Núcleos 12 Hilos)', $equipo->microprocesador->nombre_micro);
        $this->assertSame('16 Gb (DDR4 - 2667Mhz)', $equipo->ram->tipo_ram);
        $this->assertSame('Intel UHD Graphics 630 / Nvidia GeForce MX130', $equipo->gpu->nombre_gpu);
        $this->assertSame('SSD 1 TB', $equipo->disco_duro);
        $this->assertSame('Creativo', $equipo->proveedor);
        $this->assertSame('BOCSIB06', $equipo->nombre_equipo);
        $this->assertSame('7TTCH-NQH4X-9KRT3-WHCQ6-C9XTP', $equipo->licencia_so);
        $this->assertSame('2019-09-24', $equipo->inicio_garantia?->toDateString());
        $this->assertNull($equipo->venc_garantia);
        $this->assertSame(EstadoEquipo::SinAsignar, $equipo->estado);

        $surface = Equipo::query()->find('335311353');
        $this->assertSame('Surface Pro 7', $surface?->modelo->nombre);
        $this->assertSame('BOCS', $surface?->nombre_equipo);

        $cargadorDanado = Cargador::query()->find('CN-0WMDHR-CH200-14U-DD5D-A02');
        $this->assertSame(EstadoCargador::Dañado, $cargadorDanado?->estado);

        $cargadorLibre = Cargador::query()->find('CN-07MP1P-CH200-0C7-0CFD-A02');
        $this->assertSame(EstadoCargador::Disponible, $cargadorLibre?->estado);

        $this->assertSame(0, AsignacionEquipo::query()->count());
        $this->assertSame(0, AsignacionCargador::query()->count());
        $this->assertSame(0, ReciboEquipo::query()->count());
        $this->assertSame(0, MantenimientoEquipo::query()->count());
        $this->assertSame(0, Software::query()->count());
        $this->assertSame(0, Celular::query()->count());
    }
}
