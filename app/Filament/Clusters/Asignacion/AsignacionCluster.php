<?php

namespace App\Filament\Clusters\Asignacion;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class AsignacionCluster extends Cluster
{
    protected static ?string $slug = 'asignacion-formularios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static string|UnitEnum|null $navigationGroup = 'Equipos';

    protected static ?string $navigationLabel = 'Asignación';

    protected static ?string $clusterBreadcrumb = 'Asignación';

    protected static ?int $navigationSort = 3;
}
