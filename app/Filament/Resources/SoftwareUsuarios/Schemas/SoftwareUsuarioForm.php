<?php

namespace App\Filament\Resources\SoftwareUsuarios\Schemas;

use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SoftwareUsuarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rule(new NotTrashed(User::class))
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get, ?SoftwareUsuario $record): Unique {
                            return $rule
                                ->where('id_software', $get('id_software'))
                                ->ignore($record);
                        },
                    ),
                Select::make('id_software')
                    ->label('Software')
                    ->relationship('software', 'nombre_software')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),
                Toggle::make('estado')
                    ->label('Activo')
                    ->default(false)
                    ->required(),
            ]);
    }
}
