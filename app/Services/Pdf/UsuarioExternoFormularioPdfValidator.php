<?php

namespace App\Services\Pdf;

use App\Exceptions\UsuarioExternoFormularioPdfException;
use App\Models\UsuarioExterno;

/**
 * Comprueba que el usuario externo tenga todo lo que el formulario imprime.
 */
class UsuarioExternoFormularioPdfValidator
{
    /**
     * @throws UsuarioExternoFormularioPdfException
     */
    public function validate(UsuarioExterno $usuarioExterno): void
    {
        $errors = [];

        if ($usuarioExterno->trashed()) {
            $errors[] = 'El usuario externo está en la papelera de reciclaje.';
        }

        foreach ([
            'numero' => 'El número del formulario',
            'nombre_contacto' => 'El nombre del usuario externo',
            'empresa' => 'La empresa',
            'cargo' => 'El cargo',
            'correo' => 'El correo',
            'fecha_inicio' => 'La fecha de inicio',
        ] as $campo => $etiqueta) {
            if (blank($usuarioExterno->getAttribute($campo))) {
                $errors[] = "{$etiqueta} es obligatorio.";
            }
        }

        if ($usuarioExterno->permiso === null) {
            $errors[] = 'El permiso sobre los documentos es obligatorio.';
        }

        if ($usuarioExterno->responsable === null || $usuarioExterno->responsable->trashed()) {
            $errors[] = 'El usuario solicitante (responsable interno) no existe o está eliminado.';
        }

        if ($usuarioExterno->sitio_sharepoint_id === null && blank($usuarioExterno->onedrive)) {
            $errors[] = 'Indica un sitio SharePoint, un OneDrive o ambos.';
        }

        if ($usuarioExterno->fecha_fin !== null && $usuarioExterno->fecha_inicio !== null
            && $usuarioExterno->fecha_fin->lt($usuarioExterno->fecha_inicio)) {
            $errors[] = 'La fecha de finalización no puede ser anterior a la de inicio.';
        }

        if ($errors !== []) {
            throw UsuarioExternoFormularioPdfException::validationFailed($errors);
        }
    }
}
