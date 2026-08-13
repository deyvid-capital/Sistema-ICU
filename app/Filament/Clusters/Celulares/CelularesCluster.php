<?php

namespace App\Filament\Clusters\Celulares;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Enums\SubNavigationPosition;

class CelularesCluster extends Cluster
{
    protected static ?string $slug = 'celulares-formularios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Celulares';

    protected static ?string $clusterBreadcrumb = 'Celulares';

    protected static ?int $navigationSort = 6;
}
