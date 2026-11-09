<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Dashboard principal: KPIs a lo ancho, charts de dos en dos y tablas debajo.
 * Los widgets se descubren en app/Filament/Widgets y se ordenan con su $sort.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function getColumns(): int|array
    {
        return ['default' => 1, 'lg' => 2];
    }
}
