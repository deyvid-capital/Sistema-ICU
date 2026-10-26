<?php

namespace Tests\Feature\Firma;

use App\Exceptions\FirmaDigitalException;
use App\Models\User;
use App\Services\Firma\BovedaCertificados;
use App\Services\Firma\EnvolturaContrasena;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class CustodiaCertificadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_la_boveda_guarda_el_p12_cifrado_en_el_disco_privado(): void
    {
        $usuario = User::factory()->create();
        $boveda = app(BovedaCertificados::class);

        $ruta = $boveda->guardar($usuario, 'contenido-del-p12');

        $this->assertStringStartsWith("firma-digital/certificados/{$usuario->id}/", $ruta);
        Storage::disk('local')->assertExists($ruta);
        $this->assertStringNotContainsString('contenido-del-p12', Storage::disk('local')->get($ruta));
        $this->assertSame('contenido-del-p12', $boveda->leer($ruta));
    }

    public function test_la_boveda_no_usa_app_key(): void
    {
        $cifrado = app(BovedaCertificados::class)->cifrar('secreto');

        config(['firma.encryption_key' => 'base64:'.base64_encode(random_bytes(32))]);

        $this->expectException(DecryptException::class);
        app(BovedaCertificados::class)->descifrar($cifrado);
    }

    public function test_la_boveda_exige_la_clave_dedicada(): void
    {
        config(['firma.encryption_key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('FIRMA_ENCRYPTION_KEY');

        app(BovedaCertificados::class)->cifrar('secreto');
    }

    public function test_la_envoltura_solo_abre_con_la_contrasena_del_sistema(): void
    {
        $envoltura = app(EnvolturaContrasena::class);

        $envuelta = $envoltura->envolver('clave-p12', 'MiContrasena#2026');

        $this->assertStringNotContainsString('clave-p12', $envuelta);
        $this->assertSame('clave-p12', $envoltura->desenvolver($envuelta, 'MiContrasena#2026'));

        $this->expectException(FirmaDigitalException::class);
        $envoltura->desenvolver($envuelta, 'otra-contrasena');
    }

    public function test_cada_envoltura_usa_una_sal_distinta(): void
    {
        $envoltura = app(EnvolturaContrasena::class);

        $this->assertNotSame(
            $envoltura->envolver('clave-p12', 'MiContrasena#2026'),
            $envoltura->envolver('clave-p12', 'MiContrasena#2026'),
        );
    }

    public function test_la_envoltura_conserva_sus_parametros_aunque_cambie_la_configuracion(): void
    {
        $envoltura = app(EnvolturaContrasena::class);
        $envuelta = $envoltura->envolver('clave-p12', 'MiContrasena#2026');

        config(['firma.kdf.operaciones' => 2, 'firma.kdf.memoria' => 16384]);

        $this->assertSame('clave-p12', $envoltura->desenvolver($envuelta, 'MiContrasena#2026'));
    }
}
