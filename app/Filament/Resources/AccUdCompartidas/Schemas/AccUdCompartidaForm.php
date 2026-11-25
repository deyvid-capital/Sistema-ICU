<?php

namespace App\Filament\Resources\AccUdCompartidas\Schemas;

use App\Filament\Resources\Users\RelationManagers\UnidadesCompartidasRelationManager;
use App\Models\AccUdCompartida;
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
                                    ->live()
                                    // Una misma unidad no se asigna dos veces al mismo usuario.
                                    ->unique(
                                        table: AccUdCompartida::class,
                                        column: 'unidad_compartida',
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule, Get $get, Component $livewire, ?AccUdCompartida $record): Unique {
                                            $usuarioId = $livewire instanceof RelationManager
                                                ? $livewire->getOwnerRecord()->getKey()
                                                : $get('usuario_id');

                                            return $rule
                                                ->where('usuario_id', $usuarioId)
                                                ->ignore($record);
                                        },
                                    )
                                    ->validationMessages([
                                        'unique' => 'Este usuario ya tiene asignada esta unidad compartida.',
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
