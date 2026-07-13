<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserAreaCargoValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserResource::skipAuthorization();

        $this->actingAs(User::factory()->create());
    }

    public function test_user_can_be_created_when_cargo_belongs_to_selected_area(): void
    {
        $area = Area::factory()->create();
        $cargo = Cargo::factory()->create(['area_id' => $area->id_area]);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'test.user@example.com',
                'password' => 'password123',
                'id_area' => $area->id_area,
                'id_cargo' => $cargo->id_cargo,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(User::class, [
            'email' => 'test.user@example.com',
            'id_area' => $area->id_area,
            'id_cargo' => $cargo->id_cargo,
        ]);
    }

    public function test_user_creation_fails_when_cargo_does_not_belong_to_selected_area(): void
    {
        $area = Area::factory()->create();
        $otherArea = Area::factory()->create();
        $cargoFromOtherArea = Cargo::factory()->create(['area_id' => $otherArea->id_area]);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test User',
                'email' => 'test.user2@example.com',
                'password' => 'password123',
                'id_area' => $area->id_area,
                'id_cargo' => $cargoFromOtherArea->id_cargo,
            ])
            ->call('create')
            ->assertHasFormErrors(['id_cargo']);

        $this->assertDatabaseMissing(User::class, [
            'email' => 'test.user2@example.com',
        ]);
    }
}
