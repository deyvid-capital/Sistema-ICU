<?php

namespace App\Filament\Resources\AccesoSharepoints\Schemas;

use App\Filament\Resources\Users\RelationManagers\AccesosSharepointRelationManager;
use App\Models\AccesoSharepoint;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class AccesoSharepointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del acceso SharePoint')
                    ->description('Aquí puedes agregar información del acceso SharePoint')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('usuario_id')
                                    ->label('Usuario')
                                    ->relationship('usuario', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->hiddenOn(AccesosSharepointRelationManager::class)
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule, Get $get, ?AccesoSharepoint $record): Unique {
                                            return $rule
                                                ->where('sitio_id', $get('sitio_id'))
                                                ->ignore($record);
                                        },
                                    ),
                                Select::make('sitio_id')
                                    ->label('Sitio SharePoint')
                                    ->relationship('sitio', 'nombre_sitio')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
