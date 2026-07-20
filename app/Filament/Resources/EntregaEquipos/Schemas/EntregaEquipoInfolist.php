<?php

namespace App\Filament\Resources\EntregaEquipos\Schemas;

use App\Models\EntregaEquipo;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EntregaEquipoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('asignacion.id')
                    ->label('Asignacion'),
                TextEntry::make('numero')
                    ->placeholder('-'),
                TextEntry::make('fecha')
                    ->date(),
                IconEntry::make('mouse')
                    ->boolean(),
                IconEntry::make('cooler')
                    ->boolean(),
                IconEntry::make('audifonos')
                    ->boolean(),
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
                    ->visible(fn (EntregaEquipo $record): bool => $record->trashed()),
            ]);
    }
}
