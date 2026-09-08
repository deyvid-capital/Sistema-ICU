<?php

namespace Tests\Feature;

use App\Enums\EstadoEquipo;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use App\Models\User;
use App\Rules\EquipoDisponibleParaAsignacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class EquipoDisponibilidadAsignacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_equipment_in_maintenance(): void
    {
        $equipo = Equipo::factory()->create([
            'estado' => EstadoEquipo::EnMantenimiento,
        ]);

        $validator = Validator::make(
            ['service_tag' => $equipo->service_tag],
            ['service_tag' => [new EquipoDisponibleParaAsignacion]],
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('En mantenimiento', $validator->errors()->first('service_tag'));
    }

    public function test_rejects_equipment_in_resguardo(): void
    {
        $equipo = Equipo::factory()->create([
            'estado' => EstadoEquipo::Resguardo,
        ]);

        $validator = Validator::make(
            ['service_tag' => $equipo->service_tag],
            ['service_tag' => [new EquipoDisponibleParaAsignacion]],
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('Resguardo', $validator->errors()->first('service_tag'));
    }

    public function test_rejects_equipment_with_open_assignment_without_end_date(): void
    {
        $equipo = Equipo::factory()->create([
            'estado' => EstadoEquipo::SinAsignar,
        ]);

        AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => User::factory(),
            'fecha_fin' => null,
        ]);

        $validator = Validator::make(
            ['service_tag' => $equipo->service_tag],
            ['service_tag' => [new EquipoDisponibleParaAsignacion]],
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('sin fecha de fin', $validator->errors()->first('service_tag'));
    }

    public function test_allows_available_equipment_with_closed_previous_assignment(): void
    {
        $equipo = Equipo::factory()->create([
            'estado' => EstadoEquipo::SinAsignar,
        ]);

        AsignacionEquipo::factory()->create([
            'service_tag' => $equipo->service_tag,
            'usuario_id' => User::factory(),
            'fecha_fin' => now()->subDay()->toDateString(),
        ]);

        $validator = Validator::make(
            ['service_tag' => $equipo->service_tag],
            ['service_tag' => [new EquipoDisponibleParaAsignacion]],
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rejects_equipment_already_asignado(): void
    {
        $equipo = Equipo::factory()->create([
            'estado' => EstadoEquipo::Asignado,
        ]);

        $validator = Validator::make(
            ['service_tag' => $equipo->service_tag],
            ['service_tag' => [new EquipoDisponibleParaAsignacion]],
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('Asignado', $validator->errors()->first('service_tag'));
    }
}
