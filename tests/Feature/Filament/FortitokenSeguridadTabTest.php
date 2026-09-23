<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ManageUserSeguridad;
use App\Models\AccesoVirtual;
use App\Models\User;
use App\Models\UsuarioFortitoken;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FortitokenSeguridadTabTest extends TestCase
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

    public function test_saving_seguridad_creates_fortitoken_for_the_user(): void
    {
        $objetivo = User::factory()->create();

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->fillForm([
                'fortitoken_serial' => 'FTK-ABC-001',
                'fortitoken_estado' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $token = UsuarioFortitoken::query()->where('usuario_id', $objetivo->id)->first();

        $this->assertNotNull($token);
        $this->assertSame('FTK-ABC-001', $token->serial);
        $this->assertTrue($token->estado);
        $this->assertNotSame(
            'FTK-ABC-001',
            DB::table('usuario_fortitokens')->where('id', $token->id)->value('serial'),
        );
    }

    public function test_clearing_serial_removes_fortitoken(): void
    {
        $objetivo = User::factory()->create();
        UsuarioFortitoken::factory()->create([
            'usuario_id' => $objetivo->id,
            'serial' => 'FTK-KEEP',
            'estado' => true,
        ]);

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->fillForm([
                'fortitoken_serial' => '',
                'fortitoken_estado' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSoftDeleted('usuario_fortitokens', [
            'usuario_id' => $objetivo->id,
        ]);
    }

    public function test_acceso_virtual_can_exist_without_vpn(): void
    {
        $user = User::factory()->create();

        $acceso = AccesoVirtual::factory()->sinVpn()->create([
            'usuario_id' => $user->id,
            'estado' => true,
        ]);

        $this->assertNull($acceso->fresh()->vpn_id);
        $this->assertDatabaseHas('accesos_virtuales', [
            'id' => $acceso->id,
            'usuario_id' => $user->id,
            'vpn_id' => null,
        ]);
    }
}
