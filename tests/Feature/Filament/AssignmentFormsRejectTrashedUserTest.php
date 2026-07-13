<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\SoftwareUsuarios\Pages\CreateSoftwareUsuario;
use App\Filament\Resources\SoftwareUsuarios\SoftwareUsuarioResource;
use App\Models\Software;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssignmentFormsRejectTrashedUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SoftwareUsuarioResource::skipAuthorization();

        $this->actingAs(User::factory()->create());
    }

    public function test_it_rejects_assigning_software_to_a_trashed_user(): void
    {
        $trashedUser = User::factory()->create();
        $trashedUser->delete();
        $software = Software::factory()->create();

        Livewire::test(CreateSoftwareUsuario::class)
            ->fillForm([
                'usuario_id' => $trashedUser->id,
                'id_software' => $software->id_software,
                'estado' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['usuario_id']);
    }

    public function test_it_allows_assigning_software_to_an_active_user(): void
    {
        $user = User::factory()->create();
        $software = Software::factory()->create();

        Livewire::test(CreateSoftwareUsuario::class)
            ->fillForm([
                'usuario_id' => $user->id,
                'id_software' => $software->id_software,
                'estado' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }
}
