<?php

namespace App\Filament\Clusters\Equipos;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Enums\SubNavigationPosition;

class EquiposCluster extends Cluster
{
    protected static ?string $slug = 'equipos-formularios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Equipos y formularios';

    protected static ?string $clusterBreadcrumb = 'Equipos y formularios';

    protected static ?int $navigationSort = 5;
}
