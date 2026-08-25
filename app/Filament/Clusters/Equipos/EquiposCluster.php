<?php

namespace App\Filament\Clusters\Equipos;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class EquiposCluster extends Cluster
{
    protected static ?string $slug = 'equipos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string|UnitEnum|null $navigationGroup = 'Equipos';

    protected static ?string $navigationLabel = 'Equipos';

    protected static ?string $clusterBreadcrumb = 'Equipos';

    protected static ?int $navigationSort = 2;
}
