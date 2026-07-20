<?php

namespace App\Filament\Resources\RecepcionEquipos\Schemas;

use App\Models\RecepcionEquipo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RecepcionEquipoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('asignacion.id')
                    ->label('Asignacion'),
                TextEntry::make('numero'),
                TextEntry::make('fecha')
                    ->date(),
                TextEntry::make('observaciones')
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
                    ->visible(fn (RecepcionEquipo $record): bool => $record->trashed()),
            ]);
    }
}
