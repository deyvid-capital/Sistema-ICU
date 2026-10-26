<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Enums\EventoSeguridad;
use App\Exceptions\FirmaDigitalException;
use App\Livewire\Profile\UpdatePassword;
use App\Models\User;
use App\Notifications\Firma\FirmaRequiereReactivacionNotification;
use App\Services\Firma\EnvolturaContrasena;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\TestCase;

class CambioContrasenaFirmaTest extends TestCase
{
    use FakeFirmador;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_cambiar_la_contrasena_desde_mi_perfil_mantiene_la_firma_activa(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoActivo($titular);
        $this->actingAs($titular);

        Livewire::test(UpdatePassword::class)
            ->fillForm([
                'current_password' => self::CONTRASENA_SISTEMA,
                'new_password' => 'NuevaClave1',
                'new_password_confirmation' => 'NuevaClave1',
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $certificado->refresh();

        $this->assertTrue(Hash::check('NuevaClave1', $titular->fresh()->password));
        $this->assertSame(EstadoCertificadoFirma::Activo, $certificado->estado);
        $this->assertSame(
            self::CONTRASENA_P12,
            app(EnvolturaContrasena::class)->desenvolver($certificado->contrasena_p12_envuelta, 'NuevaClave1'),
        );
        Notification::assertNothingSent();
    }

    public function test_la_contrasena_anterior_ya_no_abre_la_firma(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoActivo($titular);
        $this->actingAs($titular);

        Livewire::test(UpdatePassword::class)
            ->fillForm([
                'current_password' => self::CONTRASENA_SISTEMA,
                'new_password' => 'NuevaClave1',
                'new_password_confirmation' => 'NuevaClave1',
            ])
            ->call('submit');

        $this->expectException(FirmaDigitalException::class);
        app(EnvolturaContrasena::class)->desenvolver($certificado->refresh()->contrasena_p12_envuelta, self::CONTRASENA_SISTEMA);
    }

    public function test_si_un_admin_restablece_la_contrasena_la_firma_requiere_reactivacion(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoActivo($titular);
        $admin = User::factory()->create(['name' => 'María Gómez']);
        $admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
        $this->actingAs($admin);

        $titular->update(['password' => 'Temporal123']);

        $certificado->refresh();
        $this->assertSame(EstadoCertificadoFirma::RequiereReactivacion, $certificado->estado);
        $this->assertNull($certificado->contrasena_p12_envuelta);

        Notification::assertSentTo($titular, FirmaRequiereReactivacionNotification::class,
            fn (FirmaRequiereReactivacionNotification $n): bool => ! $n->paraAdministrador
                && $n->motivo === 'restablecida por el administrador María Gómez');

        foreach ([$admin, $superAdmin] as $administrador) {
            Notification::assertSentTo($administrador, FirmaRequiereReactivacionNotification::class,
                fn (FirmaRequiereReactivacionNotification $n): bool => $n->paraAdministrador
                    && $n->titularNombre === $titular->name);
        }

        $evento = Activity::query()->where('event', EventoSeguridad::FirmaDigitalSuspendida->value)->sole();
        $this->assertSame((string) $admin->id, (string) $evento->causer_id);
    }

    public function test_si_se_recupera_por_correo_la_firma_requiere_reactivacion(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoActivo($titular);

        // Sin usuario autenticado, como en la recuperación por correo o la importación.
        $titular->forceFill(['password' => 'Recuperada123'])->save();

        $this->assertSame(EstadoCertificadoFirma::RequiereReactivacion, $certificado->refresh()->estado);
        Notification::assertSentTo($titular, FirmaRequiereReactivacionNotification::class,
            fn (FirmaRequiereReactivacionNotification $n): bool => str_contains($n->motivo, 'recuperación de contraseña por correo'));
    }

    public function test_el_titular_no_recibe_el_aviso_de_administrador_aunque_sea_admin(): void
    {
        $titular = $this->titular();
        $titular->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));
        $this->certificadoActivo($titular);

        $titular->forceFill(['password' => 'Recuperada123'])->save();

        Notification::assertSentToTimes($titular, FirmaRequiereReactivacionNotification::class, 1);
    }

    public function test_otros_cambios_del_usuario_no_afectan_la_firma(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoActivo($titular);

        $titular->update(['telefono' => '70000000']);

        $this->assertSame(EstadoCertificadoFirma::Activo, $certificado->refresh()->estado);
        Notification::assertNothingSent();
    }

    public function test_sin_firma_activa_no_se_notifica_nada(): void
    {
        $titular = $this->titular();
        $this->certificadoPendiente($titular);

        $titular->forceFill(['password' => 'Recuperada123'])->save();

        Notification::assertNothingSent();
    }

    public function test_el_correo_al_titular_explica_como_reactivar(): void
    {
        $correo = FirmaRequiereReactivacionNotification::paraTitular('restablecida por el administrador X')
            ->toMail(User::factory()->make());

        $texto = implode("\n", $correo->introLines);

        $this->assertStringContainsString('Mi firma digital → Reactivar', $texto);
        $this->assertStringContainsString('**no** la del sistema', $texto);
    }
}
