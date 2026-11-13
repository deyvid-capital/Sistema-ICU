<?php

namespace App\Services\Movimientos;

/**
 * Lo que se le devuelve (o se le devolvería) a un colaborador que reingresa.
 */
final readonly class ResumenReasignacion
{
    /**
     * @param  list<string>  $omitidos  Lo que no se pudo devolver porque hoy lo tiene otra persona o no está disponible.
     */
    public function __construct(
        public int $accesos = 0,
        public int $equipos = 0,
        public int $cargadores = 0,
        public int $celulares = 0,
        public int $excepcionesSoftware = 0,
        public array $omitidos = [],
    ) {}

    public function totalAsignaciones(): int
    {
        return $this->equipos + $this->cargadores + $this->celulares + $this->excepcionesSoftware;
    }

    public function total(): int
    {
        return $this->accesos + $this->totalAsignaciones();
    }

    public function hayAlgo(): bool
    {
        return $this->total() > 0;
    }

    /**
     * "3 accesos, 1 equipo y 1 celular", o cadena vacía si no hay nada.
     */
    public function detalle(): string
    {
        $partes = array_filter([
            $this->contar($this->accesos, 'acceso', 'accesos'),
            $this->contar($this->equipos, 'equipo', 'equipos'),
            $this->contar($this->cargadores, 'cargador', 'cargadores'),
            $this->contar($this->celulares, 'celular', 'celulares'),
            $this->contar($this->excepcionesSoftware, 'excepción de software', 'excepciones de software'),
        ]);

        if ($partes === []) {
            return '';
        }

        $ultima = array_pop($partes);

        return $partes === [] ? $ultima : implode(', ', $partes).' y '.$ultima;
    }

    /**
     * Aviso de lo que quedó fuera, o null si se devolvió todo.
     */
    public function avisoDeOmitidos(): ?string
    {
        if ($this->omitidos === []) {
            return null;
        }

        return 'No se devolvió porque hoy lo tiene otra persona o no está disponible: '.implode(', ', $this->omitidos).'.';
    }

    private function contar(int $cantidad, string $singular, string $plural): ?string
    {
        return match (true) {
            $cantidad === 0 => null,
            $cantidad === 1 => "1 {$singular}",
            default => "{$cantidad} {$plural}",
        };
    }
}
