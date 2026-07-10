<?php

namespace App\Filament\Resources\AccesoDazas\Schemas;

use App\Models\AccesoDaza;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AccesoDazaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('usuario_daza')
                    ->label('Usuario DAZA')
                    ->placeholder('—'),
                TextEntry::make('nivel_acceso')
                    ->label('Nivel de acceso')
                    ->placeholder('—'),
                TextEntry::make('fondo.nombre_completo')
                    ->label('Fondo')
                    ->formatStateUsing(fn (?string $state, AccesoDaza $record): string => $state ?? $record->fondo?->codigo ?? '—')
                    ->placeholder('—'),
                TextEntry::make('ambiente')
                    ->label('Ambiente')
                    ->placeholder('—'),
                TextEntry::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
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
                    ->visible(fn (AccesoDaza $record): bool => $record->trashed()),
            ]);
    }
}
