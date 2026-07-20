<?php

namespace App\Filament\Resources\Firmantes\Schemas;

use App\Models\Firmante;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FirmanteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario'),
                TextEntry::make('fecha')
                    ->date(),
                TextEntry::make('documento_type'),
                TextEntry::make('documento_id')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Firmante $record): bool => $record->trashed()),
            ]);
    }
}
