<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use \Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Filament\Schemas\Schema;

//modelos
use App\Models\Area;
use App\Models\Cargo;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('carnet'),
                Select::make('id_area')
                    ->label('Área')
                    ->required()
                    ->options(fn () => Area::all()->pluck('area', 'id_area'))
                    ->createOptionForm([
                        TextInput::make('area')
                            ->label('Nombre del área')
                            ->required(),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return Area::create(['area' => $data['area']])->id_area;
                    })
                    ->native(false)
                    ->placeholder('Selecciona un área')
                    ->searchable(),

                Select::make('id_cargo')
                    ->label('Cargo')
                    ->required()
                    ->options(fn () => Cargo::all()->pluck('cargo', 'id_cargo'))
                    ->createOptionForm([
                        TextInput::make('cargo')
                            ->label('Nombre del cargo')
                            ->required(),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return Cargo::create(['cargo' => $data['cargo']])->id_cargo;
                    })
                    ->native(false)
                    ->placeholder('Selecciona un cargo')
                    ->searchable(),
               
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required(),
                TextInput::make('telefono')
                    ->tel(),
                TextInput::make('password')
                    ->password()
                    ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->visible(fn ($livewire) => $livewire instanceof CreateRecord)
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
               
            ]);
    }
}
