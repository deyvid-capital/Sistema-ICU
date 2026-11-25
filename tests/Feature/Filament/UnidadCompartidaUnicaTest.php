<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AccUdCompartidas\Pages\CreateAccUdCompartida;
use App\Filament\Resources\AccUdCompartidas\Pages\EditAccUdCompartida;
use App\Filament\Resources\Users\Pages\ManageUserAccesos;
use App\Filament\Resources\Users\RelationManagers\UnidadesCompartidasRelationManager;
use App\Models\AccUdCompartida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Una misma unidad compartida no se asigna dos veces al mismo usuario.
 */
class UnidadCompartidaUnicaTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE = 'Este usuario ya tiene asignada esta unidad compartida.';

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_no_se_asigna_dos_veces_la_misma_unidad_al_mismo_usuario(): void
    {
        $usuario = User::factory()->create();
        $this->unidad($usuario, 'Daza Producción (P)');

        Livewire::test(CreateAccUdCompartida::class)
            ->fillForm([
                'usuario_id' => $usuario->getKey(),
                'unidad_compartida' => 'Daza Producción (P)',
                'estado' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['unidad_compartida' => [self::MENSAJE]]);

        $this->assertSame(1, AccUdCompartida::query()->where('usuario_id', $usuario->getKey())->count());
    }

    public function test_el_mismo_usuario_puede_tener_otra_unidad_y_otro_usuario_la_misma(): void
    {
        $usuario = User::factory()->create();
        $this->unidad($usuario, 'Daza Producción (P)');

        Livewire::test(CreateAccUdCompartida::class)
            ->fillForm(['usuario_id' => $usuario->getKey(), 'unidad_compartida' => 'Daza Test (T)', 'estado' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateAccUdCompartida::class)
            ->fillForm(['usuario_id' => User::factory()->create()->getKey(), 'unidad_compartida' => 'Daza Producción (P)', 'estado' => true])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_editar_una_unidad_no_choca_consigo_misma(): void
    {
        $unidad = $this->unidad(User::factory()->create(), 'Daza Contingencia (Q)');

        Livewire::test(EditAccUdCompartida::class, ['record' => $unidad->getKey()])
            ->fillForm(['estado' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse((bool) $unidad->fresh()->estado);
    }

    public function test_desde_la_ficha_del_usuario_tambien_se_impide_el_duplicado(): void
    {
        $usuario = User::factory()->create();
        $this->unidad($usuario, 'Daza Producción (P)');

        Livewire::test(UnidadesCompartidasRelationManager::class, [
            'ownerRecord' => $usuario,
            'pageClass' => ManageUserAccesos::class,
        ])
            ->callTableAction('create', data: [
                'unidad_compartida' => 'Daza Producción (P)',
                'estado' => true,
            ])
            ->assertHasTableActionErrors(['unidad_compartida' => [self::MENSAJE]]);

        $this->assertSame(1, AccUdCompartida::query()->where('usuario_id', $usuario->getKey())->count());
    }

    private function unidad(User $usuario, string $nombre): AccUdCompartida
    {
        return AccUdCompartida::query()->create([
            'usuario_id' => $usuario->getKey(),
            'unidad_compartida' => $nombre,
            'estado' => true,
        ]);
    }
}
