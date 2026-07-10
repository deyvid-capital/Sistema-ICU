<?php

namespace App\Filament\Resources\Sistemas\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SistemaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del sistema')
                    ->description('Agrega el nuevo sistema')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('nombre_sistema')
                            ->label('Nombre del sistema')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
