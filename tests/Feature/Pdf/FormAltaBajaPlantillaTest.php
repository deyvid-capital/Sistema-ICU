<?php

namespace Tests\Feature\Pdf;

use Tests\TestCase;

class FormAltaBajaPlantillaTest extends TestCase
{
    /**
     * @param  array<string, string>  $software
     */
    private function render(string $tipo, array $software = []): string
    {
        return view('PlantillasPdf.FormAltaBaja', [
            'documento' => [
                'tipo' => $tipo,
                'fecha_alta' => '10/01/2026',
                'fecha_baja' => $tipo === 'Baja' ? '30/04/2026' : '-',
                'usuario' => [
                    'nombres' => 'Ana',
                    'apellido' => 'Pérez',
                    'carnet' => '123 LP',
                    'area' => 'Tecnología',
                    'cargo' => 'Analista',
                    'email' => 'ana@example.com',
                ],
                'equipo' => [
                    'modelo' => 'Latitude 3500',
                    'nombre' => 'BOCSIA06',
                    'service_tag' => 'DB3BMW2',
                    'fecha_entrega' => '10/01/2026',
                    'fecha_recepcion' => '-',
                    'sistema_operativo' => 'Windows 11 Pro',
                ],
                'software' => array_merge([
                    'sistemas' => 'Bflow - Usuario',
                    'microsoft' => 'Office 365 E3',
                    'sitio_sharepoint' => 'Intranet',
                    'antivirus' => '-',
                    'internet' => 'Tecnología (T)',
                    'colaboracion' => '-',
                    'escritorio_remoto' => '-',
                    'mensajeria_instantanea' => '-',
                    'unidad_compartida' => '-',
                    'pdf' => '-',
                    'vpn' => '-',
                    'navegadores' => '-',
                    'software_vpn' => '-',
                    'soporte_remoto' => '-',
                    'impresora_scaner' => '-',
                    'compresion' => '-',
                    'software_escaner' => '-',
                    'autenticacion_multifactor' => '-',
                ], $software),
            ],
        ])->render();
    }

    public function test_en_una_baja_el_listado_va_en_plomo_y_rotulado_sin_acceso(): void
    {
        $html = $this->render('Baja');

        $this->assertStringContainsString(
            '<div class="celda celda-lista celda-revocada">Bflow - Usuario</div><div class="sin-acceso">Sin acceso</div>',
            $html,
        );
    }

    public function test_en_una_baja_una_casilla_vacia_solo_muestra_sin_acceso(): void
    {
        $html = $this->render('Baja');

        $this->assertStringContainsString('<div class="sin-acceso sin-acceso-solo">Sin acceso</div>', $html);
    }

    public function test_en_un_alta_el_listado_va_en_texto_normal_y_sin_rotulo(): void
    {
        $html = $this->render('Alta');

        $this->assertStringContainsString('<div class="celda celda-lista">Bflow - Usuario</div>', $html);
        $this->assertStringNotContainsString('<div class="sin-acceso', $html);
        $this->assertStringNotContainsString('celda-lista celda-revocada', $html);
    }

    public function test_todas_las_casillas_se_recortan_para_no_desbordar(): void
    {
        $html = $this->render('Alta', [
            'sistemas' => str_repeat('Sistema con nombre largo | ', 40),
        ]);

        // Los datos del usuario y del equipo se recortan en una sola línea.
        $this->assertStringContainsString('<div class="celda celda-linea">Ana</div>', $html);
        $this->assertStringContainsString('<div class="celda celda-linea">Latitude 3500</div>', $html);

        // El listado largo entra en la casilla de listado, que está limitada por CSS.
        $this->assertStringContainsString('class="celda celda-lista"', $html);
        $this->assertStringContainsString('-webkit-line-clamp: 3;', $html);
        $this->assertStringContainsString('text-overflow: ellipsis;', $html);
    }

    public function test_los_valores_se_escapan(): void
    {
        $html = $this->render('Alta', ['sistemas' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
