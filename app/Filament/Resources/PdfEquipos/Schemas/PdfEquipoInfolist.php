<?php

namespace App\Filament\Resources\PdfEquipos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PdfEquipoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre'),
                TextEntry::make('documento_type'),
                TextEntry::make('documento_id')
                    ->numeric(),
                TextEntry::make('fecha')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('url')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
