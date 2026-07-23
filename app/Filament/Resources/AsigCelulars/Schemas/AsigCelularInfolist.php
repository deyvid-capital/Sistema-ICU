<?php

namespace App\Filament\Resources\AsigCelulars\Schemas;

use App\Models\AsigCelular;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AsigCelularInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('celular.id')
                    ->label('Celular')
                    ->placeholder('—'),
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('fecha_ini')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y')
                    ->placeholder('—'),
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
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn (AsigCelular $record): bool => $record->trashed()),
            ]);
    }
}
