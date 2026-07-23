<?php

namespace App\Filament\Resources\CargadorCels\Schemas;

use App\Models\CargadorCel;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CargadorCelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('serial')->label('Serial'),
                TextEntry::make('modelo')->label('Modelo')->placeholder('—'),
                TextEntry::make('observacion')
                    ->label('Observación')
                    ->html()
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn (CargadorCel $record): bool => $record->trashed()),
            ]);
    }
}
