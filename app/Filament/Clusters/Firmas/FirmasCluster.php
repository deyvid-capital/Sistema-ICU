<?php

namespace App\Filament\Clusters\Firmas;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class FirmasCluster extends Cluster
{
    protected static ?string $slug = 'firmas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Firma digital';

    protected static ?string $clusterBreadcrumb = 'Firma digital';

    protected static ?int $navigationSort = 7;
}
