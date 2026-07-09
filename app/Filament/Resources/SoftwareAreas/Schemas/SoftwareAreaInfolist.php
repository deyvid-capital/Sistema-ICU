<?php

namespace App\Filament\Resources\SoftwareAreas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SoftwareAreaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('software.nombre_software')
                    ->label('Software')
                    ->placeholder('—'),
                TextEntry::make('area.area')
                    ->label('Area')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
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
