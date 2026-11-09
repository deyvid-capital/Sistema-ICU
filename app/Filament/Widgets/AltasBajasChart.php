<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ConstruyeSeriesMensuales;
use App\Models\Movimiento;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

/**
 * Altas y bajas de colaboradores registradas cada mes (tabla movimientos).
 */
class AltasBajasChart extends ChartWidget
{
    use ConstruyeSeriesMensuales;
    use HasWidgetShield;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Altas y bajas por mes';

    protected ?string $description = 'Movimientos de colaboradores según su fecha.';

    protected ?string $maxHeight = '280px';

    protected ?string $pollingInterval = null;

    /**
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}
     */
    protected function getData(): array
    {
        $meses = $this->mesesDelPeriodo();

        return [
            'datasets' => [
                [
                    'label' => 'Altas',
                    'data' => $this->contarPorMes(Movimiento::query()->where('tipo', 'Alta'), 'fecha', $meses),
                    'backgroundColor' => '#22c55e',
                    'borderColor' => '#22c55e',
                ],
                [
                    'label' => 'Bajas',
                    'data' => $this->contarPorMes(Movimiento::query()->where('tipo', 'Baja'), 'fecha', $meses),
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#ef4444',
                ],
            ],
            'labels' => $this->etiquetasDeMeses($meses),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ];
    }
}
