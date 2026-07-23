<?php

namespace App\Filament\Resources\CargadorCels\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class CargadorCelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del cargador')
                    ->description('Información del cargador de celular.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('serial')
                                ->label('Serial')
                                ->required()
                                ->maxLength(50)
                                ->unique(
                                    table: 'cargador_cel',
                                    column: 'serial',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                )
                                ->disabledOn('edit')
                                ->dehydrated(),
                            TextInput::make('modelo')
                                ->label('Modelo')
                                ->maxLength(100),
                        ]),
                        RichEditor::make('observacion')
                            ->label('Observación')
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
