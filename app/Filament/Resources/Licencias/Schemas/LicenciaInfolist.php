<?php

namespace App\Filament\Resources\Licencias\Schemas;

use App\Models\Licencia;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LicenciaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre_licencia')
                    ->label('Nombre de la licencia'),
                TextEntry::make('categoria')
                    ->label('Categoria')
                    ->badge()
                    ->color('info'),
                TextEntry::make('total_licencias')
                    ->label('Total'),
                TextEntry::make('asignadas')
                    ->label('Asignadas')
                    ->state(fn (Licencia $record): int => $record->asignadas)
                    ->color(fn (Licencia $record): string => $record->asignadas >= $record->total_licencias
                        ? 'danger'
                        : 'success'),
                IconEntry::make('estado')
                    ->label('Activa')
                    ->boolean(),
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
