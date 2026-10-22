<?php

namespace Tests\Feature\Auditoria;

use App\Enums\DominioActividad;
use App\Enums\SeccionConfiguracion;
use App\Filament\Clusters\Configuracion\Pages\Archivos;
use App\Filament\Clusters\Configuracion\Pages\Correo;
use App\Filament\Clusters\Configuracion\Pages\Marca;
use App\Filament\Clusters\Configuracion\Pages\TemaColores;
use App\Models\AppearanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditoriaConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);
        Storage::fake('public');

        $this->usuario = User::factory()->create();
        $this->usuario->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

        $this->actingAs($this->usuario);
    }

    /**
     * @return Collection<int, Activity>
     */
    private function registros(?SeccionConfiguracion $seccion = null): Collection
    {
        return Activity::query()
            ->where('log_name', DominioActividad::Configuracion->value)
            ->orderBy('id')
            ->get()
            ->when(
                $seccion !== null,
                fn (Collection $registros): Collection => $registros->filter(
                    fn (Activity $registro): bool => $registro->properties['seccion'] === $seccion->value,
                )->values(),
            );
    }

    public function test_guardar_tema_registra_solo_los_campos_cambiados(): void
    {
        Livewire::test(TemaColores::class)
            ->fillForm(['tema' => 'personalizado', 'color_primario' => '#112233'])
            ->call('guardar');

        $registros = $this->registros(SeccionConfiguracion::TemaColores);

        $this->assertCount(1, $registros);

        $registro = $registros->first();

        $this->assertSame('updated', $registro->event);
        $this->assertSame('#112233', $registro->attribute_changes['attributes']['color_primario']);
        $this->assertSame((string) $this->usuario->id, (string) $registro->causer_id);
        $this->assertArrayNotHasKey('updated_at', $registro->attribute_changes['attributes']);
    }

    public function test_guardar_sin_cambios_no_registra_nada(): void
    {
        Livewire::test(TemaColores::class)->call('guardar');

        $this->assertCount(1, $this->registros(SeccionConfiguracion::TemaColores));

        Livewire::test(TemaColores::class)->call('guardar');

        // El segundo guardado no altera nada, así que no debe dejar rastro.
        $this->assertCount(1, $this->registros(SeccionConfiguracion::TemaColores));
    }

    public function test_restaurar_tema_se_registra_como_restored(): void
    {
        Livewire::test(TemaColores::class)
            ->fillForm(['tema' => 'personalizado', 'color_primario' => '#112233'])
            ->call('guardar');

        Livewire::test(TemaColores::class)->call('restaurar');

        $registros = $this->registros(SeccionConfiguracion::TemaColores);

        $this->assertCount(2, $registros);
        $this->assertSame('restored', $registros->last()->event);
        $this->assertSame('#112233', $registros->last()->attribute_changes['old']['color_primario']);
    }

    public function test_visitar_marca_no_genera_ningun_registro(): void
    {
        $this->get(Marca::getUrl(panel: 'admin'))->assertSuccessful();

        // mount() crea la fila appearance_settings en un GET: si el modelo
        // usara LogsIcuActivity, esto dejaría un "created" espurio.
        $this->assertTrue(AppearanceSetting::query()->exists());
        $this->assertCount(0, $this->registros());
    }

    public function test_subir_logo_registra_el_archivo_subido(): void
    {
        Livewire::test(Marca::class)
            ->fillForm([
                'logo_claro' => [UploadedFile::fake()->image('logo.png')],
                'alto_logo' => '3rem',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $registros = $this->registros(SeccionConfiguracion::Marca);

        $this->assertCount(1, $registros);

        $propiedades = $registros->first()->properties;

        $this->assertArrayHasKey('logo_claro', $propiedades['archivos']['subidos']);
        $this->assertArrayNotHasKey('eliminados', $propiedades['archivos']);
        $this->assertSame('3rem', $registros->first()->attribute_changes['attributes']['alto_logo']);
    }

    public function test_quitar_logo_registra_el_archivo_eliminado(): void
    {
        Livewire::test(Marca::class)
            ->fillForm(['logo_claro' => [UploadedFile::fake()->image('logo.png')]])
            ->call('guardar');

        Livewire::test(Marca::class)
            ->fillForm(['logo_claro' => []])
            ->call('guardar');

        $registros = $this->registros(SeccionConfiguracion::Marca);

        $this->assertCount(2, $registros);
        $this->assertArrayHasKey('logo_claro', $registros->last()->properties['archivos']['eliminados']);
    }

    public function test_restaurar_marca_registra_los_archivos_eliminados(): void
    {
        Livewire::test(Marca::class)
            ->fillForm(['logo_claro' => [UploadedFile::fake()->image('logo.png')]])
            ->call('guardar');

        Livewire::test(Marca::class)->call('restaurar');

        $registros = $this->registros(SeccionConfiguracion::Marca);

        $this->assertSame('restored', $registros->last()->event);
        $this->assertArrayHasKey('logo_claro', $registros->last()->properties['archivos']['eliminados']);
    }

    public function test_guardar_archivos_registra_los_cambios(): void
    {
        Livewire::test(Archivos::class)
            ->fillForm(['prefijo' => 'icu-nuevo'])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $registros = $this->registros(SeccionConfiguracion::Archivos);

        $this->assertCount(1, $registros);
        $this->assertSame('icu-nuevo', $registros->first()->attribute_changes['attributes']['prefijo']);
    }

    public function test_guardar_correo_registra_una_sola_fila_sin_exponer_la_contrasena(): void
    {
        Livewire::test(Correo::class)
            ->fillForm([
                'activo' => true,
                'mailer' => 'smtp',
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'buzon@example.com',
                'password' => 'SuperSecreta123',
                'from_address' => 'buzon@example.com',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $registros = $this->registros(SeccionConfiguracion::Correo);

        // Si quedara el activity() manual antiguo además del auditor, serían dos.
        $this->assertCount(1, $registros);

        $propiedades = $registros->first()->properties;
        $cambios = $registros->first()->attribute_changes;
        $serializado = json_encode([$propiedades->all(), $cambios->all()]);

        $this->assertTrue($propiedades['password_actualizada']);
        $this->assertArrayNotHasKey('password', $cambios['attributes']);
        $this->assertArrayNotHasKey('password', $cambios['old']);
        $this->assertStringNotContainsString('SuperSecreta123', $serializado);
        $this->assertSame('smtp.example.com', $cambios['attributes']['host']);
    }

    public function test_las_cuatro_paginas_usan_el_dominio_de_configuracion(): void
    {
        Livewire::test(TemaColores::class)->fillForm(['tema' => 'personalizado'])->call('guardar');
        Livewire::test(Marca::class)->fillForm(['alto_logo' => '4rem'])->call('guardar');
        Livewire::test(Archivos::class)->fillForm(['prefijo' => 'otro'])->call('guardar');
        Livewire::test(Correo::class)->fillForm(['host' => 'smtp.example.org'])->call('guardar');

        $secciones = $this->registros()
            ->map(fn (Activity $registro): string => $registro->properties['seccion'])
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            ['archivos', 'correo', 'marca', 'tema_colores'],
            $secciones,
        );
    }
}
