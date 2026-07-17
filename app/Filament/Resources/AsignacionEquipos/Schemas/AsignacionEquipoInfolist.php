<?php

namespace App\Filament\Resources\AsignacionEquipos\Schemas;

use App\Models\AsignacionEquipo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AsignacionEquipoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('equipo.service_tag')
                    ->label('Equipo')
                    ->placeholder('—'),
                TextEntry::make('equipo.nombre_equipo')
                    ->label('Nombre del equipo')
                    ->placeholder('—'),
                TextEntry::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextEntry::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextEntry::make('motivo')
                    ->label('Motivo')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('observaciones')
                    ->label('Observaciones')
                    ->html()
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
                TextEntry::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn (AsignacionEquipo $record): bool => $record->trashed()),
            ]);
    }
}
