<?php

namespace App\Filament\Resources\Cargadors\Schemas;

use App\Models\Cargador;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CargadorInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('serial')->label('Serial'),
                TextEntry::make('marca')->label('Marca')->placeholder('—'),
                TextEntry::make('modelo')->label('Modelo')->placeholder('—'),
                TextEntry::make('estado')->label('Estado')->badge()->placeholder('—'),
                TextEntry::make('observaciones')
                    ->label('Observaciones')
                    ->html()
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn (Cargador $record): bool => $record->trashed()),
            ]);
    }
}
