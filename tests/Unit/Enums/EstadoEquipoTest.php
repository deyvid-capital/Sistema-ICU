<?php

namespace Tests\Unit\Enums;

use App\Enums\EstadoEquipo;
use PHPUnit\Framework\TestCase;

class EstadoEquipoTest extends TestCase
{
    public function test_solo_sin_asignar_esta_disponible_para_asignacion(): void
    {
        foreach (EstadoEquipo::cases() as $estado) {
            $this->assertSame(
                $estado === EstadoEquipo::SinAsignar,
                $estado->isDisponibleParaAsignacion(),
                "Estado {$estado->value}",
            );
        }
    }

    public function test_todo_lo_que_no_sea_sin_asignar_ni_asignado_esta_bloqueado(): void
    {
        $noBloqueados = [EstadoEquipo::SinAsignar, EstadoEquipo::Asignado];

        foreach (EstadoEquipo::cases() as $estado) {
            $this->assertSame(
                ! in_array($estado, $noBloqueados, true),
                $estado->estaBloqueado(),
                "Estado {$estado->value}",
            );
        }
    }

    public function test_las_bajas_incluyen_siniestros_y_depreciacion(): void
    {
        $bajas = [
            EstadoEquipo::BajaTemporal,
            EstadoEquipo::BajaDefinitiva,
            EstadoEquipo::Desarmado,
            EstadoEquipo::Robo,
            EstadoEquipo::Perdida,
            EstadoEquipo::Depreciado,
        ];

        foreach (EstadoEquipo::cases() as $estado) {
            $this->assertSame(in_array($estado, $bajas, true), $estado->esBaja(), "Estado {$estado->value}");
        }
    }

    public function test_la_baja_temporal_es_baja_pero_no_definitiva(): void
    {
        $this->assertTrue(EstadoEquipo::BajaTemporal->esBaja());
        $this->assertFalse(EstadoEquipo::BajaTemporal->esDefinitivo());

        $this->assertTrue(EstadoEquipo::BajaDefinitiva->esDefinitivo());
        $this->assertTrue(EstadoEquipo::Robo->esDefinitivo());
        $this->assertTrue(EstadoEquipo::Perdida->esDefinitivo());
        $this->assertTrue(EstadoEquipo::Desarmado->esDefinitivo());
        $this->assertTrue(EstadoEquipo::Depreciado->esDefinitivo());

        $this->assertFalse(EstadoEquipo::SinAsignar->esDefinitivo());
        $this->assertFalse(EstadoEquipo::Resguardo->esDefinitivo());
    }

    public function test_asignado_no_es_seleccionable_manualmente_pero_tampoco_bloquea(): void
    {
        $this->assertFalse(EstadoEquipo::Asignado->esSeleccionableManualmente());
        $this->assertFalse(EstadoEquipo::Asignado->estaBloqueado());
        $this->assertFalse(EstadoEquipo::Asignado->isDisponibleParaAsignacion());

        foreach (EstadoEquipo::cases() as $estado) {
            if ($estado === EstadoEquipo::Asignado) {
                continue;
            }

            $this->assertTrue($estado->esSeleccionableManualmente(), "Estado {$estado->value}");
        }
    }

    public function test_todos_los_casos_tienen_etiqueta_y_color(): void
    {
        foreach (EstadoEquipo::cases() as $estado) {
            $this->assertNotSame('', $estado->getLabel(), "Estado {$estado->value}");
            $this->assertNotNull($estado->getColor(), "Estado {$estado->value}");
        }
    }
}
