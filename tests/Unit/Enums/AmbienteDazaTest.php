<?php

namespace Tests\Unit\Enums;

use App\Enums\AmbienteDaza;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AmbienteDazaTest extends TestCase
{
    public function test_cada_ambiente_tiene_su_abreviatura(): void
    {
        $this->assertSame('DazProd', AmbienteDaza::Produccion->abreviatura());
        $this->assertSame('DazCont', AmbienteDaza::Contingencia->abreviatura());
        $this->assertSame('DazTest', AmbienteDaza::Test->abreviatura());
    }

    /**
     * @return array<string, array{0: ?string, 1: ?AmbienteDaza}>
     */
    public static function textos(): array
    {
        return [
            'producción con tilde' => ['Producción', AmbienteDaza::Produccion],
            'produccion sin tilde' => ['produccion', AmbienteDaza::Produccion],
            'contingencia' => [' CONTINGENCIA ', AmbienteDaza::Contingencia],
            'test' => ['Test', AmbienteDaza::Test],
            'pruebas (nombre histórico)' => ['Pruebas', AmbienteDaza::Test],
            'desconocido' => ['Desarrollo', null],
            'vacío' => ['', null],
            'nulo' => [null, null],
        ];
    }

    #[DataProvider('textos')]
    public function test_resuelve_el_ambiente_desde_el_texto_guardado(?string $texto, ?AmbienteDaza $esperado): void
    {
        $this->assertSame($esperado, AmbienteDaza::tryFromTexto($texto));
    }
}
