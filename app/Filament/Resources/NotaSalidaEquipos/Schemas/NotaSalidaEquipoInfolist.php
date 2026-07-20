<?php

namespace App\Filament\Resources\NotaSalidaEquipos\Schemas;

use App\Models\NotaSalidaEquipo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NotaSalidaEquipoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('equipo.service_tag')
                    ->label('Equipo'),
                TextEntry::make('usuarioExterno.id')
                    ->label('Usuario externo'),
                TextEntry::make('numero'),
                TextEntry::make('fecha')
                    ->date(),
                TextEntry::make('nota')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (NotaSalidaEquipo $record): bool => $record->trashed()),
            ]);
    }
}
