<?php

namespace Tests\Unit\Services\Formularios;

use App\Models\EntregaEquipo;
use App\Models\RecepcionEquipo;
use App\Services\Formularios\FormularioEquipoNumeroGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormularioEquipoNumeroGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_starts_at_001_for_empty_year(): void
    {
        $generator = app(FormularioEquipoNumeroGenerator::class);

        $this->assertSame('001-2026', $generator->next(EntregaEquipo::class, 2026));
    }

    public function test_it_increments_per_model_and_year(): void
    {
        EntregaEquipo::factory()->create(['numero' => '001-2026']);
        EntregaEquipo::factory()->create(['numero' => '002-2026']);
        EntregaEquipo::factory()->create(['numero' => '001-2025']);
        RecepcionEquipo::factory()->create(['numero' => '005-2026']);

        $generator = app(FormularioEquipoNumeroGenerator::class);

        $this->assertSame('003-2026', $generator->next(EntregaEquipo::class, 2026));
        $this->assertSame('002-2025', $generator->next(EntregaEquipo::class, 2025));
        $this->assertSame('006-2026', $generator->next(RecepcionEquipo::class, 2026));
    }

    public function test_it_includes_soft_deleted_numbers_in_sequence(): void
    {
        $entrega = EntregaEquipo::factory()->create(['numero' => '001-2026']);
        $entrega->delete();

        $generator = app(FormularioEquipoNumeroGenerator::class);

        $this->assertSame('002-2026', $generator->next(EntregaEquipo::class, 2026));
    }

    public function test_it_validates_format_and_resuggest_rules(): void
    {
        $generator = app(FormularioEquipoNumeroGenerator::class);

        $this->assertTrue($generator->matchesFormat('001-2026'));
        $this->assertFalse($generator->matchesFormat('ENT-001'));
        $this->assertSame(2026, $generator->yearFromNumero('012-2026'));
        $this->assertTrue($generator->shouldResuggest('001-2025', 2025, 2026));
        $this->assertFalse($generator->shouldResuggest('099-2026', 2025, 2026));
        $this->assertFalse($generator->shouldResuggest('001-2026', 2026, 2026));
    }
}
