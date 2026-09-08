<?php

namespace Tests\Feature\Filament;

use App\Enums\EquipoFormularioPdfTipo;
use App\Enums\EstadoEquipo;
use App\Filament\Resources\AsignacionEquipos\AsignacionEquipoResource;
use App\Filament\Resources\AsignacionEquipos\Pages\CreateAsignacionEquipo;
use App\Filament\Resources\AsignacionEquipos\Pages\EditAsignacionEquipo;
use App\Jobs\GenerateEquipoFormularioPdfJob;
use App\Models\AsignacionEquipo;
use App\Models\EntregaEquipo;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class AsignacionEquipoPdfFormActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AsignacionEquipoResource::skipAuthorization();
        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_create_and_generate_pdf_dispatches_job_for_entrega(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);

        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);
        $usuario = User::factory()->create();

        Livewire::test(CreateAsignacionEquipo::class)
            ->fillForm([
                'usuario_id' => $usuario->id,
                'service_tag' => $equipo->service_tag,
                'fecha_inicio' => now()->toDateString(),
                'entrega' => [
                    'numero' => '020-2026',
                    'fecha' => now()->toDateString(),
                    'mouse' => false,
                    'cooler' => false,
                    'audifonos' => false,
                ],
            ])
            ->call('createAndGeneratePdf')
            ->assertHasNoFormErrors();

        $asignacion = AsignacionEquipo::query()
            ->where('service_tag', $equipo->service_tag)
            ->where('usuario_id', $usuario->id)
            ->first();

        $this->assertNotNull($asignacion);
        $this->assertNotNull($asignacion->entrega);

        Bus::assertDispatched(
            GenerateEquipoFormularioPdfJob::class,
            fn (GenerateEquipoFormularioPdfJob $job): bool => $job->record->is($asignacion->entrega)
                && $job->tipo === EquipoFormularioPdfTipo::Entrega,
        );
    }

    public function test_save_and_generate_pdf_dispatches_job_for_related_entrega(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);

        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $usuario = User::factory()->create();
        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $usuario->id,
            'fecha_fin' => null,
        ]);
        $entrega = EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => '021-2026',
        ]);

        Livewire::test(EditAsignacionEquipo::class, ['record' => $asignacion->getKey()])
            ->call('saveAndGeneratePdf')
            ->assertHasNoFormErrors()
            ->assertNotified();

        Bus::assertDispatched(
            GenerateEquipoFormularioPdfJob::class,
            fn (GenerateEquipoFormularioPdfJob $job): bool => $job->record->is($entrega)
                && $job->tipo === EquipoFormularioPdfTipo::Entrega,
        );
    }

    public function test_save_and_generate_pdf_works_for_closed_assignment_when_equipo_reassigned(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);

        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $usuarioAnterior = User::factory()->create();
        $usuarioActual = User::factory()->create();

        $asignacionAntigua = AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $usuarioAnterior->id,
            'fecha_inicio' => now()->subMonths(2)->toDateString(),
            'fecha_fin' => now()->subMonth()->toDateString(),
        ]);
        $entregaAntigua = EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacionAntigua->id,
            'numero' => '022-2026',
        ]);

        AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $usuarioActual->id,
            'fecha_inicio' => now()->subWeek()->toDateString(),
            'fecha_fin' => null,
        ]);

        Livewire::test(EditAsignacionEquipo::class, ['record' => $asignacionAntigua->getKey()])
            ->fillForm([
                'motivo' => 'Regenerar PDF histórico',
            ])
            ->call('saveAndGeneratePdf')
            ->assertHasNoFormErrors()
            ->assertNotified();

        Bus::assertDispatched(
            GenerateEquipoFormularioPdfJob::class,
            fn (GenerateEquipoFormularioPdfJob $job): bool => $job->record->is($entregaAntigua)
                && $job->tipo === EquipoFormularioPdfTipo::Entrega,
        );

        $this->assertSame(EstadoEquipo::Asignado, $equipo->fresh()->estado);
    }

    public function test_save_and_generate_pdf_validates_required_fields(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);

        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::Asignado]);
        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);
        EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => '023-2026',
        ]);

        Livewire::test(EditAsignacionEquipo::class, ['record' => $asignacion->getKey()])
            ->fillForm([
                'fecha_inicio' => null,
            ])
            ->call('saveAndGeneratePdf')
            ->assertHasFormErrors(['fecha_inicio' => 'required']);

        Bus::assertNotDispatched(GenerateEquipoFormularioPdfJob::class);
    }
}
