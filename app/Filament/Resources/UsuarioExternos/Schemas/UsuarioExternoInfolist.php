<?php

namespace App\Filament\Resources\UsuarioExternos\Schemas;

use App\Models\UsuarioExterno;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UsuarioExternoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre'),
                TextEntry::make('carnet'),
                TextEntry::make('empresa')
                    ->placeholder('-'),
                TextEntry::make('cargo'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (UsuarioExterno $record): bool => $record->trashed()),
            ]);
    }
}
