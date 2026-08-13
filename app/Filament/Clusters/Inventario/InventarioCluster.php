<?php

namespace App\Filament\Clusters\Inventario;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Enums\SubNavigationPosition;

class InventarioCluster extends Cluster
{
    protected static ?string $slug = 'inventario-componentes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Inventario';

    protected static ?string $clusterBreadcrumb = 'Inventario';

    protected static ?int $navigationSort = 7;
}
