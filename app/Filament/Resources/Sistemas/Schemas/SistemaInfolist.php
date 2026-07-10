<?php

namespace App\Filament\Resources\Sistemas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SistemaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del sistema')
                    ->schema([
                        TextEntry::make('nombre_sistema')
                            ->label('Nombre del sistema'),
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
