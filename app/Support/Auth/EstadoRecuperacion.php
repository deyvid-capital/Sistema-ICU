<?php

namespace App\Support\Auth;

use Illuminate\Support\Carbon;

final readonly class EstadoRecuperacion
{
    public function __construct(
        public bool $bloqueado,
        public ?Carbon $bloqueadoHasta,
        public int $segundosBloqueo,
        public int $segundosExpiracionCodigo,
        public int $segundosReenvio,
        public int $intentosRestantes,
        public bool $tieneCodigoActivo,
    ) {}

    public static function vacio(): self
    {
        return new self(
            bloqueado: false,
            bloqueadoHasta: null,
            segundosBloqueo: 0,
            segundosExpiracionCodigo: 0,
            segundosReenvio: 0,
            intentosRestantes: (int) config('acceso.recuperacion.max_intentos_codigo', 5),
            tieneCodigoActivo: false,
        );
    }
}
