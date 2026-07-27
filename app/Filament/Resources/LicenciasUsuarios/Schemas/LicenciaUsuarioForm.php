<?php

namespace App\Filament\Resources\LicenciasUsuarios\Schemas;

use App\Filament\Resources\Users\RelationManagers\LicenciasUsuariosRelationManager;
use App\Models\LicenciaUsuario;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Components\Component;
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
                    ->hiddenOn(LicenciasUsuariosRelationManager::class)
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
                    ->unique(
                        table: LicenciaUsuario::class,
                        column: 'licencia_id',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get, $livewire, ?LicenciaUsuario $record): Unique {
                            $usuarioId = $livewire instanceof RelationManager
                                ? $livewire->getOwnerRecord()->getKey()
                                : $get('usuario_id');

                            return $rule
                                ->where('usuario_id', $usuarioId)
                                ->ignore($record);
                        },
                    )
                    ->validationMessages([
                        'unique' => 'Este usuario ya tiene asignada esta licencia.',
                    ]),
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
