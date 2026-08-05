<?php

namespace App\Filament\Resources\MantenimientoEquipos\Schemas;

use App\Models\MantenimientoEquipo;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MantenimientoEquipoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('equipo.service_tag')
                    ->label('Equipo')
                    ->placeholder('—'),
                TextEntry::make('equipo.nombre_equipo')
                    ->label('Nombre del equipo')
                    ->placeholder('—'),
                TextEntry::make('fecha_soporte')
                    ->label('Fecha de soporte')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                ImageEntry::make('cap_antes')
                    ->label('Captura antes')
                    ->disk(MantenimientoEquipo::IMAGE_DISK)
                    ->visibility('public')
                    ->height(220)
                    ->placeholder('—'),
                ImageEntry::make('cap_despues')
                    ->label('Captura después')
                    ->disk(MantenimientoEquipo::IMAGE_DISK)
                    ->visibility('public')
                    ->height(220)
                    ->placeholder('—'),
                TextEntry::make('observaciones')
                    ->label('Observaciones')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn (MantenimientoEquipo $record): bool => $record->trashed()),
            ]);
    }
}
