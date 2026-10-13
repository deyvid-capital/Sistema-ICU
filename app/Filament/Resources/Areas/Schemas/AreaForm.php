<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del área')
                    ->icon(Heroicon::OutlinedBuildingOffice)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('area')
                            ->required(),
                        Textarea::make('descripcion')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
