<?php

namespace App\Filament\Resources\Cargadors\Schemas;

use App\Enums\EstadoCargador;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class CargadorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del cargador')
                    ->description('Información del cargador de laptop.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('serial')
                                ->label('Serial')
                                ->required()
                                ->maxLength(50)
                                ->unique(
                                    table: 'cargadores',
                                    column: 'serial',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                )
                                ->disabledOn('edit')
                                ->dehydrated(),
                            TextInput::make('marca')
                                ->label('Marca')
                                ->maxLength(100),
                            TextInput::make('modelo')
                                ->label('Modelo')
                                ->maxLength(100),
                            Select::make('estado')
                                ->label('Estado')
                                ->native(false)
                                ->options(EstadoCargador::class)
                                ->default(EstadoCargador::Disponible)
                                ->required()
                                ->helperText('Puede estar Disponible, Temporal o Reasignación. Dañado/Baja no se asignan.'),
                        ]),
                        RichEditor::make('observaciones')
                            ->label('Observaciones')
                            ->nullable()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ]),
                    ]),
            ]);
    }
}
