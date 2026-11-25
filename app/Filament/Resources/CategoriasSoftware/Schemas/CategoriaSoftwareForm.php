<?php

namespace App\Filament\Resources\CategoriasSoftware\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class CategoriaSoftwareForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la categoría')
                    ->icon(Heroicon::OutlinedTag)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation): void {
                                        if ($operation === Operation::Create->value) {
                                            $set('codigo', Str::slug($state ?? '', '_'));
                                        }
                                    }),
                                TextInput::make('codigo')
                                    ->label('Código')
                                    ->helperText('Identificador interno. El formulario de alta/baja y la matriz de perfiles ubican el software por este código; no se puede cambiar después.')
                                    ->required()
                                    ->maxLength(50)
                                    ->alphaDash()
                                    ->unique(ignoreRecord: true)
                                    ->disabledOn(Operation::Edit),
                            ]),
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
