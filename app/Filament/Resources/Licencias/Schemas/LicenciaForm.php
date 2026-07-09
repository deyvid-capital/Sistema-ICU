<?php

namespace App\Filament\Resources\Licencias\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LicenciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la licencia')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nombre_licencia')
                                    ->label('Nombre de la licencia')
                                    ->required()
                                    ->maxLength(150)
                                    ->unique(ignoreRecord: true),
                                Select::make('categoria')
                                    ->label('Categoria')
                                    ->required()
                                    ->default('MICROSOFT')
                                    ->options([
                                        'MICROSOFT' => 'MICROSOFT',
                                        'ADOBE' => 'ADOBE',
                                        'ANTIVIRUS' => 'ANTIVIRUS',
                                        'VPN' => 'VPN',
                                        'OTRO' => 'OTRO',
                                    ]),
                                Toggle::make('estado')
                                    ->label('Activa')
                                    ->default(true)
                                    ->required(),
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
