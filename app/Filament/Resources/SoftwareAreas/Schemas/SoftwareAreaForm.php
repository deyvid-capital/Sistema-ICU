<?php

namespace App\Filament\Resources\SoftwareAreas\Schemas;

use App\Models\SoftwareArea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SoftwareAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('software_id')
                    ->label('Software')
                    ->relationship('software', 'nombre_software')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get, ?SoftwareArea $record): Unique {
                            return $rule
                                ->where('area_id', $get('area_id'))
                                ->ignore($record);
                        },
                    ),
                Select::make('area_id')
                    ->label('Area')
                    ->relationship('area', 'area')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
            ]);
    }
}
