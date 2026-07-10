<?php

namespace App\Filament\Resources\AccesoSharepoints\Schemas;

use App\Models\AccesoSharepoint;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AccesoSharepointInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('sitio.nombre_sitio')
                    ->label('Sitio SharePoint')
                    ->placeholder('—'),
                IconEntry::make('estado')
                    ->label('Activo')
                    ->boolean(),
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
                    ->visible(fn (AccesoSharepoint $record): bool => $record->trashed()),
            ]);
    }
}
