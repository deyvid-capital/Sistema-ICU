<?php

namespace App\Filament\Widgets;

use App\Models\Licencia;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\ChartWidget;

/**
 * Cupos asignados frente a libres de cada licencia activa. Si una licencia
 * tiene más asignaciones que cupos, el exceso se muestra aparte en rojo.
 */
class UsoLicenciasChart extends ChartWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Uso de licencias';

    protected ?string $description = 'Cupos asignados y libres de cada licencia activa.';

    protected ?string $maxHeight = '420px';

    protected ?string $pollingInterval = null;

    protected ?string $emptyStateHeading = 'Sin licencias activas';

    protected string|BackedEnum|null $emptyStateIcon = Heroicon::OutlinedChartBar;

    /**
     * @return array{datasets: list<array<string, mixed>>, labels: list<string>}|array{}
     */
    protected function getData(): array
    {
        $licencias = Licencia::query()
            ->where('estado', true)
            ->withCount('licenciasUsuariosActivas')
            ->orderBy('nombre_licencia')
            ->get(['id', 'nombre_licencia', 'total_licencias']);

        if ($licencias->isEmpty()) {
            return [];
        }

        $asignadas = $licencias->map(fn (Licencia $licencia): int => min($licencia->asignadas, (int) $licencia->total_licencias));
        $libres = $licencias->map(fn (Licencia $licencia): int => $licencia->disponibles);
        $excedidas = $licencias->map(fn (Licencia $licencia): int => max($licencia->asignadas - (int) $licencia->total_licencias, 0));

        $datasets = [
            ['label' => 'Asignadas', 'data' => $asignadas->all(), 'backgroundColor' => '#0ea5e9'],
            ['label' => 'Libres', 'data' => $libres->all(), 'backgroundColor' => '#cbd5e1'],
        ];

        if ($excedidas->sum() > 0) {
            $datasets[] = ['label' => 'Sobre el cupo', 'data' => $excedidas->all(), 'backgroundColor' => '#ef4444'];
        }

        return [
            'datasets' => $datasets,
            'labels' => $licencias->pluck('nombre_licencia')->all(),
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
            'indexAxis' => 'y',
            'scales' => [
                'x' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]],
                'y' => ['stacked' => true],
            ],
        ];
    }
}
