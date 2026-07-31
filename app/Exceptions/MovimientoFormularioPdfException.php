<?php

namespace App\Exceptions;

use Exception;

class MovimientoFormularioPdfException extends Exception
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        string $message,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param  list<string>  $errors
     */
    public static function validationFailed(array $errors): self
    {
        return new self(
            'No se puede generar el PDF: faltan datos obligatorios.',
            $errors,
        );
    }
}
