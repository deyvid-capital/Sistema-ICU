<?php

namespace Tests\Unit\Services;

use App\Models\AppearanceSetting;
use App\Services\Configuracion\TemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemaServiceTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): TemaService
    {
        // Instancia limpia: el servicio memoriza la resolución por request.
        return new TemaService;
    }

    public function test_sin_configuracion_guardada_usa_el_tema_por_defecto(): void
    {
        $resolucion = $this->servicio()->resolver();

        $this->assertSame('safi', $resolucion['tema']);
        $this->assertSame('#003973', $resolucion['primario']);
        $this->assertSame('#003973', $resolucion['sidebar']['claro']);
        $this->assertSame('#FFFFFF', $resolucion['topbar']['claro']);
    }

    public function test_un_tema_curado_ignora_los_colores_personalizados_guardados(): void
    {
        AppearanceSetting::create([
            'tema' => 'turquesa',
            'color_primario' => '#FF00FF',
            'color_sidebar_claro' => '#FF00FF',
        ]);

        $resolucion = $this->servicio()->resolver();

        $this->assertSame('#008A83', $resolucion['primario']);
        $this->assertSame('#006B96', $resolucion['sidebar']['claro']);
    }

    public function test_el_tema_personalizado_respeta_los_colores_guardados(): void
    {
        AppearanceSetting::create([
            'tema' => TemaService::TEMA_PERSONALIZADO,
            'color_primario' => '#13A538',
            'color_gris' => 'Zinc',
            'color_sidebar_claro' => '#0081C1',
            'color_topbar_claro' => '#FFFFFF',
        ]);

        $servicio = $this->servicio();
        $resolucion = $servicio->resolver();

        $this->assertSame('#13A538', $resolucion['primario']);
        $this->assertSame('#0081C1', $resolucion['sidebar']['claro']);
        $this->assertSame('#13A538', $servicio->coloresDelPanel()['primary']);
        $this->assertIsArray($servicio->coloresDelPanel()['gray']);
    }

    public function test_el_texto_de_la_barra_se_elige_por_contraste(): void
    {
        $servicio = $this->servicio();

        $this->assertSame('#FFFFFF', $servicio->tokensDeBarra('#003973')['fg']);
        $this->assertSame('var(--gray-950)', $servicio->tokensDeBarra('#FFFFFF')['fg']);
    }

    public function test_el_item_activo_contrasta_con_el_fondo_de_la_barra(): void
    {
        $servicio = $this->servicio();

        $sobreOscuro = $servicio->tokensDeBarra('#003973', '#003973');
        $sobreClaro = $servicio->tokensDeBarra('#FFFFFF', '#003973');

        $this->assertMatchesRegularExpression('/^var\(--primary-\d+\)$/', $sobreOscuro['activo']);
        $this->assertMatchesRegularExpression('/^var\(--primary-\d+\)$/', $sobreClaro['activo']);
        $this->assertNotSame($sobreOscuro['activo'], $sobreClaro['activo']);
    }

    public function test_un_color_invalido_no_llega_a_la_hoja_de_estilos(): void
    {
        $tokens = $this->servicio()->tokensDeBarra('rojo; background: url(x)');

        $this->assertSame('#FFFFFF', $tokens['bg']);
    }

    public function test_guardar_la_configuracion_invalida_la_cache(): void
    {
        $servicio = $this->servicio();
        $this->assertSame('#003973', $servicio->resolver()['primario']);

        AppearanceSetting::create(['tema' => 'confianza']);

        $this->assertSame('#0081C1', $this->servicio()->resolver()['primario']);
    }

    public function test_la_placa_del_logo_se_activa_solo_cuando_hace_falta(): void
    {
        // Tema safi: sidebar oscuro y barra superior blanca.
        $placa = $this->servicio()->placaDeLogo();
        $this->assertTrue($placa['claro']['sidebar']);
        $this->assertFalse($placa['claro']['topbar']);

        // Tema confianza: al revés, la barra superior es la oscura.
        AppearanceSetting::create(['tema' => 'confianza']);
        $placa = $this->servicio()->placaDeLogo();
        $this->assertFalse($placa['claro']['sidebar']);
        $this->assertTrue($placa['claro']['topbar']);
    }

    public function test_un_logo_propio_desactiva_la_placa_automatica(): void
    {
        AppearanceSetting::create([
            'tema' => 'safi',
            'logo_claro' => 'apariencia/logo.png',
        ]);

        $this->assertFalse($this->servicio()->placaDeLogo()['claro']['sidebar']);
    }

    public function test_la_placa_se_puede_forzar_o_desactivar(): void
    {
        $configuracion = AppearanceSetting::create([
            'tema' => 'safi',
            'logo_claro' => 'apariencia/logo.png',
            'fondo_logo' => 'claro',
        ]);

        $this->assertTrue($this->servicio()->placaDeLogo()['claro']['sidebar']);

        $configuracion->update(['fondo_logo' => 'ninguno']);

        $this->assertFalse($this->servicio()->placaDeLogo()['claro']['sidebar']);
    }

    public function test_el_logo_y_el_favicon_caen_a_los_archivos_de_la_aplicacion(): void
    {
        $servicio = $this->servicio();

        $this->assertStringContainsString('Capital-Safi-SA-logo-.png', (string) $servicio->logoClaro());
        $this->assertNull($servicio->logoOscuro());
        $this->assertStringContainsString('favicon.ico', (string) $servicio->favicon());
        $this->assertSame('2.5rem', $servicio->altoLogo());
    }
}
