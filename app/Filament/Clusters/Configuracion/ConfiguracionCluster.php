<?php

namespace App\Filament\Clusters\Configuracion;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class ConfiguracionCluster extends Cluster
{
    protected static ?string $slug = 'configuracion';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    /**
     * Sub-navegación a la izquierda: las páginas del cluster se listan en una
     * columna lateral dentro de la propia página.
     */
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $clusterBreadcrumb = 'Configuración';

    /**
     * No se muestra en el sidebar: se entra desde el menú de usuario.
     */
    protected static bool $shouldRegisterNavigation = false;
}
