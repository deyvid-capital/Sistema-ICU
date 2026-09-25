<?php

namespace App\Filament\Resources\Software\Schemas;

use App\Enums\CategoriasSoftware;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SoftwareForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del software')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nombre_software')
                                    ->label('Nombre del software')
                                    ->required()
                                    ->maxLength(150)
                                    ->unique(ignoreRecord: true),
                                Select::make('categoria')
                                    ->label('Categoria')
                                    ->options(CategoriasSoftware::getOptions())
                                    ->searchable()
                                    ->required(),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->helperText('Si el software no esta activo, se desactivaran todas las licencias de usuario que tengan asignado este software')
                                    ->default(true)
                                    ->required(),
                                DatePicker::make('fecha_autorizacion')
                                    ->label('Fecha de autorizacion'),
                            ]),
                        Textarea::make('descripcion')
                            ->label('Descripcion')
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
