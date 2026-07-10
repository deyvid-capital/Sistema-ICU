<?php

namespace App\Filament\Resources\SitioSharepoints\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SitioSharepointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del sitio SharePoint')
                    ->description('Aquí puedes agregar información del sitio SharePoint')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nombre_sitio')
                                    ->label('Nombre del sitio')
                                    ->required()
                                    ->maxLength(200),
                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->maxLength(500),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                            ]),
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
