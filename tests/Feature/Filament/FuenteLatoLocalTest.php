<?php

namespace Tests\Feature\Filament;

use Filament\Facades\Filament;
use Filament\FontProviders\LocalFontProvider;
use Tests\TestCase;

class FuenteLatoLocalTest extends TestCase
{
    public function test_el_panel_usa_lato_como_fuente_por_defecto(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertSame('Lato', $panel->getFontFamily());
        $this->assertSame(LocalFontProvider::class, $panel->getFontProvider());
    }

    public function test_el_login_sirve_lato_sin_pedir_cdn_externos(): void
    {
        $respuesta = $this->get('/admin/login')->assertSuccessful();

        $respuesta->assertSee("--font-family: 'Lato'", false);
        $respuesta->assertDontSee('Roboto Mono', false);
        $respuesta->assertDontSee('fonts.bunny.net', false);
        $respuesta->assertDontSee('fonts.googleapis.com', false);
        $respuesta->assertDontSee('fonts.gstatic.com', false);
    }
}
