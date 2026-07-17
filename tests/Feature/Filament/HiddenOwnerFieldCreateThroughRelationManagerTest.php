<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ManageUserSoftware;
use App\Filament\Resources\Users\RelationManagers\LicenciasUsuariosRelationManager;
use App\Filament\Resources\Users\UserResource;
use App\Models\Licencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class HiddenOwnerFieldCreateThroughRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserResource::skipAuthorization();
        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_creating_through_a_relation_manager_with_a_hidden_required_owner_field_succeeds(): void
    {
        $user = User::factory()->create();
        $licencia = Licencia::factory()->create();

        Livewire::test(LicenciasUsuariosRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => ManageUserSoftware::class,
        ])
            ->callTableAction('create', data: [
                'licencia_id' => $licencia->id,
                'estado' => true,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('licencias_usuarios', [
            'usuario_id' => $user->id,
            'licencia_id' => $licencia->id,
        ]);
    }
}
