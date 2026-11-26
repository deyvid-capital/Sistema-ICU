<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Models\UsuarioExterno;
use App\Notifications\UsuarioExternoFinalizadoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RecordatorioFinUsuarioExternoTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $responsable;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Carbon::setTestNow(Carbon::parse('2026-12-15 09:00', 'America/La_Paz'));

        $this->admin = User::factory()->conRol()->create();
        $this->responsable = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_avisa_a_los_administradores_por_correo_cuando_llega_la_fecha_fin(): void
    {
        $usuarioExterno = $this->usuarioExterno(['fecha_fin' => '2026-12-15']);

        $this->artisan('usuarios-externos:recordar-fin')->assertSuccessful();

        Notification::assertSentTo(
            $this->admin,
            UsuarioExternoFinalizadoNotification::class,
            fn (UsuarioExternoFinalizadoNotification $aviso, array $canales): bool => $aviso->usuarioExterno->is($usuarioExterno) && in_array('mail', $canales, true),
        );
        Notification::assertNotSentTo($this->responsable, UsuarioExternoFinalizadoNotification::class);
        $this->assertNotNull($usuarioExterno->fresh()->recordatorio_enviado_at);
    }

    public function test_avisa_los_accesos_vencidos_que_no_se_avisaron(): void
    {
        $this->usuarioExterno(['fecha_fin' => '2026-12-10']);

        $this->artisan('usuarios-externos:recordar-fin')->assertSuccessful();

        Notification::assertSentToTimes($this->admin, UsuarioExternoFinalizadoNotification::class, 1);
    }

    public function test_no_avisa_antes_de_la_fecha_fin_ni_sin_fecha_fin(): void
    {
        $this->usuarioExterno(['fecha_fin' => '2026-12-16']);
        $this->usuarioExterno(['fecha_fin' => null]);

        $this->artisan('usuarios-externos:recordar-fin')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_avisa_una_sola_vez(): void
    {
        $this->usuarioExterno(['fecha_fin' => '2026-12-15']);

        $this->artisan('usuarios-externos:recordar-fin')->assertSuccessful();
        $this->artisan('usuarios-externos:recordar-fin')->assertSuccessful();

        Notification::assertSentToTimes($this->admin, UsuarioExternoFinalizadoNotification::class, 1);
    }

    public function test_un_usuario_externo_desactivado_o_eliminado_cancela_el_aviso(): void
    {
        $desactivada = $this->usuarioExterno(['fecha_fin' => '2026-12-15']);
        $desactivada->update(['estado' => false]);

        $eliminada = $this->usuarioExterno(['fecha_fin' => '2026-12-15']);
        $eliminada->delete();

        $this->artisan('usuarios-externos:recordar-fin')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertSame('Cancelado', $desactivada->fresh()->estadoRecordatorio());
        $this->assertSame('Cancelado', UsuarioExterno::withTrashed()->find($eliminada->id)->estadoRecordatorio());
    }

    public function test_cambiar_la_fecha_fin_reprograma_el_aviso_ya_enviado(): void
    {
        $usuarioExterno = $this->usuarioExterno(['fecha_fin' => '2026-12-15']);

        $this->artisan('usuarios-externos:recordar-fin')->assertSuccessful();

        $usuarioExterno->refresh()->update(['fecha_fin' => '2026-12-20']);
        $this->assertNull($usuarioExterno->fresh()->recordatorio_enviado_at);

        $this->artisan('usuarios-externos:recordar-fin')->assertSuccessful();
        Notification::assertSentToTimes($this->admin, UsuarioExternoFinalizadoNotification::class, 1);

        Carbon::setTestNow(Carbon::parse('2026-12-20 08:00', 'America/La_Paz'));
        $this->artisan('usuarios-externos:recordar-fin')->assertSuccessful();
        Notification::assertSentToTimes($this->admin, UsuarioExternoFinalizadoNotification::class, 2);
    }

    public function test_respeta_la_preferencia_del_administrador(): void
    {
        $this->admin->notificationPreferences()->create([
            'tipo_evento' => 'usuario_externo',
            'canal' => 'mail',
            'habilitado' => false,
        ]);

        $aviso = new UsuarioExternoFinalizadoNotification($this->usuarioExterno(['fecha_fin' => '2026-12-15']));

        $this->assertSame(['database'], $aviso->via($this->admin));
    }

    public function test_el_correo_incluye_los_datos_del_usuario_externo(): void
    {
        $usuarioExterno = $this->usuarioExterno(['fecha_fin' => '2026-12-15', 'onedrive' => 'Carpeta Auditoría']);

        $correo = (new UsuarioExternoFinalizadoNotification($usuarioExterno))->toMail($this->admin);
        $texto = implode("\n", [...$correo->introLines, ...$correo->outroLines]);

        $this->assertStringContainsString($usuarioExterno->nombre_contacto, $correo->subject);
        $this->assertStringContainsString('15/12/2026', $texto);
        $this->assertStringContainsString('OneDrive: Carpeta Auditoría', $texto);
        $this->assertStringContainsString($this->responsable->name, $texto);
    }

    public function test_el_scheduler_programa_el_recordatorio(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('usuarios-externos:recordar-fin')
            ->assertSuccessful();
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function usuarioExterno(array $datos): UsuarioExterno
    {
        return UsuarioExterno::factory()->create([
            'responsable_id' => $this->responsable->id,
            'fecha_inicio' => '2026-11-25',
            'onedrive' => 'Carpeta',
            ...$datos,
        ]);
    }
}
