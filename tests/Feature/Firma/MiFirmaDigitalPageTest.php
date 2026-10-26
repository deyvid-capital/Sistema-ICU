<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Filament\Pages\MiFirmaDigital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\TestCase;

class MiFirmaDigitalPageTest extends TestCase
{
    use FakeFirmador;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
    }

    private function entrarComo(User $usuario): void
    {
        $usuario->assignRole(Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']));
        $this->actingAs($usuario);
    }

    public function test_cualquier_usuario_con_firma_ve_su_pagina(): void
    {
        $titular = $this->titular();
        $this->certificadoActivo($titular, ['titular_cn' => 'VLADIMIR ADALID CALDERON SARMIENTO']);
        $this->entrarComo($titular);

        $this->get(MiFirmaDigital::getUrl(panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('VLADIMIR ADALID CALDERON SARMIENTO');

        $this->assertTrue(MiFirmaDigital::tieneFirmaAsignada());
    }

    public function test_el_menu_solo_aparece_si_tiene_firma_asignada(): void
    {
        $this->entrarComo($this->titular());

        $this->assertFalse(MiFirmaDigital::tieneFirmaAsignada());
    }

    public function test_activar_la_firma_con_el_asistente_de_dos_pasos(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $certificado = $this->certificadoPendiente($titular);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->assertSee('Tienes una firma digital pendiente de activar')
            ->assertActionVisible('activar')
            ->assertActionHidden('probar')
            ->callAction('activar', [
                'contrasena_p12' => self::CONTRASENA_P12,
                'contrasena_sistema' => self::CONTRASENA_SISTEMA,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified('Firma digital activada');

        $this->assertTrue($certificado->refresh()->puedeFirmar());
    }

    public function test_el_paso_del_p12_detecta_que_escribio_la_contrasena_del_sistema(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $this->certificadoPendiente($titular);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->callAction('activar', [
                'contrasena_p12' => self::CONTRASENA_SISTEMA,
                'contrasena_sistema' => self::CONTRASENA_SISTEMA,
            ])
            ->assertHasFormErrors(['contrasena_p12']);

        $this->assertSame(EstadoCertificadoFirma::PendienteActivacion, $titular->certificadosFirma()->sole()->estado);
    }

    public function test_el_paso_del_sistema_valida_la_contrasena_actual(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $this->certificadoPendiente($titular);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->callAction('activar', [
                'contrasena_p12' => self::CONTRASENA_P12,
                'contrasena_sistema' => 'no-es-la-mia',
            ])
            ->assertHasFormErrors(['contrasena_sistema']);
    }

    public function test_una_firma_en_pausa_muestra_reactivar(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $this->certificadoActivo($titular, [
            'estado' => EstadoCertificadoFirma::RequiereReactivacion,
            'contrasena_p12_envuelta' => null,
        ]);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->assertSee('Tu firma está en pausa')
            ->assertActionHasLabel('activar', 'Reactivar mi firma')
            ->callAction('activar', [
                'contrasena_p12' => self::CONTRASENA_P12,
                'contrasena_sistema' => self::CONTRASENA_SISTEMA,
            ])
            ->assertNotified('Firma digital activada');
    }

    public function test_la_renovacion_pendiente_se_activa_aunque_la_actual_siga_activa(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $actual = $this->certificadoActivo($titular, ['huella_sha256' => str_repeat('b', 64)]);
        $renovacion = $this->certificadoPendiente($titular, ['reemplaza_a_id' => $actual->id]);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->assertActionHasLabel('activar', 'Activar renovación')
            ->assertActionVisible('probar')
            ->callAction('activar', [
                'contrasena_p12' => self::CONTRASENA_P12,
                'contrasena_sistema' => self::CONTRASENA_SISTEMA,
            ]);

        $this->assertSame(EstadoCertificadoFirma::Activo, $renovacion->refresh()->estado);
        $this->assertSame(EstadoCertificadoFirma::Reemplazado, $actual->refresh()->estado);
    }

    public function test_probar_mi_firma_descarga_un_pdf_firmado_con_la_contrasena_del_sistema(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $this->certificadoActivo($titular, ['nombre_en_sello' => 'Vladimir Adalid Calderón Sarmiento']);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->callAction('probar', ['contrasena_sistema' => self::CONTRASENA_SISTEMA])
            ->assertFileDownloaded('prueba-firma-digital.pdf', '%PDF-preparado +firma_prueba');

        // Al firmador le llega la contraseña del .p12 (desenvuelta), nunca la del sistema.
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/pdf/firmar')
            && $this->campoMultipart($request, 'contrasena') === self::CONTRASENA_P12
            && json_decode((string) $this->campoMultipart($request, 'sello'), true)['nombre'] === 'Vladimir Adalid Calderón Sarmiento');
    }

    public function test_probar_mi_firma_con_contrasena_incorrecta(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $this->certificadoActivo($titular);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->callAction('probar', ['contrasena_sistema' => 'otra'])
            ->assertNotified('No se pudo firmar');

        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/pdf/firmar'));
    }
}
