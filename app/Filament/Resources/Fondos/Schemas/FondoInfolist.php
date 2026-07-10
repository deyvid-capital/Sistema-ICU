<?php

namespace App\Filament\Resources\Fondos\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FondoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del fondo')
                    ->schema([
                        TextEntry::make('codigo')
                            ->label('Código'),
                        TextEntry::make('nombre_completo')
                            ->label('Nombre completo')
                            ->placeholder('—'),
                        TextEntry::make('descripcion')
                            ->label('Descripción')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Registrado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('updated_at')
                            ->label('Actualizado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }
}
