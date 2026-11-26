<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\AzureAds\AzureAdResource;
use App\Filament\Resources\AzureAds\Pages\CreateAzureAd;
use App\Models\AzureAd;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AzureAdFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AzureAdResource::skipAuthorization();

        $this->actingAs(User::factory()->conRol()->create());
    }

    public function test_la_sincronizacion_por_defecto_es_una_opcion_valida(): void
    {
        $usuario = User::factory()->create();

        Livewire::test(CreateAzureAd::class)
            ->assertSchemaStateSet(['sincronizacion' => 'Local / Nube'])
            ->fillForm([
                'usuario_id' => $usuario->id,
                'iniciales' => 'MRV',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(AzureAd::class, [
            'usuario_id' => $usuario->id,
            'sincronizacion' => 'Local / Nube',
        ]);
    }

    public function test_rechaza_la_opcion_retirada(): void
    {
        Livewire::test(CreateAzureAd::class)
            ->fillForm([
                'usuario_id' => User::factory()->create()->id,
                'iniciales' => 'MRV',
                'sincronizacion' => 'Local / No sincronizado',
            ])
            ->call('create')
            ->assertHasFormErrors(['sincronizacion' => 'in']);
    }
}
