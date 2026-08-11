<?php

namespace App\Filament\Clusters\Equipos;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class EquiposCluster extends Cluster
{
    protected static ?string $slug = 'equipos-formularios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?string $navigationLabel = 'Equipos y formularios';

    protected static ?string $clusterBreadcrumb = 'Equipos y formularios';

    protected static ?int $navigationSort = 5;
}
