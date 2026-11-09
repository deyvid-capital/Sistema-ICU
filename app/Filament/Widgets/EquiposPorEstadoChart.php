<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoEquipo;
use App\Models\Equipo;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\ChartWidget;

/**
 * Reparto del inventario de equipos según su EstadoEquipo.
 */
class EquiposPorEstadoChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Equipos por estado';

    protected ?string $description = 'Cuántos equipos hay en cada estado del inventario.';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    protected ?string $emptyStateHeading = 'Sin equipos registrados';

    protected string|BackedEnum|null $emptyStateIcon = Heroicon::OutlinedChartPie;

    /**
     * Un color distinto por estado: en una dona dos porciones del mismo color
     * no se pueden distinguir, por eso no se reutiliza EstadoEquipo::getColor().
     *
     * @var array<string, string>
     */
    private const COLORES = [
        'sin asignar' => '#0ea5e9',
        'asignado' => '#22c55e',
        'en mantenimiento' => '#a855f7',
        'resguardo' => '#64748b',
        'baja temporal' => '#f59e0b',
        'baja definitiva' => '#ef4444',
        'desarmado' => '#78716c',
        'robo' => '#be123c',
        'perdida' => '#f97316',
        'depreciado' => '#94a3b8',
    ];

    /**
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}|array{}
     */
    protected function getData(): array
    {
        $porEstado = Equipo::query()
            ->toBase()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        if ($porEstado->isEmpty()) {
            return [];
        }

        $estados = collect(EstadoEquipo::cases())
            ->filter(fn (EstadoEquipo $estado): bool => isset($porEstado[$estado->value]))
            ->values();

        return [
            'datasets' => [
                [
                    'label' => 'Equipos',
                    'data' => $estados->map(fn (EstadoEquipo $estado): int => (int) $porEstado[$estado->value])->all(),
                    'backgroundColor' => $estados->map(fn (EstadoEquipo $estado): string => self::COLORES[$estado->value])->all(),
                ],
            ],
            'labels' => $estados->map(fn (EstadoEquipo $estado): string => $estado->getLabel())->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
            'plugins' => [
                'legend' => ['position' => 'right'],
            ],
        ];
    }
}
