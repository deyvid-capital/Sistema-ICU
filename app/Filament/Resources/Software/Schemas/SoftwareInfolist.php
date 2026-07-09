<?php

namespace App\Filament\Resources\Software\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SoftwareInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('categoria')
                    ->label('Categoria')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                TextEntry::make('nombre_software')
                    ->label('Nombre del software'),
                IconEntry::make('estado')
                    ->label('Activo')
                    ->boolean(),
                TextEntry::make('fecha_autorizacion')
                    ->label('Fecha de autorizacion')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextEntry::make('descripcion')
                    ->label('Descripcion')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ]);
    }
}
