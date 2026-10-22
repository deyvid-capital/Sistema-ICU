<?php

namespace Tests\Feature;

use App\Filament\Resources\Activitylogs\ActivitylogResource;
use App\Filament\Resources\Activitylogs\Pages\ListActivitylogs;
use App\Models\CargadorCel;
use App\Models\User;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function crearSuperAdmin(): User
    {
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

        return $admin;
    }

    public function test_crear_y_actualizar_un_cargador_registra_quien_cuando_y_que_cambio(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $cargador = CargadorCel::factory()->create([
            'modelo' => 'USB-C 20W',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'created',
            'subject_type' => $cargador->getMorphClass(),
            'subject_id' => $cargador->getKey(),
            'causer_type' => $admin->getMorphClass(),
            'causer_id' => $admin->getKey(),
        ]);

        $cargador->update(['modelo' => 'USB-C 30W']);

        $actividad = Activity::query()
            ->where('event', 'updated')
            ->where('subject_type', $cargador->getMorphClass())
            ->where('subject_id', $cargador->getKey())
            ->latest('id')
            ->first();

        $this->assertNotNull($actividad);
        $this->assertSame($admin->getKey(), $actividad->causer_id);
        $this->assertSame('USB-C 20W', $actividad->attribute_changes['old']['modelo'] ?? null);
        $this->assertSame('USB-C 30W', $actividad->attribute_changes['attributes']['modelo'] ?? null);
    }

    public function test_no_registra_el_password_del_usuario(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $usuario = User::factory()->create();
        $usuario->update(['password' => 'NuevaClave123']);

        $registros = Activity::query()
            ->where('subject_type', $usuario->getMorphClass())
            ->where('subject_id', $usuario->getKey())
            ->get();

        $this->assertNotEmpty($registros);

        foreach ($registros as $registro) {
            $this->assertArrayNotHasKey('password', $registro->attribute_changes['attributes'] ?? []);
            $this->assertArrayNotHasKey('password', $registro->attribute_changes['old'] ?? []);
        }
    }

    public function test_super_admin_puede_ver_el_listado_de_actividad(): void
    {
        $admin = $this->crearSuperAdmin();
        $this->actingAs($admin);

        CargadorCel::factory()->create();

        $this->get(ActivitylogResource::getUrl('index', panel: 'admin'))
            ->assertSuccessful();

        Livewire::test(ListActivitylogs::class)
            ->assertSuccessful();
    }

    public function test_las_horas_se_muestran_en_zona_de_la_paz(): void
    {
        $this->assertSame('America/La_Paz', FilamentTimezone::get());

        $utc = Carbon::parse('2026-08-24 20:57:00', 'UTC');
        $laPaz = $utc->copy()->timezone(config('app.display_timezone'));

        $this->assertSame('16:57:00', $laPaz->format('H:i:s'));
        $this->assertSame('America/La_Paz', $laPaz->timezoneName);
    }

    public function test_usuario_sin_permiso_no_accede_al_registro_de_actividad(): void
    {
        $role = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);
        $sinPermiso = User::factory()->create();
        $sinPermiso->assignRole($role);

        $this->actingAs($sinPermiso)
            ->get(ActivitylogResource::getUrl('index', panel: 'admin'))
            ->assertForbidden();
    }
}
