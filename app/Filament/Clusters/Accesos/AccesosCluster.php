<?php

namespace App\Filament\Clusters\Accesos;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Enums\SubNavigationPosition;

class AccesosCluster extends Cluster
{
    protected static ?string $slug = 'accesos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Accesos';

    protected static ?string $clusterBreadcrumb = 'Accesos';

    protected static ?int $navigationSort = 3;
}
