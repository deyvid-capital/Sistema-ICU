<?php

namespace App\Filament\Resources\LicenciasUsuarios\Schemas;

use App\Models\LicenciaUsuario;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class LicenciaUsuarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rule(new NotTrashed(User::class))
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get, ?LicenciaUsuario $record): Unique {
                            return $rule
                                ->where('licencia_id', $get('licencia_id'))
                                ->ignore($record);
                        },
                    ),
                Select::make('licencia_id')
                    ->label('Licencia')
                    ->relationship('licencia', 'nombre_licencia')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                DatePicker::make('fecha')
                    ->label('Fecha de solicitud')
                    ->default(now()),
                Toggle::make('estado')
                    ->label('Activa')
                    ->default(true)
                    ->required(),
            ]);
    }
}
