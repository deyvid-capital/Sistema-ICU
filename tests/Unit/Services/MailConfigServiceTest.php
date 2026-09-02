<?php

namespace Tests\Unit\Services;

use App\Models\MailSetting;
use App\Services\Configuracion\MailConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_para_formulario_nunca_incluye_la_contrasena(): void
    {
        MailSetting::query()->create([
            'activo' => true,
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user@example.com',
            'password' => 'SuperSecret#99',
            'from_address' => 'user@example.com',
            'from_name' => 'ICU',
        ]);

        $servicio = app(MailConfigService::class);
        $servicio->olvidar();

        $formulario = $servicio->paraFormulario();

        $this->assertArrayNotHasKey('password', $formulario);
        $this->assertTrue($formulario['tiene_password']);
        $this->assertTrue($servicio->tienePasswordGuardada());
    }

    public function test_model_oculta_password_en_array(): void
    {
        $fila = MailSetting::query()->create([
            'activo' => true,
            'password' => 'NoDebeVerse',
            'host' => 'smtp.example.com',
        ]);

        $this->assertArrayNotHasKey('password', $fila->toArray());
        $this->assertSame('NoDebeVerse', $fila->password);
    }
}
