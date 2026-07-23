<?php

namespace App\Filament\Resources\AsigCargadorCels\Schemas;

use App\Models\CargadorCel;
use App\Models\Celular;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AsigCargadorCelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de cargador de celular')
                    ->description('Asocia un cargador a un celular.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('celulares_id')
                                    ->label('Celular')
                                    ->relationship('celular', 'id')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Celular $record): string => trim(sprintf(
                                            '%s — %s %s',
                                            $record->id,
                                            $record->marca ?? '',
                                            $record->modelo ?? '',
                                        ))
                                    )
                                    ->searchable(['id', 'marca', 'modelo'])
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(Celular::class)),
                                Select::make('cargador_serial')
                                    ->label('Cargador')
                                    ->relationship('cargador', 'serial')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (CargadorCel $record): string => trim(sprintf(
                                            '%s — %s',
                                            $record->serial,
                                            $record->modelo ?? '',
                                        ))
                                    )
                                    ->searchable(['serial', 'modelo'])
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(CargadorCel::class)),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now()),
                                Textarea::make('motivo')
                                    ->label('Motivo')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
