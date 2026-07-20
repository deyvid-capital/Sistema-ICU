<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Equipos\RelationManagers\RecibosEquipoRelationManager;
use App\Filament\Resources\ReciboEquipos\Schemas\ReciboEquipoForm;
use App\Models\Equipo;
use App\Models\ReciboEquipo;
use App\Models\User;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ReciboEquipoStoredDocumentFilenameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_build_stored_document_filename_uses_service_tag_and_date(): void
    {
        $this->assertSame(
            'ST100001_2026-07-20.pdf',
            ReciboEquipoForm::buildStoredDocumentFilename('ST100001', '2026-07-20'),
        );
    }

    public function test_resolve_service_tag_from_form_field(): void
    {
        $get = Mockery::mock(Get::class);
        $get->shouldReceive('__invoke')->with('equipo_id')->andReturn('ST100001');

        $this->assertSame(
            'ST100001',
            ReciboEquipoForm::resolveServiceTagForFilename($get),
        );
    }

    public function test_resolve_service_tag_from_relation_manager_owner(): void
    {
        $equipo = Equipo::factory()->create(['service_tag' => 'ST999999']);

        $get = Mockery::mock(Get::class);
        $get->shouldReceive('__invoke')->with('equipo_id')->andReturn(null);

        $livewire = Livewire::test(RecibosEquipoRelationManager::class, [
            'ownerRecord' => $equipo,
            'pageClass' => EditEquipo::class,
        ])->instance();

        $this->assertSame(
            'ST999999',
            ReciboEquipoForm::resolveServiceTagForFilename($get, $livewire),
        );
    }

    public function test_resolve_service_tag_from_existing_recibo_record(): void
    {
        $recibo = ReciboEquipo::factory()->create(['equipo_id' => Equipo::factory()->create([
            'service_tag' => 'ST555555',
        ])->service_tag]);

        $get = Mockery::mock(Get::class);
        $get->shouldReceive('__invoke')->with('equipo_id')->andReturn(null);

        $this->assertSame(
            'ST555555',
            ReciboEquipoForm::resolveServiceTagForFilename($get, null, $recibo),
        );
    }
}
