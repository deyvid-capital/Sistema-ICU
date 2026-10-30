<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Equipos\EquipoResource;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\RelationManagers\RecibosEquipoRelationManager;
use App\Filament\Resources\ReciboEquipos\Pages\ListReciboEquipos;
use App\Filament\Resources\ReciboEquipos\ReciboEquipoResource;
use App\Models\Equipo;
use App\Models\ReciboEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RecibosEquipoRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        EquipoResource::skipAuthorization();
        ReciboEquipoResource::skipAuthorization();
        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_creating_a_recibo_through_the_equipo_relation_manager_sets_equipo_id(): void
    {
        Storage::fake('local');

        $equipo = Equipo::factory()->create();

        Livewire::test(RecibosEquipoRelationManager::class, [
            'ownerRecord' => $equipo,
            'pageClass' => EditEquipo::class,
        ])
            ->callTableAction('create', data: [
                'fecha' => now()->toDateString(),
                'url' => [UploadedFile::fake()->create('recibo.pdf', 10, 'application/pdf')],
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('recibos_equipos', [
            'equipo_id' => $equipo->service_tag,
        ]);
    }

    public function test_pdf_preview_modal_is_not_available_through_the_relation_manager(): void
    {
        Storage::fake('local');

        $equipo = Equipo::factory()->create();

        $path = 'recibo-equipos/recibo.pdf';
        Storage::disk('local')->put($path, 'contenido-pdf');

        $recibo = ReciboEquipo::factory()->create([
            'equipo_id' => $equipo->service_tag,
            'url' => $path,
        ]);

        Livewire::test(RecibosEquipoRelationManager::class, [
            'ownerRecord' => $equipo,
            'pageClass' => EditEquipo::class,
        ])
            ->assertTableActionDoesNotExist('viewDocument', record: $recibo)
            ->assertTableActionExists('verDocumentoPantallaCompleta', record: $recibo)
            ->assertTableActionExists('downloadDocument', record: $recibo);
    }

    public function test_pdf_preview_modal_is_available_from_the_standalone_recibo_resource(): void
    {
        Storage::fake('local');

        $path = 'recibo-equipos/recibo.pdf';
        Storage::disk('local')->put($path, 'contenido-pdf');

        $recibo = ReciboEquipo::factory()->create(['url' => $path]);

        Livewire::test(
            ListReciboEquipos::class,
        )->assertTableActionExists('viewDocument', record: $recibo);
    }
}
