<?php

namespace App\Filament\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Filtro de periodo y conteo por mes para los charts del dashboard.
 *
 * `$this->filter` lo controla el usuario (Livewire): nunca se usa directo en
 * la consulta, solo se traduce a un número de meses con un `match` cerrado.
 */
trait ConstruyeSeriesMensuales
{
    public ?string $filter = '12';

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            '6' => 'Últimos 6 meses',
            '12' => 'Últimos 12 meses',
            'anio' => 'Este año',
        ];
    }

    /**
     * Primer día de cada mes del periodo elegido, del más antiguo al actual.
     *
     * @return Collection<int, CarbonImmutable>
     */
    protected function mesesDelPeriodo(): Collection
    {
        $actual = CarbonImmutable::today()->startOfMonth();

        $cantidad = match ($this->filter) {
            '6' => 6,
            'anio' => $actual->month,
            default => 12,
        };

        return collect(range($cantidad - 1, 0))
            ->map(fn (int $atras): CarbonImmutable => $actual->subMonths($atras));
    }

    /**
     * Cuenta los registros de cada mes según una columna de fecha.
     *
     * @param  Builder<Model>  $query
     * @param  Collection<int, CarbonImmutable>  $meses
     * @return list<int>
     */
    protected function contarPorMes(Builder $query, string $columnaFecha, Collection $meses): array
    {
        $totales = $query
            ->whereBetween($columnaFecha, [
                $meses->first()->toDateString(),
                $meses->last()->endOfMonth()->toDateString(),
            ])
            ->selectRaw("to_char({$columnaFecha}, 'YYYY-MM') as mes, count(*) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        return $meses
            ->map(fn (CarbonImmutable $mes): int => (int) ($totales[$mes->format('Y-m')] ?? 0))
            ->all();
    }

    /**
     * @param  Collection<int, CarbonImmutable>  $meses
     * @return list<string>
     */
    protected function etiquetasDeMeses(Collection $meses): array
    {
        return $meses
            ->map(fn (CarbonImmutable $mes): string => ucfirst($mes->locale('es')->translatedFormat('M Y')))
            ->all();
    }
}
