<?php

namespace App\Filament\Resources\SoftwareExcs\Schemas;

use App\Models\SoftwareExc;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SoftwareExcInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('software.nombre_software')
                    ->label('Software')
                    ->placeholder('—'),
                TextEntry::make('tipo_excepcion')
                    ->label('Tipo de excepcion')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—'),
                TextEntry::make('motivo')
                    ->label('Motivo')
                    ->columnSpanFull(),
                TextEntry::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y'),
                TextEntry::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->date('d/m/Y')
                    ->placeholder('—'),
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
                    ->dateTime()
                    ->visible(fn (SoftwareExc $record): bool => $record->trashed()),
            ]);
    }
}
