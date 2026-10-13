<?php

namespace App\Filament\Resources\LicenciasUsuarios\Schemas;

use App\Filament\Resources\Users\RelationManagers\LicenciasUsuariosRelationManager;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\User;
use App\Rules\LicenciaConCupoDisponible;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class LicenciaUsuarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de licencia al usuario')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columnSpanFull()
                    ->schema([
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
                            ->relationship(
                                name: 'licencia',
                                titleAttribute: 'nombre_licencia',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->withCount('licenciasUsuariosActivas'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Licencia $record): string => $record->etiquetaConCupo())
                            ->disableOptionWhen(function (string $value, ?LicenciaUsuario $record): bool {
                                if ($record !== null && (string) $record->licencia_id === $value) {
                                    return false;
                                }

                                $licencia = Licencia::query()->find($value);

                                return $licencia === null || ! $licencia->tieneCupoDisponible($record);
                            })
                            ->helperText('Solo se pueden asignar licencias con cupo disponible.')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->rule(fn (Get $get, ?LicenciaUsuario $record): LicenciaConCupoDisponible => new LicenciaConCupoDisponible(
                                assignment: $record,
                                consumes: (bool) $get('estado'),
                            ))
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
                    ]),
            ]);
    }
}
