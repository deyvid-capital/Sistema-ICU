<?php

namespace Tests\Feature;

use App\Enums\EstadoEquipo;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CriticalEventNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: User}
     */
    private function crearAdministradoresYActor(): array
    {
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('admin', 'web');

        $superAdmin = User::factory()->create()->assignRole('super_admin');
        $admin = User::factory()->create()->assignRole('admin');
        $actor = User::factory()->create();

        return [$superAdmin, $admin, $actor];
    }

    public function test_notifica_a_los_administradores_al_crear_una_asignacion_de_equipo(): void
    {
        Notification::fake();

        [$superAdmin, $admin, $actor] = $this->crearAdministradoresYActor();
        $this->actingAs($actor);

        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $actor->id,
            'fecha_fin' => null,
        ]);

        Notification::assertSentTo($superAdmin, CriticalEventNotification::class);
        Notification::assertSentTo($admin, CriticalEventNotification::class);
    }

    public function test_no_notifica_al_actor_que_origino_el_evento(): void
    {
        Notification::fake();

        [, , $actor] = $this->crearAdministradoresYActor();
        $actor->assignRole('admin'); // el actor también es admin
        $this->actingAs($actor);

        $equipo = Equipo::factory()->create(['estado' => EstadoEquipo::SinAsignar]);

        AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => $actor->id,
            'fecha_fin' => null,
        ]);

        Notification::assertNotSentTo($actor, CriticalEventNotification::class);
    }

    public function test_el_correo_solo_se_envia_en_severidad_alta(): void
    {
        $notifiable = new User;

        $alta = new CriticalEventNotification('t', 'b', severity: CriticalEventNotification::SEVERIDAD_ALTA);
        $media = new CriticalEventNotification('t', 'b', severity: CriticalEventNotification::SEVERIDAD_MEDIA);

        $this->assertEqualsCanonicalizing(['database', 'mail'], $alta->via($notifiable));
        $this->assertSame(['database'], $media->via($notifiable));
    }
}
