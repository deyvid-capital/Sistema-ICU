<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AsigCargadorCels\Pages\CreateAsigCargadorCel;
use App\Filament\Resources\AsigCargadorCels\Pages\EditAsigCargadorCel;
use App\Filament\Resources\AsigCelulars\Pages\CreateAsigCelular;
use App\Filament\Resources\AsigCelulars\Pages\EditAsigCelular;
use App\Models\AsigCargadorCel;
use App\Models\AsigCelular;
use App\Models\CargadorCel;
use App\Models\Celular;
use App\Models\User;
use App\Rules\CargadorCelSinAsignar;
use App\Rules\CelularDisponibleParaAsignacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Un celular no admite dos asignaciones activas y un cargador de celular
 * solo puede estar en un celular a la vez.
 */
class CelularAsignacionUnicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_no_se_asigna_un_celular_que_ya_tiene_una_asignacion_activa(): void
    {
        $activa = AsigCelular::factory()->create(['fecha_fin' => null]);

        Livewire::test(CreateAsigCelular::class)
            ->fillForm([
                'celular_id' => $activa->celular_id,
                'usuario_id' => User::factory()->create()->getKey(),
                'fecha_ini' => '2026-06-15',
            ])
            ->call('create')
            ->assertHasFormErrors(['celular_id'])
            ->assertNotified('No se puede asignar el celular');

        $this->assertSame(1, AsigCelular::query()->where('celular_id', $activa->celular_id)->count());
    }

    public function test_se_asigna_un_celular_cuya_asignacion_anterior_ya_se_cerro(): void
    {
        $cerrada = AsigCelular::factory()->create(['fecha_ini' => '2026-01-10', 'fecha_fin' => '2026-03-10']);

        Livewire::test(CreateAsigCelular::class)
            ->fillForm([
                'celular_id' => $cerrada->celular_id,
                'usuario_id' => User::factory()->create()->getKey(),
                'fecha_ini' => '2026-06-15',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(2, AsigCelular::query()->where('celular_id', $cerrada->celular_id)->count());
    }

    public function test_se_puede_registrar_una_asignacion_ya_cerrada_aunque_haya_otra_activa(): void
    {
        $activa = AsigCelular::factory()->create(['fecha_fin' => null]);

        Livewire::test(CreateAsigCelular::class)
            ->fillForm([
                'celular_id' => $activa->celular_id,
                'usuario_id' => User::factory()->create()->getKey(),
                'fecha_ini' => '2025-01-10',
                'fecha_fin' => '2025-03-10',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_una_asignacion_eliminada_no_bloquea_el_celular(): void
    {
        $eliminada = AsigCelular::factory()->create(['fecha_fin' => null]);
        $eliminada->delete();

        Livewire::test(CreateAsigCelular::class)
            ->fillForm([
                'celular_id' => $eliminada->celular_id,
                'usuario_id' => User::factory()->create()->getKey(),
                'fecha_ini' => '2026-06-15',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_editar_la_asignacion_activa_no_choca_consigo_misma_pero_no_puede_tomar_un_celular_ocupado(): void
    {
        $propia = AsigCelular::factory()->create(['fecha_ini' => '2026-01-10', 'fecha_fin' => null]);
        $ajena = AsigCelular::factory()->create(['fecha_fin' => null]);

        Livewire::test(EditAsigCelular::class, ['record' => $propia->getKey()])
            ->fillForm(['fecha_ini' => '2026-02-01'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(EditAsigCelular::class, ['record' => $propia->getKey()])
            ->fillForm(['celular_id' => $ajena->celular_id])
            ->call('save')
            ->assertHasFormErrors(['celular_id'])
            ->assertNotified('No se puede asignar el celular');

        $this->assertSame($propia->celular_id, $propia->fresh()->celular_id);
    }

    public function test_el_formulario_solo_ofrece_cargadores_de_celular_sin_asignar(): void
    {
        $ocupado = AsigCargadorCel::factory()->create()->cargador;
        $libre = CargadorCel::factory()->create();
        $liberado = AsigCargadorCel::factory()->create();
        $liberado->delete();

        Livewire::test(CreateAsigCargadorCel::class)
            ->assertFormFieldExists('cargador_serial', function ($campo) use ($ocupado, $libre, $liberado): bool {
                $opciones = array_keys($campo->getOptions());

                return in_array($libre->serial, $opciones, true)
                    && in_array($liberado->cargador_serial, $opciones, true)
                    && ! in_array($ocupado->serial, $opciones, true);
            });
    }

    public function test_no_se_asigna_un_cargador_de_celular_que_ya_esta_en_otro_celular(): void
    {
        $ocupado = AsigCargadorCel::factory()->create();

        Livewire::test(CreateAsigCargadorCel::class)
            ->fillForm([
                'celulares_id' => Celular::factory()->create()->getKey(),
                'cargador_serial' => $ocupado->cargador_serial,
                'fecha' => '2026-06-15',
            ])
            ->call('create')
            ->assertHasFormErrors(['cargador_serial']);

        $this->assertSame(1, AsigCargadorCel::query()->where('cargador_serial', $ocupado->cargador_serial)->count());
    }

    public function test_editar_una_asignacion_de_cargador_conserva_su_propio_cargador(): void
    {
        $asignacion = AsigCargadorCel::factory()->create(['motivo' => 'Original']);

        Livewire::test(EditAsigCargadorCel::class, ['record' => $asignacion->getKey()])
            ->assertFormFieldExists(
                'cargador_serial',
                fn ($campo): bool => array_key_exists($asignacion->cargador_serial, $campo->getOptions()),
            )
            ->fillForm(['motivo' => 'Corregido'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Corregido', $asignacion->fresh()->motivo);
    }

    public function test_los_mensajes_dicen_que_hacer(): void
    {
        $this->assertStringContainsString('Ciérrela antes de crear una nueva', CelularDisponibleParaAsignacion::MENSAJE);
        $this->assertStringContainsString('ya está asignado', CargadorCelSinAsignar::MENSAJE);
    }
}
