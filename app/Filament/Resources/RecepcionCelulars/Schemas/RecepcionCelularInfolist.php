<?php

namespace App\Filament\Resources\RecepcionCelulars\Schemas;

use App\Models\RecepcionCelular;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RecepcionCelularInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('asignacion.celular.id')
                    ->label('Celular')
                    ->placeholder('—'),
                TextEntry::make('asignacion.usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('numero')
                    ->placeholder('-'),
                TextEntry::make('fecha')
                    ->date()
                    ->placeholder('-'),
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
                    ->visible(fn (RecepcionCelular $record): bool => $record->trashed()),
            ]);
    }
}
