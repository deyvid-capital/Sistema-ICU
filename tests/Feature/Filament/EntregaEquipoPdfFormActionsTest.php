<?php

namespace Tests\Feature\Filament;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\EntregaEquipos\EntregaEquipoResource;
use App\Filament\Resources\EntregaEquipos\Pages\CreateEntregaEquipo;
use App\Filament\Resources\EntregaEquipos\Pages\EditEntregaEquipo;
use App\Jobs\GenerateEquipoFormularioPdfJob;
use App\Models\AsignacionEquipo;
use App\Models\EntregaEquipo;
use App\Models\PdfEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EntregaEquipoPdfFormActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EntregaEquipoResource::skipAuthorization();
        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_create_and_generate_pdf_dispatches_job_after_creating(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);

        $asignacion = AsignacionEquipo::factory()->create();

        Livewire::test(CreateEntregaEquipo::class)
            ->fillForm([
                'asignacion_id' => $asignacion->id,
                'numero' => '010-2026',
                'fecha' => now()->toDateString(),
                'mouse' => false,
                'cooler' => false,
                'audifonos' => false,
            ])
            ->call('createAndGeneratePdf')
            ->assertHasNoFormErrors();

        $entrega = EntregaEquipo::query()->where('numero', '010-2026')->first();

        $this->assertNotNull($entrega);

        Bus::assertDispatched(
            GenerateEquipoFormularioPdfJob::class,
            fn (GenerateEquipoFormularioPdfJob $job): bool => $job->record->is($entrega)
                && $job->tipo === EquipoFormularioPdfTipo::Entrega,
        );
    }

    public function test_normal_create_does_not_dispatch_pdf_job(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);

        $asignacion = AsignacionEquipo::factory()->create();

        Livewire::test(CreateEntregaEquipo::class)
            ->fillForm([
                'asignacion_id' => $asignacion->id,
                'numero' => '011-2026',
                'fecha' => now()->toDateString(),
                'mouse' => false,
                'cooler' => false,
                'audifonos' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Bus::assertNotDispatched(GenerateEquipoFormularioPdfJob::class);
    }

    public function test_save_and_generate_pdf_dispatches_job_after_saving(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);

        $entrega = EntregaEquipo::factory()->create([
            'numero' => '012-2026',
            'mouse' => false,
        ]);

        Livewire::test(EditEntregaEquipo::class, ['record' => $entrega->getKey()])
            ->fillForm([
                'mouse' => true,
            ])
            ->call('saveAndGeneratePdf')
            ->assertHasNoFormErrors();

        $this->assertTrue($entrega->fresh()->mouse);

        Bus::assertDispatched(
            GenerateEquipoFormularioPdfJob::class,
            fn (GenerateEquipoFormularioPdfJob $job): bool => $job->record->is($entrega)
                && $job->tipo === EquipoFormularioPdfTipo::Entrega,
        );
    }

    public function test_save_and_generate_pdf_still_dispatches_when_documento_oficial_exists(): void
    {
        Bus::fake([GenerateEquipoFormularioPdfJob::class]);
        Storage::fake('public');

        $entrega = EntregaEquipo::factory()->create(['numero' => '013-2026']);
        $path = EquipoFormularioPdfTipo::Entrega->pdfRelativePath('013-2026');
        Storage::disk('public')->put($path, '%PDF-1.4 mock');

        PdfEquipo::factory()->create([
            'nombre' => EquipoFormularioPdfTipo::Entrega->nombreOficial(),
            'documento_id' => $entrega->id,
            'documento_type' => EntregaEquipo::class,
            'url' => $path,
        ]);

        $this->assertTrue($entrega->fresh()->hasDocumentoOficial());

        Livewire::test(EditEntregaEquipo::class, ['record' => $entrega->getKey()])
            ->call('saveAndGeneratePdf')
            ->assertHasNoFormErrors();

        Bus::assertDispatched(
            GenerateEquipoFormularioPdfJob::class,
            fn (GenerateEquipoFormularioPdfJob $job): bool => $job->record->is($entrega)
                && $job->tipo === EquipoFormularioPdfTipo::Entrega,
        );
    }
}
