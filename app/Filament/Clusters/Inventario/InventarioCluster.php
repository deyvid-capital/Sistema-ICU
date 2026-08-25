<?php

namespace App\Filament\Clusters\Inventario;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class InventarioCluster extends Cluster
{
    protected static ?string $slug = 'inventario-componentes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string|UnitEnum|null $navigationGroup = 'Equipos';

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $clusterBreadcrumb = 'Inventario';

    protected static ?int $navigationSort = 1;
}
