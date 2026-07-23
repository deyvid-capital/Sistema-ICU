<?php

namespace App\Filament\Resources\AsigCargadorCels\Schemas;

use App\Models\AsigCargadorCel;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AsigCargadorCelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('celular.id')
                    ->label('Celular')
                    ->placeholder('—'),
                TextEntry::make('cargador.serial')
                    ->label('Cargador')
                    ->placeholder('—'),
                TextEntry::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextEntry::make('motivo')
                    ->label('Motivo')
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
                    ->visible(fn (AsigCargadorCel $record): bool => $record->trashed()),
            ]);
    }
}
