<?php

namespace App\Support\Auth;

use Illuminate\Support\Carbon;

final readonly class ResultadoRestablecer
{
    public function __construct(
        public bool $ok,
        public string $mensaje,
        public bool $bloqueado = false,
        public ?Carbon $bloqueadoHasta = null,
        public ?int $intentosRestantes = null,
    ) {}
}
