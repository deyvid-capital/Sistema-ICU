<?php

namespace Tests\Feature\Auditoria;

use App\Enums\DominioActividad;
use App\Enums\SeccionConfiguracion;
use App\Filament\Clusters\Configuracion\Pages\Archivos;
use App\Filament\Clusters\Configuracion\Pages\Correo;
use App\Models\User;
use CapitalSafi\Apariencia\Events\AparienciaActualizada;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /**
     * @param  array<string, mixed>  $antes
     * @param  array<string, mixed>  $despues
     * @param  array{subidos: array<string, string>, eliminados: array<string, string>}  $archivos
     */
    private function lanzarApariencia(
        array $antes = [],
        array $despues = [],
        array $archivos = ['subidos' => [], 'eliminados' => []],
        string $alcance = 'global',
        string $accion = 'guardado',
    ): void {
        AparienciaActualizada::dispatch($alcance, $antes, $despues, $archivos, $accion);
    }

    public function test_guardar_apariencia_registra_solo_los_campos_cambiados(): void
    {
        $this->lanzarApariencia(
            antes: ['primary' => '#003973', 'success' => '#13A538'],
            despues: ['primary' => '#112233', 'success' => '#13A538'],
        );

        $registros = $this->registros(SeccionConfiguracion::Apariencia);

        $this->assertCount(1, $registros);

        $registro = $registros->first();

        $this->assertSame('updated', $registro->event);
        $this->assertSame('Apariencia actualizada', $registro->description);
        $this->assertSame(['primary' => '#112233'], $registro->attribute_changes['attributes']);
        $this->assertSame(['primary' => '#003973'], $registro->attribute_changes['old']);
        $this->assertSame((string) $this->usuario->id, (string) $registro->causer_id);
    }

    public function test_guardar_apariencia_sin_cambios_no_registra_nada(): void
    {
        $this->lanzarApariencia(
            antes: ['primary' => '#003973'],
            despues: ['primary' => '#003973'],
        );

        $this->assertCount(0, $this->registros());
    }

    public function test_restablecer_apariencia_se_registra_como_restored(): void
    {
        $this->lanzarApariencia(
            antes: ['primary' => '#112233'],
            despues: ['primary' => '#003973'],
            accion: 'restablecido',
        );

        $registros = $this->registros(SeccionConfiguracion::Apariencia);

        $this->assertCount(1, $registros);
        $this->assertSame('restored', $registros->first()->event);
        $this->assertSame('Apariencia restaurada', $registros->first()->description);
        $this->assertSame('#112233', $registros->first()->attribute_changes['old']['primary']);
    }

    public function test_subir_logo_registra_el_archivo_subido(): void
    {
        $this->lanzarApariencia(
            antes: ['logo_height' => '2.5rem'],
            despues: ['logo_height' => '3rem'],
            archivos: ['subidos' => ['logo_light' => 'apariencia/marca/logo-01ABC.webp'], 'eliminados' => []],
        );

        $registros = $this->registros(SeccionConfiguracion::Apariencia);

        $this->assertCount(1, $registros);

        $propiedades = $registros->first()->properties;

        $this->assertSame('logo-01ABC.webp', $propiedades['archivos']['subidos']['logo_light']);
        $this->assertArrayNotHasKey('eliminados', $propiedades['archivos']);
        $this->assertSame('3rem', $registros->first()->attribute_changes['attributes']['logo_height']);
    }

    public function test_quitar_logo_registra_el_archivo_eliminado_aunque_no_cambien_otros_ajustes(): void
    {
        $this->lanzarApariencia(
            archivos: ['subidos' => [], 'eliminados' => ['logo_light' => 'apariencia/marca/logo-01ABC.webp']],
        );

        $registros = $this->registros(SeccionConfiguracion::Apariencia);

        $this->assertCount(1, $registros);
        $this->assertSame('logo-01ABC.webp', $registros->first()->properties['archivos']['eliminados']['logo_light']);
        $this->assertArrayNotHasKey('subidos', $registros->first()->properties['archivos']);
    }

    public function test_la_apariencia_personal_no_se_audita(): void
    {
        $this->lanzarApariencia(
            antes: ['primary' => '#003973'],
            despues: ['primary' => '#112233'],
            alcance: 'personal',
        );

        $this->assertCount(0, $this->registros());
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

    public function test_las_paginas_y_la_apariencia_usan_el_dominio_de_configuracion(): void
    {
        $this->lanzarApariencia(antes: ['primary' => '#003973'], despues: ['primary' => '#112233']);
        Livewire::test(Archivos::class)->fillForm(['prefijo' => 'otro'])->call('guardar');
        Livewire::test(Correo::class)->fillForm(['host' => 'smtp.example.org'])->call('guardar');

        $secciones = $this->registros()
            ->map(fn (Activity $registro): string => $registro->properties['seccion'])
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            ['apariencia', 'archivos', 'correo'],
            $secciones,
        );
    }

    public function test_las_secciones_retiradas_siguen_disponibles_para_filas_antiguas(): void
    {
        $this->assertSame('Tema y colores', SeccionConfiguracion::from('tema_colores')->getLabel());
        $this->assertSame('Marca e identidad', SeccionConfiguracion::from('marca')->getLabel());
    }
}
