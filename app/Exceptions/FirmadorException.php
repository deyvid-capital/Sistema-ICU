<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Error devuelto por el servicio firmador. El código es estable y permite
 * mostrar mensajes de negocio (contraseña incorrecta, PDF cifrado, etc.).
 */
class FirmadorException extends Exception
{
    public const CONTRASENA_INCORRECTA = 'contrasena_incorrecta';

    public const P12_INVALIDO = 'p12_invalido';

    public const P12_SIN_CLAVE = 'p12_sin_clave';

    public const CERTIFICADO_VENCIDO = 'certificado_vencido';

    public const PDF_INVALIDO = 'pdf_invalido';

    public const PDF_CIFRADO = 'pdf_cifrado';

    public const CAMPO_INEXISTENTE = 'campo_inexistente';

    public const CAMPO_YA_FIRMADO = 'campo_ya_firmado';

    public const NO_DISPONIBLE = 'no_disponible';

    public const ERROR_INTERNO = 'error_interno';

    public function __construct(
        public readonly string $codigo,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function fromResponse(Response $response): self
    {
        $codigo = $response->json('codigo');
        $mensaje = $response->json('mensaje');

        if (! is_string($codigo) || ! is_string($mensaje)) {
            return new self(self::ERROR_INTERNO, 'El servicio de firma respondió con un error inesperado.');
        }

        return new self($codigo, $mensaje);
    }

    public static function noDisponible(): self
    {
        return new self(self::NO_DISPONIBLE, 'El servicio de firma no está disponible. Intenta nuevamente en unos minutos.');
    }

    public function esContrasenaIncorrecta(): bool
    {
        return $this->codigo === self::CONTRASENA_INCORRECTA;
    }
}
