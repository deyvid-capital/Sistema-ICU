<?php

namespace App\Filament\Resources\Movimientos\Schemas;

use App\Models\Movimiento;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MovimientoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (Movimiento $record): string => $record->tipo === 'Alta' ? 'success' : 'danger'),
                TextEntry::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y'),
                TextEntry::make('cargo.cargo')
                    ->label('Cargo')
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
                    ->visible(fn (Movimiento $record): bool => $record->trashed()),
            ]);
    }
}
