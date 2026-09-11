<?php

namespace Tests\Unit\Enums;

use App\Enums\EventoSeguridad;
use App\Enums\ResultadoSeguridad;
use Tests\TestCase;

class EventoSeguridadTest extends TestCase
{
    public function test_cada_evento_tiene_etiqueta_color_icono_y_resultado(): void
    {
        foreach (EventoSeguridad::cases() as $evento) {
            $this->assertNotSame('', $evento->getLabel(), "{$evento->value} no tiene etiqueta.");
            $this->assertNotSame('', $evento->getColor(), "{$evento->value} no tiene color.");
            $this->assertNotSame('', $evento->getIcon()->value, "{$evento->value} no tiene icono.");
            $this->assertInstanceOf(ResultadoSeguridad::class, $evento->resultado());
        }
    }

    public function test_las_opciones_cubren_todos_los_casos(): void
    {
        $opciones = EventoSeguridad::opciones();

        $this->assertCount(count(EventoSeguridad::cases()), $opciones);

        foreach (EventoSeguridad::cases() as $evento) {
            $this->assertArrayHasKey($evento->value, $opciones);
            $this->assertSame($evento->getLabel(), $opciones[$evento->value]);
        }
    }

    public function test_los_resultados_particionan_todos_los_eventos(): void
    {
        $agrupados = [];

        foreach (ResultadoSeguridad::cases() as $resultado) {
            $agrupados = array_merge($agrupados, EventoSeguridad::valoresPorResultado($resultado));
        }

        $esperados = array_map(fn (EventoSeguridad $e): string => $e->value, EventoSeguridad::cases());

        sort($agrupados);
        sort($esperados);

        $this->assertSame($esperados, $agrupados, 'Los buckets de resultado deben cubrir cada evento exactamente una vez.');
    }

    public function test_cada_evento_tiene_traduccion_de_vendor(): void
    {
        foreach (EventoSeguridad::cases() as $evento) {
            $clave = 'activitylog::action.event.'.$evento->value;

            $this->assertTrue(
                trans()->has($clave),
                "Falta la traducción {$clave} en lang/vendor/activitylog/es/action.php.",
            );
        }
    }

    public function test_las_traducciones_del_vendor_siguen_disponibles(): void
    {
        foreach (['created', 'updated', 'deleted', 'restored'] as $evento) {
            $this->assertTrue(trans()->has('activitylog::action.event.'.$evento));
        }
    }
}
