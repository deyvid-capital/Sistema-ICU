<?php

namespace App\Filament\Resources\NotaRecepcionEquipos\Schemas;

use App\Models\NotaRecepcionEquipo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NotaRecepcionEquipoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('equipo.service_tag')
                    ->label('Equipo'),
                TextEntry::make('usuarios_externo_id')
                    ->numeric(),
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
                    ->visible(fn (NotaRecepcionEquipo $record): bool => $record->trashed()),
            ]);
    }
}
