<?php

namespace App\Exceptions;

use App\Models\AsignacionEquipo;
use RuntimeException;

class EquipoOrigenConUsuarioException extends RuntimeException
{
    public function __construct(public AsignacionEquipo $asignacion)
    {
        parent::__construct('El equipo origen tiene un usuario asignado.');
    }
}
