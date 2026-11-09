<?php

namespace App\Filament\Widgets\UsuariosPanel;

use Filament\Support\Icons\Heroicon;

/**
 * Accesos activos de cada usuario, una columna de badges por tipo de acceso.
 * Los tipos menos usados arrancan ocultos y se activan desde el selector de columnas.
 */
class AccesosTab extends PestanaUsuarios
{
    public static function getEtiqueta(): string
    {
        return 'Accesos';
    }

    public static function getIcono(): Heroicon
    {
        return Heroicon::OutlinedLockOpen;
    }

    protected function columnasDeBadges(): array
    {
        return [
            ColumnaDeBadges::make('sistemas', 'Sistemas', 'accesosSistemas', 'sistema.nombre_sistema'),
            ColumnaDeBadges::make('sharepoint', 'SharePoint', 'accesosSharepoint', 'sitio.nombre_sitio', 'info'),
            ColumnaDeBadges::make('virtuales', 'Máquinas virtuales', 'accesosVirtual', 'virtual.maquina_virtual', 'success'),
            ColumnaDeBadges::make('unidades', 'Unidades compartidas', 'unidadesCompartidas', 'unidad_compartida', 'warning', ocultaPorDefecto: true),
            ColumnaDeBadges::make('internet', 'Internet / Wi-Fi', 'accesosInternet', 'nivel_acceso', 'gray', ocultaPorDefecto: true),
            ColumnaDeBadges::make('daza', 'DAZA', 'accesosDaza', 'fondo.codigo', 'gray', ocultaPorDefecto: true),
            ColumnaDeBadges::make('azure', 'Azure AD', 'azureAd', 'iniciales', 'gray', ocultaPorDefecto: true),
            ColumnaDeBadges::make('fortitoken', 'FortiToken', 'fortitokens', 'nombre_token', 'gray', ocultaPorDefecto: true),
        ];
    }

    protected function filtrosPropios(): array
    {
        return [
            $this->filtroPorTipoDeBadge('tipo_acceso', 'Tiene acceso a', $this->columnasDeBadges()),
        ];
    }
}
