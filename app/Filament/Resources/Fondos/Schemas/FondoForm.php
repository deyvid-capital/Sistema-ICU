<?php

namespace App\Filament\Resources\Fondos\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FondoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del fondo')
                    ->description('Datos del fondo')
                    ->aside()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Código')
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('nombre_completo')
                                    ->label('Nombre completo')
                                    ->required()
                                    ->maxLength(200),
                            ]),
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
