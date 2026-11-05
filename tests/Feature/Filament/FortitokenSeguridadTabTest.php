<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ManageUserSeguridad;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\AccesoVirtual;
use App\Models\User;
use App\Models\UsuarioFortitoken;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
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

    public function test_saving_seguridad_creates_several_fortitokens_for_the_user(): void
    {
        $objetivo = User::factory()->create();
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->fillForm([
                'fortitokens' => [
                    ['nombre_token' => 'Producción', 'serial' => 'FTK-ABC-001', 'estado' => true],
                    ['nombre_token' => 'Contingencia', 'serial' => 'FTK-ABC-002', 'estado' => false],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();

        $tokens = $objetivo->fortitokens()->get();

        $this->assertSame(['Contingencia', 'Producción'], $tokens->pluck('nombre_token')->all());
        $this->assertSame('FTK-ABC-001', $tokens->firstWhere('nombre_token', 'Producción')->serial);
        $this->assertFalse($tokens->firstWhere('nombre_token', 'Contingencia')->estado);
        $this->assertNotSame(
            'FTK-ABC-001',
            DB::table('usuario_fortitokens')->where('nombre_token', 'Producción')->value('serial'),
        );
    }

    public function test_the_form_shows_existing_tokens_with_their_serial(): void
    {
        $objetivo = User::factory()->create();
        $token = UsuarioFortitoken::factory()->create([
            'usuario_id' => $objetivo->id,
            'nombre_token' => 'Producción',
            'serial' => 'FTK-KEEP',
        ]);

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->assertSchemaStateSet([
                "fortitokens.record-{$token->id}.nombre_token" => 'Producción',
                "fortitokens.record-{$token->id}.serial" => 'FTK-KEEP',
                "fortitokens.record-{$token->id}.estado" => true,
            ]);
    }

    public function test_removing_a_token_soft_deletes_only_that_one(): void
    {
        $objetivo = User::factory()->create();
        $produccion = UsuarioFortitoken::factory()->create(['usuario_id' => $objetivo->id, 'nombre_token' => 'Producción', 'serial' => 'FTK-1']);
        $contingencia = UsuarioFortitoken::factory()->create(['usuario_id' => $objetivo->id, 'nombre_token' => 'Contingencia', 'serial' => 'FTK-2']);

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->set('data.fortitokens', [
                "record-{$produccion->id}" => ['nombre_token' => 'Producción', 'serial' => 'FTK-1', 'estado' => true],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNotSoftDeleted($produccion);
        $this->assertSoftDeleted($contingencia);
        $this->assertFalse($contingencia->fresh()->estado);
    }

    public function test_token_names_cannot_repeat_for_the_same_user(): void
    {
        $objetivo = User::factory()->create();
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->fillForm([
                'fortitokens' => [
                    ['nombre_token' => 'Producción', 'serial' => 'FTK-1', 'estado' => true],
                    ['nombre_token' => 'Producción', 'serial' => 'FTK-2', 'estado' => true],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['fortitokens.0.nombre_token', 'fortitokens.1.nombre_token']);

        $undoRepeaterFake();

        $this->assertSame(0, $objetivo->fortitokens()->count());
    }

    public function test_name_and_serial_are_required(): void
    {
        $objetivo = User::factory()->create();
        $undoRepeaterFake = Repeater::fake();

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->fillForm([
                'fortitokens' => [
                    ['nombre_token' => '', 'serial' => '', 'estado' => true],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors([
                'fortitokens.0.nombre_token' => 'required',
                'fortitokens.0.serial' => 'required',
            ]);

        $undoRepeaterFake();
    }

    public function test_saving_without_tokens_is_allowed(): void
    {
        $objetivo = User::factory()->create();

        Livewire::test(ManageUserSeguridad::class, ['record' => $objetivo->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, $objetivo->fortitokens()->count());
    }

    public function test_the_same_name_can_be_reused_after_removing_a_token(): void
    {
        $objetivo = User::factory()->create();
        UsuarioFortitoken::factory()->create(['usuario_id' => $objetivo->id, 'nombre_token' => 'Producción'])->delete();

        UsuarioFortitoken::factory()->create(['usuario_id' => $objetivo->id, 'nombre_token' => 'Producción']);

        $this->assertSame(1, $objetivo->fortitokens()->count());
    }

    public function test_the_user_page_lists_every_token_by_name(): void
    {
        $objetivo = User::factory()->create();
        UsuarioFortitoken::factory()->create(['usuario_id' => $objetivo->id, 'nombre_token' => 'Producción', 'estado' => true]);
        UsuarioFortitoken::factory()->create(['usuario_id' => $objetivo->id, 'nombre_token' => 'Contingencia', 'estado' => false]);

        Livewire::test(ViewUser::class, ['record' => $objetivo->id])
            ->assertSee('Producción (activo)')
            ->assertSee('Contingencia (inactivo)');
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
