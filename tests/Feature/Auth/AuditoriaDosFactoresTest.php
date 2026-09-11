<?php

namespace Tests\Feature\Auth;

use App\Enums\DominioActividad;
use App\Enums\EventoSeguridad;
use App\Filament\Pages\Auth\SetupTwoFactor;
use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditoriaDosFactoresTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    /**
     * @return Collection<int, Activity>
     */
    private function eventos(EventoSeguridad $evento): Collection
    {
        return Activity::query()
            ->where('log_name', DominioActividad::Seguridad->value)
            ->where('event', $evento->value)
            ->orderBy('id')
            ->get();
    }

    private function codigoValido(User $usuario): string
    {
        return filament('filament-breezy')
            ->getEngine()
            ->getCurrentOtp($usuario->breezySession->two_factor_secret);
    }

    public function test_confirmar_enrolamiento_se_registra(): void
    {
        $usuario = User::factory()->conRol()->create(['requiere_2fa' => true]);
        $this->actingAs($usuario);

        $componente = Livewire::test(SetupTwoFactor::class);
        $componente->call('empezar');

        $usuario->refresh();

        $componente
            ->fillForm(['code' => $this->codigoValido($usuario)])
            ->call('confirmar');

        $this->assertCount(1, $this->eventos(EventoSeguridad::DosFactoresConfirmado));
        $this->assertCount(0, $this->eventos(EventoSeguridad::DosFactoresCodigoIncorrecto));
    }

    public function test_codigo_incorrecto_en_el_enrolamiento_se_registra(): void
    {
        $usuario = User::factory()->conRol()->create(['requiere_2fa' => true]);
        $this->actingAs($usuario);

        $componente = Livewire::test(SetupTwoFactor::class);
        $componente->call('empezar');

        $componente
            ->fillForm(['code' => '000000'])
            ->call('confirmar');

        $eventos = $this->eventos(EventoSeguridad::DosFactoresCodigoIncorrecto);

        $this->assertCount(1, $eventos);
        $this->assertSame((string) $usuario->id, (string) $eventos->first()->subject_id);
        $this->assertCount(0, $this->eventos(EventoSeguridad::DosFactoresConfirmado));
    }

    public function test_reset_por_admin_registra_al_admin_y_al_usuario_afectado(): void
    {
        $administrador = User::factory()->conRol()->create();
        $objetivo = User::factory()->conRol('operador')->create();

        $this->actingAs($administrador);

        app(AuditorSeguridad::class)->dosFactoresReseteado($objetivo);

        $eventos = $this->eventos(EventoSeguridad::DosFactoresReseteado);

        $this->assertCount(1, $eventos);
        $this->assertSame((string) $objetivo->id, (string) $eventos->first()->subject_id);
        $this->assertSame((string) $administrador->id, (string) $eventos->first()->causer_id);
        $this->assertSame($administrador->name, $eventos->first()->properties['administrador']);
    }
}
