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

    public function test_un_p12_pendiente_del_flujo_anterior_espera_al_admin(): void
    {
        $titular = $this->titular();
        $this->certificadoPendiente($titular);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->assertSee('Tu firma digital está pendiente de activación')
            ->assertActionHidden('vincular')
            ->assertActionHidden('probar');
    }

    public function test_una_firma_activada_por_el_admin_se_vincula_con_la_contrasena_del_sistema(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoPendienteDeVincular($titular);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->assertSee('Tu firma está lista')
            ->assertActionHasLabel('vincular', 'Vincular mi firma')
            ->assertActionVisible('probar')
            ->callAction('vincular', ['contrasena_sistema' => self::CONTRASENA_SISTEMA])
            ->assertHasNoFormErrors()
            ->assertNotified('Firma digital lista');

        $this->assertFalse($certificado->refresh()->estaPendienteDeVincular());
        $this->assertTrue($certificado->puedeFirmar());
    }

    public function test_vincular_valida_la_contrasena_actual(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoPendienteDeVincular($titular);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->callAction('vincular', ['contrasena_sistema' => 'no-es-la-mia'])
            ->assertHasFormErrors(['contrasena_sistema']);

        $this->assertTrue($certificado->refresh()->estaPendienteDeVincular());
    }

    public function test_una_firma_ya_vinculada_no_muestra_vincular(): void
    {
        $titular = $this->titular();
        $this->certificadoActivo($titular);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)->assertActionHidden('vincular');
    }

    public function test_una_firma_en_pausa_se_reactiva_solo_con_la_nueva_contrasena(): void
    {
        $titular = $this->titular();
        $certificado = $this->certificadoActivo($titular, [
            'estado' => EstadoCertificadoFirma::RequiereReactivacion,
            'contrasena_p12_envuelta' => null,
        ]);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->assertSee('Tu firma está en pausa')
            ->assertActionHasLabel('vincular', 'Reactivar mi firma')
            ->callAction('vincular', ['contrasena_sistema' => self::CONTRASENA_SISTEMA])
            ->assertNotified('Firma digital lista');

        $this->assertSame(EstadoCertificadoFirma::Activo, $certificado->refresh()->estado);
    }

    public function test_una_firma_en_pausa_sin_custodia_la_reactiva_el_admin(): void
    {
        $titular = $this->titular();
        $this->certificadoActivo($titular, [
            'estado' => EstadoCertificadoFirma::RequiereReactivacion,
            'contrasena_p12_envuelta' => null,
            'contrasena_p12_custodia' => null,
        ]);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->assertSee('Un administrador debe reactivar tu firma')
            ->assertActionHidden('vincular');
    }

    public function test_probar_mi_firma_vincula_una_firma_recien_activada(): void
    {
        $this->fakeFirmador();
        $titular = $this->titular();
        $certificado = $this->certificadoPendienteDeVincular($titular);
        $this->entrarComo($titular);

        Livewire::test(MiFirmaDigital::class)
            ->callAction('probar', ['contrasena_sistema' => self::CONTRASENA_SISTEMA])
            ->assertFileDownloaded('prueba-firma-digital.pdf');

        $this->assertFalse($certificado->refresh()->estaPendienteDeVincular());
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
