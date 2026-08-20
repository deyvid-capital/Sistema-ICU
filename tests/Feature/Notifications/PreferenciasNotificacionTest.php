<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferenciasNotificacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_por_defecto_recibe_todos_los_canales(): void
    {
        $user = User::factory()->create();

        $notif = new CriticalEventNotification('t', 'b', severity: CriticalEventNotification::SEVERIDAD_ALTA);

        $this->assertSame(['database', 'mail'], $notif->via($user));
    }

    public function test_una_preferencia_deshabilitada_quita_el_canal(): void
    {
        $user = User::factory()->create();

        NotificationPreference::create([
            'usuario_id' => $user->id,
            'tipo_evento' => 'evento_critico',
            'canal' => 'mail',
            'habilitado' => false,
        ]);

        $notif = new CriticalEventNotification('t', 'b', severity: CriticalEventNotification::SEVERIDAD_ALTA);

        $this->assertSame(['database'], $notif->via($user->fresh()));
    }

    public function test_severidad_media_solo_usa_database(): void
    {
        $user = User::factory()->create();

        $notif = new CriticalEventNotification('t', 'b', severity: CriticalEventNotification::SEVERIDAD_MEDIA);

        $this->assertSame(['database'], $notif->via($user));
    }

    public function test_usuario_no_persistido_recibe_por_defecto(): void
    {
        $notif = new CriticalEventNotification('t', 'b', severity: CriticalEventNotification::SEVERIDAD_ALTA);

        $this->assertSame(['database', 'mail'], $notif->via(new User));
    }
}
