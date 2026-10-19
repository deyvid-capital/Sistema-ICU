<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserPermissionMemoizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::findOrCreate('Update:User', 'web');
        Permission::findOrCreate('Delete:User', 'web');
        Role::findOrCreate('editor', 'web')->givePermissionTo('Update:User');
        Role::findOrCreate('lector', 'web');
    }

    /**
     * @return array<string, bool|PermissionDoesNotExist>
     */
    private function memoria(User $user): array
    {
        return (new ReflectionProperty($user, 'memoizedPermissionChecks'))->getValue($user);
    }

    public function test_memoriza_la_respuesta_en_la_instancia(): void
    {
        $user = User::factory()->create()->assignRole('editor');

        $this->assertTrue($user->can('Update:User'));
        $this->assertArrayHasKey('|Update:User', $this->memoria($user));
        $this->assertTrue($user->can('Update:User'));
    }

    public function test_dos_usuarios_no_comparten_respuestas(): void
    {
        $editor = User::factory()->create()->assignRole('editor');
        $lector = User::factory()->create()->assignRole('lector');

        $this->assertTrue($editor->can('Update:User'));
        $this->assertFalse($lector->can('Update:User'));
        $this->assertTrue($editor->can('Update:User'));
        $this->assertFalse($lector->can('Update:User'));
    }

    public function test_asignar_un_rol_se_refleja_en_la_misma_instancia(): void
    {
        $user = User::factory()->create()->assignRole('lector');

        $this->assertFalse($user->can('Update:User'));

        $user->assignRole('editor');

        $this->assertTrue($user->can('Update:User'));
    }

    public function test_quitar_un_rol_se_refleja_en_la_misma_instancia(): void
    {
        $user = User::factory()->create()->assignRole('editor');

        $this->assertTrue($user->can('Update:User'));

        $user->removeRole('editor');

        $this->assertFalse($user->can('Update:User'));
    }

    public function test_sincronizar_roles_se_refleja_en_la_misma_instancia(): void
    {
        $user = User::factory()->create()->assignRole('editor');

        $this->assertTrue($user->can('Update:User'));

        $user->syncRoles(['lector']);

        $this->assertFalse($user->can('Update:User'));
    }

    public function test_permisos_directos_se_reflejan_en_la_misma_instancia(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('Delete:User'));

        $user->givePermissionTo('Delete:User');
        $this->assertTrue($user->can('Delete:User'));

        $user->revokePermissionTo('Delete:User');
        $this->assertFalse($user->can('Delete:User'));
    }

    public function test_editar_los_permisos_de_un_rol_invalida_a_los_usuarios_cargados(): void
    {
        $user = User::factory()->create()->assignRole('editor');

        $this->assertTrue($user->can('Update:User'));
        $this->assertFalse($user->can('Delete:User'));

        Role::findByName('editor', 'web')->syncPermissions(['Delete:User']);

        $this->assertFalse($user->can('Update:User'));
        $this->assertTrue($user->can('Delete:User'));
    }

    public function test_un_permiso_inexistente_sigue_denegado_y_lanzando_excepcion(): void
    {
        $user = User::factory()->create()->assignRole('editor');

        $this->assertFalse($user->can('NoExiste:User'));
        $this->assertFalse($user->can('NoExiste:User'));
        $this->assertFalse($user->checkPermissionTo('NoExiste:User'));

        $this->expectException(PermissionDoesNotExist::class);
        $user->hasPermissionTo('NoExiste:User');
    }

    public function test_la_memoria_no_sobrevive_a_la_serializacion(): void
    {
        $user = User::factory()->create()->assignRole('editor');
        $user->can('Update:User');

        $this->assertNotEmpty($this->memoria($user));

        $restaurado = unserialize(serialize($user));

        $this->assertSame([], $this->memoria($restaurado));
        $this->assertTrue($restaurado->can('Update:User'));
    }
}
