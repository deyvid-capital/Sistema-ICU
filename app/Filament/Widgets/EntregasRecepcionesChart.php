<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ConstruyeSeriesMensuales;
use App\Models\EntregaEquipo;
use App\Models\RecepcionEquipo;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

/**
 * Entregas de equipos a colaboradores frente a recepciones (devoluciones) por mes.
 */
class EntregasRecepcionesChart extends ChartWidget
{
    use ConstruyeSeriesMensuales;
    use HasWidgetShield;

    protected static ?int $sort = 5;

    protected ?string $heading = 'Entregas y recepciones de equipos';

    protected ?string $description = 'Equipos entregados y devueltos cada mes.';

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
                    'label' => 'Entregas',
                    'data' => $this->contarPorMes(EntregaEquipo::query(), 'fecha', $meses),
                    'borderColor' => '#0ea5e9',
                    'backgroundColor' => 'rgba(14, 165, 233, 0.15)',
                    'fill' => true,
                ],
                [
                    'label' => 'Recepciones',
                    'data' => $this->contarPorMes(RecepcionEquipo::query(), 'fecha', $meses),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $this->etiquetasDeMeses($meses),
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
