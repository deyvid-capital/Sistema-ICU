<?php

namespace App\Filament\Clusters\Recursos;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Enums\SubNavigationPosition;

class RecursosCluster extends Cluster
{
    protected static ?string $slug = 'recursos-compartidos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Recursos compartidos';

    protected static ?string $clusterBreadcrumb = 'Recursos compartidos';

    protected static ?int $navigationSort = 4;
}
