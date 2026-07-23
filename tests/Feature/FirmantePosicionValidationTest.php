<?php

namespace Tests\Feature;

use App\Enums\FirmantePosicion;
use App\Models\EntregaCelular;
use App\Models\EntregaEquipo;
use App\Models\Firmante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FirmantePosicionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_entrega_equipo_rejects_centro_position(): void
    {
        $entrega = EntregaEquipo::factory()->create();
        $usuario = User::factory()->create();

        $this->expectException(ValidationException::class);

        Firmante::query()->create([
            'usuario_id' => $usuario->id,
            'fecha' => now(),
            'posicion' => FirmantePosicion::Centro,
            'documento_id' => $entrega->id,
            'documento_type' => EntregaEquipo::class,
        ]);
    }

    public function test_entrega_equipo_allows_two_signers_and_rejects_third(): void
    {
        $entrega = EntregaEquipo::factory()->create();
        $usuarios = User::factory()->count(3)->create();

        Firmante::query()->create([
            'usuario_id' => $usuarios[0]->id,
            'fecha' => now(),
            'posicion' => FirmantePosicion::Izquierda,
            'documento_id' => $entrega->id,
            'documento_type' => EntregaEquipo::class,
        ]);

        Firmante::query()->create([
            'usuario_id' => $usuarios[1]->id,
            'fecha' => now(),
            'posicion' => FirmantePosicion::Derecha,
            'documento_id' => $entrega->id,
            'documento_type' => EntregaEquipo::class,
        ]);

        $this->expectException(ValidationException::class);

        Firmante::query()->create([
            'usuario_id' => $usuarios[2]->id,
            'fecha' => now(),
            'posicion' => FirmantePosicion::Izquierda,
            'documento_id' => $entrega->id,
            'documento_type' => EntregaEquipo::class,
        ]);
    }

    public function test_entrega_celular_allows_three_positions(): void
    {
        $entrega = EntregaCelular::factory()->create();
        $usuarios = User::factory()->count(3)->create();

        foreach ([
            FirmantePosicion::Izquierda,
            FirmantePosicion::Centro,
            FirmantePosicion::Derecha,
        ] as $index => $posicion) {
            $firmante = Firmante::query()->create([
                'usuario_id' => $usuarios[$index]->id,
                'fecha' => now(),
                'posicion' => $posicion,
                'documento_id' => $entrega->id,
                'documento_type' => EntregaCelular::class,
            ]);

            $this->assertTrue($firmante->exists);
        }

        $this->assertSame(3, $entrega->firmantes()->count());
    }

    public function test_duplicate_position_on_same_document_is_rejected(): void
    {
        $entrega = EntregaCelular::factory()->create();
        $usuarios = User::factory()->count(2)->create();

        Firmante::query()->create([
            'usuario_id' => $usuarios[0]->id,
            'fecha' => now(),
            'posicion' => FirmantePosicion::Izquierda,
            'documento_id' => $entrega->id,
            'documento_type' => EntregaCelular::class,
        ]);

        $this->expectException(ValidationException::class);

        Firmante::query()->create([
            'usuario_id' => $usuarios[1]->id,
            'fecha' => now(),
            'posicion' => FirmantePosicion::Izquierda,
            'documento_id' => $entrega->id,
            'documento_type' => EntregaCelular::class,
        ]);
    }
}
