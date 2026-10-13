<?php

namespace App\Filament\Resources\SoftwareUsuarios\Schemas;

use App\Filament\Resources\Users\RelationManagers\SoftwareUsuariosRelationManager;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class SoftwareUsuarioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de software al usuario')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('usuario_id')
                            ->label('Usuario')
                            ->relationship('usuario', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->rule(new NotTrashed(User::class))
                            ->hiddenOn(SoftwareUsuariosRelationManager::class),
                        Select::make('id_software')
                            ->label('Software')
                            ->relationship('software', 'nombre_software')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->unique(
                                table: SoftwareUsuario::class,
                                column: 'id_software',
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule, Get $get, Component $livewire, ?SoftwareUsuario $record): Unique {
                                    $usuarioId = $livewire instanceof RelationManager
                                        ? $livewire->getOwnerRecord()->getKey()
                                        : $get('usuario_id');

                                    return $rule
                                        ->where('usuario_id', $usuarioId)
                                        ->ignore($record);
                                },
                            )
                            ->validationMessages([
                                'unique' => 'Este usuario ya tiene asignado este software.',
                            ]),
                        Toggle::make('estado')
                            ->label('Activo')
                            ->default(false)
                            ->required(),
                    ]),
            ]);
    }
}
