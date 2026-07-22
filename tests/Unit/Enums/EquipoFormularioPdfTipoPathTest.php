<?php

namespace Tests\Unit\Enums;

use App\Enums\EquipoFormularioPdfTipo;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EquipoFormularioPdfTipoPathTest extends TestCase
{
    #[DataProvider('pathProvider')]
    public function test_it_builds_prefixed_pdf_paths(
        EquipoFormularioPdfTipo $tipo,
        string $expectedPath,
    ): void {
        $this->assertSame($expectedPath, $tipo->pdfRelativePath('001-2026'));
    }

    /**
     * @return array<string, array{0: EquipoFormularioPdfTipo, 1: string}>
     */
    public static function pathProvider(): array
    {
        return [
            'entrega' => [EquipoFormularioPdfTipo::Entrega, 'pdf-equipos/entrega/FUE-001-2026.pdf'],
            'recepcion' => [EquipoFormularioPdfTipo::Recepcion, 'pdf-equipos/recepcion/FUR-001-2026.pdf'],
            'nota_salida' => [EquipoFormularioPdfTipo::NotaSalida, 'pdf-equipos/nota-salida/NotaSalida-001-2026.pdf'],
            'nota_recepcion' => [EquipoFormularioPdfTipo::NotaRecepcion, 'pdf-equipos/nota-recepcion/NotaRecepcion-001-2026.pdf'],
        ];
    }
}
