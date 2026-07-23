<?php

namespace App\Filament\Resources\AsigCelulars\Schemas;

use App\Models\Celular;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AsigCelularForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de celular')
                    ->description('Asocia un celular a un usuario durante un periodo.')
                    ->icon(Heroicon::OutlinedLink)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('celular_id')
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
                                Select::make('usuario_id')
                                    ->label('Usuario')
                                    ->relationship('usuario', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(User::class)),
                                DatePicker::make('fecha_ini')
                                    ->label('Fecha de inicio')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha de fin')
                                    ->afterOrEqual('fecha_ini')
                                    ->helperText('Deje vacío mientras la asignación esté activa.'),
                            ]),
                    ]),
            ]);
    }
}
