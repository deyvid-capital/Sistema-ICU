<?php

namespace App\Filament\Resources\MantenimientoEquipos\Schemas;

use App\Models\MantenimientoEquipo;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MantenimientoEquipoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextEntry::make('equipo.service_tag')
                    ->label('👨‍💻 Equipo')
                    ->placeholder('No especificado')
                    ->badge()
                    ->color('primary'),
                TextEntry::make('equipo.nombre_equipo')
                    ->label('📝 Nombre del equipo')
                    ->placeholder('No especificado')
                    ->badge()
                    ->color('secondary'),
                TextEntry::make('fecha_soporte')
                    ->label('📆 Fecha de soporte')
                    ->date('d/m/Y')
                    ->placeholder('Sin fecha')
                    ->badge()
                    ->color('info'),
                SpatieMediaLibraryImageEntry::make('cap_antes')
                    ->label('📸 Captura antes')
                    ->collection('cap_antes')
                    ->conversion('thumb')
                    ->placeholder('Sin imagen'),
                SpatieMediaLibraryImageEntry::make('cap_despues')
                    ->label('📸 Captura después')
                    ->collection('cap_despues')
                    ->conversion('thumb')
                    ->placeholder('Sin imagen'),
                TextEntry::make('observaciones')
                    ->label('🗒️ Observaciones')
                    ->placeholder('No hay observaciones')
                    ->columnSpanFull()
                    ->markdown(),
                TextEntry::make('created_at')
                    ->label('🕒 Creado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('No disponible')
                    ->icon('heroicon-m-calendar-days')
                    ->color('success'),
                TextEntry::make('updated_at')
                    ->label('🔄 Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('No disponible')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning'),
                TextEntry::make('deleted_at')
                    ->label('❌ Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn (MantenimientoEquipo $record): bool => $record->trashed())
                    ->placeholder('No eliminado')
                    ->color('danger'),
            ]);
    }
}
