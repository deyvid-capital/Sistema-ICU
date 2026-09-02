<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\Configuracion\Pages\Correo;
use App\Models\MailSetting;
use App\Models\User;
use App\Services\Configuracion\MailConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CorreoPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $usuario = User::factory()->create();
        $usuario->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));

        $this->actingAs($usuario);
    }

    public function test_la_pagina_carga_sin_exponer_contrasena(): void
    {
        MailSetting::query()->create([
            'activo' => true,
            'mailer' => 'smtp',
            'host' => 'smtp.office365.com',
            'port' => 587,
            'username' => 'ti@example.com',
            'password' => 'Secreto#123',
            'from_address' => 'ti@example.com',
            'from_name' => 'ICU',
        ]);

        $this->get(Correo::getUrl(panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('Correo SMTP')
            ->assertDontSee('Secreto#123');
    }

    public function test_guardar_cifra_la_contrasena_y_aplica_config(): void
    {
        Livewire::test(Correo::class)
            ->fillForm([
                'activo' => true,
                'mailer' => 'smtp',
                'host' => 'smtp.office365.com',
                'port' => 587,
                'scheme' => 'smtp',
                'encryption' => 'tls',
                'require_tls' => true,
                'username' => 'ti@example.com',
                'password' => 'NuevaClave#456',
                'from_address' => 'ti@example.com',
                'from_name' => 'Sistema ICU',
                'ehlo_domain' => 'example.com',
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $fila = MailSetting::query()->first();
        $this->assertNotNull($fila);
        $this->assertTrue($fila->activo);
        $this->assertSame('smtp.office365.com', $fila->host);
        $this->assertSame('NuevaClave#456', $fila->password);

        $raw = DB::table('mail_settings')->where('id', $fila->id)->value('password');
        $this->assertNotSame('NuevaClave#456', $raw);
        $this->assertNotEmpty($raw);

        $servicio = app(MailConfigService::class);
        $servicio->olvidar();
        $servicio->aplicar();

        $this->assertSame('smtp.office365.com', config('mail.mailers.smtp.host'));
        $this->assertSame('NuevaClave#456', config('mail.mailers.smtp.password'));
        $this->assertSame('ti@example.com', config('mail.from.address'));
    }

    public function test_guardar_sin_contrasena_conserva_la_anterior(): void
    {
        MailSetting::query()->create([
            'activo' => true,
            'mailer' => 'smtp',
            'host' => 'smtp.old.com',
            'port' => 587,
            'username' => 'old@example.com',
            'password' => 'ClaveOriginal#1',
            'from_address' => 'old@example.com',
            'from_name' => 'Old',
        ]);

        Livewire::test(Correo::class)
            ->fillForm([
                'activo' => true,
                'mailer' => 'smtp',
                'host' => 'smtp.new.com',
                'port' => 587,
                'scheme' => 'smtp',
                'encryption' => 'tls',
                'require_tls' => true,
                'username' => 'new@example.com',
                'password' => null,
                'from_address' => 'new@example.com',
                'from_name' => 'New',
                'ehlo_domain' => null,
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $fila = MailSetting::query()->firstOrFail();
        $this->assertSame('smtp.new.com', $fila->host);
        $this->assertSame('ClaveOriginal#1', $fila->password);
    }

    public function test_inactivo_no_sobrescribe_el_env(): void
    {
        config([
            'mail.mailers.smtp.host' => 'smtp.from-env.test',
            'mail.mailers.smtp.password' => 'env-password',
        ]);

        MailSetting::query()->create([
            'activo' => false,
            'mailer' => 'smtp',
            'host' => 'smtp.from-db.test',
            'port' => 587,
            'username' => 'db@example.com',
            'password' => 'db-password',
            'from_address' => 'db@example.com',
            'from_name' => 'DB',
        ]);

        $servicio = app(MailConfigService::class);
        $servicio->olvidar();
        $servicio->aplicar();

        $this->assertSame('smtp.from-env.test', config('mail.mailers.smtp.host'));
        $this->assertSame('env-password', config('mail.mailers.smtp.password'));
    }

    public function test_enviar_correo_de_prueba(): void
    {
        Mail::fake();

        MailSetting::query()->create([
            'activo' => true,
            'mailer' => 'array',
            'host' => '127.0.0.1',
            'port' => 2525,
            'from_address' => 'ti@example.com',
            'from_name' => 'ICU',
        ]);

        Livewire::test(Correo::class)
            ->callAction('probar', [
                'email' => 'destino@example.com',
            ])
            ->assertNotified();
    }
}
