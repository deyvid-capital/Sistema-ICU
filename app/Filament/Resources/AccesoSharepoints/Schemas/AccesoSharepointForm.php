<?php

namespace App\Filament\Resources\AccesoSharepoints\Schemas;

use App\Filament\Resources\Users\RelationManagers\AccesosSharepointRelationManager;
use App\Models\AccesoSharepoint;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

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
                                    ->rule(new NotTrashed(User::class))
                                    ->hiddenOn(AccesosSharepointRelationManager::class),
                                Select::make('sitio_id')
                                    ->label('Sitio SharePoint')
                                    ->relationship('sitio', 'nombre_sitio')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->unique(
                                        table: AccesoSharepoint::class,
                                        column: 'sitio_id',
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule, Get $get, Component $livewire, ?AccesoSharepoint $record): Unique {
                                            $usuarioId = $livewire instanceof RelationManager
                                                ? $livewire->getOwnerRecord()->getKey()
                                                : $get('usuario_id');

                                            return $rule
                                                ->where('usuario_id', $usuarioId)
                                                ->ignore($record);
                                        },
                                    )
                                    ->validationMessages([
                                        'unique' => 'Este usuario ya tiene asignado este sitio de SharePoint.',
                                    ]),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
