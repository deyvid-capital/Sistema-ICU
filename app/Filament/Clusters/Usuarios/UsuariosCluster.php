<?php

namespace App\Filament\Clusters\Usuarios;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Filament\Pages\Enums\SubNavigationPosition;

class UsuariosCluster extends Cluster
{
    protected static ?string $slug = 'gestion-usuarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $clusterBreadcrumb = 'Usuarios';

    protected static ?int $navigationSort = 1;
}
