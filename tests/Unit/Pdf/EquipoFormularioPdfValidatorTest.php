<?php

namespace Tests\Unit\Pdf;

use App\Enums\EquipoFormularioPdfTipo;
use App\Exceptions\EquipoFormularioPdfException;
use App\Models\AsignacionEquipo;
use App\Models\EntregaEquipo;
use App\Models\Equipo;
use App\Models\NotaSalidaEquipo;
use App\Models\User;
use App\Models\UsuarioExterno;
use App\Services\Pdf\EquipoFormularioPdfValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipoFormularioPdfValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fails_when_entrega_has_no_numero(): void
    {
        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => Equipo::factory(),
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $entrega = EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => null,
            'fecha' => now()->toDateString(),
        ]);

        $this->expectException(EquipoFormularioPdfException::class);

        app(EquipoFormularioPdfValidator::class)->validate($entrega, EquipoFormularioPdfTipo::Entrega);
    }

    public function test_it_fails_when_asignacion_is_trashed(): void
    {
        $asignacion = AsignacionEquipo::factory()->create([
            'service_tag' => Equipo::factory(),
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $entrega = EntregaEquipo::factory()->create([
            'asignacion_id' => $asignacion->id,
        ]);

        $asignacion->delete();

        try {
            app(EquipoFormularioPdfValidator::class)->validate($entrega->fresh(), EquipoFormularioPdfTipo::Entrega);
            $this->fail('Expected validation exception.');
        } catch (EquipoFormularioPdfException $exception) {
            $this->assertNotEmpty($exception->errors);
            $this->assertTrue(
                collect($exception->errors)->contains(fn (string $error): bool => str_contains($error, 'asignación')),
            );
        }
    }

    public function test_it_passes_for_valid_nota_salida(): void
    {
        $nota = NotaSalidaEquipo::factory()->create([
            'equipo_id' => Equipo::factory(),
            'usuario_externo_id' => UsuarioExterno::factory(),
        ]);

        $errors = app(EquipoFormularioPdfValidator::class)->validate($nota, EquipoFormularioPdfTipo::NotaSalida);

        $this->assertSame([], $errors);
    }
}
