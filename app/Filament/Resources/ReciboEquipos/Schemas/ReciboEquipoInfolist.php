<?php

namespace App\Filament\Resources\ReciboEquipos\Schemas;

use App\Models\ReciboEquipo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ReciboEquipoInfolist
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
                TextEntry::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextEntry::make('url')
                    ->label('URL')
                    ->placeholder('—')
                    ->columnSpanFull(),
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
                    ->visible(fn (ReciboEquipo $record): bool => $record->trashed()),
            ]);
    }
}
