<?php

namespace Tests\Feature\Filament;

use App\Enums\TipoAcceso;
use App\Filament\Resources\Users\Pages\ManageUserSeguridad;
use App\Models\User;
use App\Support\Seguridad\ProteccionSuperAdmin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguridadSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private Role $rolSuperAdmin;

    private Role $rolOperador;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--ignore-existing-policies' => true,
        ]);

        $this->rolSuperAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->rolSuperAdmin->syncPermissions(Permission::query()->get());
        $this->rolOperador = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    private function crearSuperAdmin(): User
    {
        $usuario = User::factory()->create();
        $usuario->assignRole($this->rolSuperAdmin);

        return $usuario;
    }

    public function test_super_admin_solo_no_puede_quitarse_el_rol(): void
    {
        $admin = $this->crearSuperAdmin();
        $this->actingAs($admin);

        Livewire::test(ManageUserSeguridad::class, ['record' => $admin->id])
            ->fillForm([
                'roles' => [$this->rolOperador->id],
            ])
            ->call('save')
            ->assertNotified();

        $this->assertTrue($admin->fresh()->hasRole('super_admin'));
    }

    public function test_no_puede_quitarse_super_admin_si_el_otro_no_tiene_acceso(): void
    {
        $admin = $this->crearSuperAdmin();
        $otro = User::factory()->conRol('super_admin')->sinAcceso()->create();

        $this->actingAs($admin);

        Livewire::test(ManageUserSeguridad::class, ['record' => $admin->id])
            ->fillForm([
                'roles' => [$this->rolOperador->id],
            ])
            ->call('save')
            ->assertNotified();

        $this->assertTrue($admin->fresh()->hasRole('super_admin'));
        $this->assertTrue($otro->fresh()->hasRole('super_admin'));
    }

    public function test_puede_quitarse_super_admin_si_hay_otro_activo(): void
    {
        $admin = $this->crearSuperAdmin();
        User::factory()->conRol('super_admin')->create();

        $this->actingAs($admin);

        Livewire::test(ManageUserSeguridad::class, ['record' => $admin->id])
            ->fillForm([
                'roles' => [$this->rolOperador->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertFalse($admin->fresh()->hasRole('super_admin'));
        $this->assertTrue($admin->fresh()->hasRole('operador'));
    }

    public function test_ficha_propia_rechaza_deshabilitar_acceso_en_servidor(): void
    {
        $admin = $this->crearSuperAdmin();
        User::factory()->conRol('super_admin')->create();

        $this->actingAs($admin);

        $errores = ProteccionSuperAdmin::erroresAlGuardar(
            $admin,
            ['acceso_habilitado' => false],
            ['tipo_acceso' => TipoAcceso::Indefinido->value],
        );

        $this->assertNotEmpty($errores);
    }

    public function test_ficha_propia_rechaza_cambiar_vencimiento_en_servidor(): void
    {
        $admin = $this->crearSuperAdmin();
        User::factory()->conRol('super_admin')->create();

        $this->actingAs($admin);

        $errores = ProteccionSuperAdmin::erroresAlGuardar(
            $admin,
            ['acceso_expira_at' => now()->addMonth()],
            ['tipo_acceso' => TipoAcceso::Definido->value],
        );

        $this->assertNotEmpty($errores);
    }

    public function test_no_puede_cerrar_sus_propias_sesiones(): void
    {
        $admin = $this->crearSuperAdmin();

        $this->actingAs($admin);

        $this->assertFalse(ProteccionSuperAdmin::puedeCerrarSesiones($admin));
    }

    public function test_puede_quitar_super_admin_a_otro_usuario(): void
    {
        $admin = $this->crearSuperAdmin();
        $objetivo = User::factory()->conRol('super_admin')->create();

        $this->actingAs($admin);

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->fillForm([
                'roles' => [$this->rolOperador->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($objetivo->fresh()->hasRole('super_admin'));
        $this->assertTrue($objetivo->fresh()->hasRole('operador'));
    }

    public function test_puede_deshabilitar_acceso_a_otro_super_admin(): void
    {
        $admin = $this->crearSuperAdmin();
        $objetivo = User::factory()->conRol('super_admin')->create();

        $this->actingAs($admin);

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->fillForm([
                'acceso_habilitado' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($objetivo->fresh()->acceso_habilitado);
    }

    public function test_cambiar_de_definido_a_indefinido_limpia_la_fecha(): void
    {
        $admin = $this->crearSuperAdmin();
        $objetivo = User::factory()->conRol('operador')->accesoDefinido(now()->addMonth())->create();

        $this->actingAs($admin);

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->fillForm([
                'tipo_acceso' => TipoAcceso::Indefinido->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($objetivo->fresh()->acceso_expira_at);
    }

    public function test_cambiar_de_indefinido_a_definido_guarda_la_fecha(): void
    {
        $admin = $this->crearSuperAdmin();
        $objetivo = User::factory()->conRol('operador')->create(['acceso_expira_at' => null]);
        $fecha = now()->addWeeks(2)->startOfMinute();

        $this->actingAs($admin);

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->set('data.tipo_acceso', TipoAcceso::Definido->value)
            ->set('data.acceso_expira_at', $fecha->toDateTimeString())
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotNull($objetivo->fresh()->acceso_expira_at);
        $this->assertTrue($objetivo->fresh()->acceso_expira_at->equalTo($fecha));
    }

    public function test_cambiar_fecha_manteniendo_definido_actualiza_la_fecha(): void
    {
        $admin = $this->crearSuperAdmin();
        $objetivo = User::factory()->conRol('operador')->accesoDefinido(now()->addWeek())->create();
        $nuevaFecha = now()->addMonths(2)->startOfMinute();

        $this->actingAs($admin);

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->set('data.acceso_expira_at', $nuevaFecha->toDateTimeString())
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($objetivo->fresh()->acceso_expira_at->equalTo($nuevaFecha));
    }
}
