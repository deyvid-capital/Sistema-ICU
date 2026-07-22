<?php

namespace Tests\Feature\Filament;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\Concerns\EquipoFormularioPdfActions;
use Tests\TestCase;

class NotaRecepcionFirmanteActionTest extends TestCase
{
    public function test_nota_recepcion_generate_action_shows_form_instead_of_confirmation(): void
    {
        $action = EquipoFormularioPdfActions::generate(EquipoFormularioPdfTipo::NotaRecepcion);

        // El firmante se captura en un modal con formulario; requiresConfirmation()
        // ocultaría los campos, por eso no debe estar activo para este tipo.
        $this->assertFalse($action->isConfirmationRequired());
    }

    public function test_other_generate_actions_keep_confirmation_modal(): void
    {
        foreach ([
            EquipoFormularioPdfTipo::Entrega,
            EquipoFormularioPdfTipo::Recepcion,
            EquipoFormularioPdfTipo::NotaSalida,
        ] as $tipo) {
            $action = EquipoFormularioPdfActions::generate($tipo);

            $this->assertTrue(
                $action->isConfirmationRequired(),
                "El tipo {$tipo->value} debe conservar el modal de confirmación.",
            );
        }
    }
}
