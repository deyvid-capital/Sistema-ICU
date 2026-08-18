<?php

namespace App\Filament\Clusters\Equipos;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class EquiposCluster extends Cluster
{
    protected static ?string $slug = 'asignacion-formularios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Asignación y Form.';

    protected static ?string $clusterBreadcrumb = 'Asignación y Form.';

    protected static ?int $navigationSort = 5;
}
