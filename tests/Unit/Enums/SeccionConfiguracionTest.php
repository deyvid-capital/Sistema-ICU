<?php

namespace Tests\Unit\Enums;

use App\Enums\SeccionConfiguracion;
use Tests\TestCase;

class SeccionConfiguracionTest extends TestCase
{
    public function test_cada_seccion_tiene_etiqueta_y_descripciones(): void
    {
        foreach (SeccionConfiguracion::cases() as $seccion) {
            $this->assertNotSame('', $seccion->getLabel());
            $this->assertNotSame('', $seccion->descripcionActualizado());
            $this->assertNotSame('', $seccion->descripcionRestaurado());
        }
    }

    public function test_conserva_la_descripcion_historica_del_correo(): void
    {
        $this->assertSame(
            'Configuración de correo actualizada',
            SeccionConfiguracion::Correo->descripcionActualizado(),
            'Cambiarla dejaría las filas históricas con otra descripción.',
        );
    }

    public function test_las_opciones_cubren_todos_los_casos(): void
    {
        $this->assertCount(count(SeccionConfiguracion::cases()), SeccionConfiguracion::opciones());
    }
}
