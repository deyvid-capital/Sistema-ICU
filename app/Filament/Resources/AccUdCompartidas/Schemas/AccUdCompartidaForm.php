<?php

namespace App\Filament\Resources\AccUdCompartidas\Schemas;

use App\Filament\Resources\Users\RelationManagers\UnidadesCompartidasRelationManager;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AccUdCompartidaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la unidad compartida')
                    ->description('Unidades Compartidas del usuario para el Sistema de DAZA')
                    ->icon(Heroicon::OutlinedUser)
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
                                    ->hiddenOn(UnidadesCompartidasRelationManager::class),
                                Select::make('unidad_compartida')
                                    ->label('Unidad compartida')
                                    ->options(
                                        [
                                            'Daza Producción (P)' => ' Daza Producción (P) ',
                                            'Daza Contingencia (Q)' => 'Daza Contingencia (Q)',
                                            'Daza Test (T)' => 'Daza Test (T)',
                                        ]
                                    )
                                    ->native(false)
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
