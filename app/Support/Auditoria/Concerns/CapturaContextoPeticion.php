<?php

namespace App\Support\Auditoria\Concerns;

use Illuminate\Support\Str;

trait CapturaContextoPeticion
{
    /**
     * Origen de la petición que provocó el evento.
     *
     * En consola y en colas `request()` existe igualmente, pero devuelve un
     * 127.0.0.1 y un «Symfony» falsos: ahí se registra null. Durante los tests
     * sí se captura, porque la petición simulada es el cliente real del caso.
     *
     * @return array{ip: string|null, user_agent: string|null}
     */
    protected function contextoPeticion(): array
    {
        $enConsola = app()->runningInConsole() && ! app()->runningUnitTests();

        if ($enConsola || ! app()->bound('request')) {
            return ['ip' => null, 'user_agent' => null];
        }

        $peticion = request();

        $userAgent = $peticion->userAgent();

        return [
            'ip' => $peticion->ip(),
            'user_agent' => filled($userAgent) ? Str::limit($userAgent, 500, '') : null,
        ];
    }
}
