<?php

namespace App\Filament\Resources\EntregaCelulars\Schemas;

use App\Models\EntregaCelular;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EntregaCelularInfolist
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
                    ->date(),
                TextEntry::make('observaciones')
                    ->placeholder('-')
                    ->columnSpanFull(),
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
                    ->visible(fn (EntregaCelular $record): bool => $record->trashed()),
            ]);
    }
}
