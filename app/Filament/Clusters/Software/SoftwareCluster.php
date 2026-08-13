<?php

namespace App\Filament\Clusters\Software;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Enums\SubNavigationPosition;

class SoftwareCluster extends Cluster
{
    protected static ?string $slug = 'software-licencias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Software y licencias';

    protected static ?string $clusterBreadcrumb = 'Software y licencias';

    protected static ?int $navigationSort = 2;
}
