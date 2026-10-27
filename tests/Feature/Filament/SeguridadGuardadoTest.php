<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ManageUserSeguridad;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguridadGuardadoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--ignore-existing-policies' => true,
        ]);

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::query()->get());

        $admin = User::factory()->create();
        $admin->assignRole($role);

        $this->actingAs($admin);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_guardar_persiste_2fa_obligatorio_y_preferencias(): void
    {
        $objetivo = User::factory()->create();

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->fillForm([
                'acceso_habilitado' => true,
                'requiere_2fa' => true,
                'pref__evento_critico__mail' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $objetivo->refresh();

        $this->assertTrue($objetivo->requiere_2fa);
        $this->assertFalse($objetivo->debeRecibir('evento_critico', 'mail'));
        $this->assertTrue($objetivo->debeRecibir('evento_critico', 'database'));
    }

    public function test_apagar_y_volver_a_encender_los_avisos_de_firma_digital(): void
    {
        $objetivo = User::factory()->create();

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->assertSee('Firma digital')
            ->assertDontSee('Qué pasa si lo desactivas')
            ->fillForm([
                'fortitoken_serial' => 'FTK-001',
                'pref__firma_digital__mail' => false,
            ])
            ->assertSee('Qué pasa si lo desactivas')
            ->assertSee('Nada se bloquea')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($objetivo->debeRecibir('firma_digital', 'mail'));
        $this->assertTrue($objetivo->debeRecibir('firma_digital', 'database'));

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->assertSchemaStateSet(['pref__firma_digital__mail' => false])
            ->assertSee('Qué pasa si lo desactivas')
            ->fillForm(['pref__firma_digital__mail' => true])
            ->assertDontSee('Qué pasa si lo desactivas')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($objetivo->debeRecibir('firma_digital', 'mail'));
    }
}
