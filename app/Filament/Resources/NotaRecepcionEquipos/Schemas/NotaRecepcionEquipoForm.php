<?php

namespace App\Filament\Resources\NotaRecepcionEquipos\Schemas;

use App\Filament\Resources\Equipos\RelationManagers\NotasRecepcionRelationManager as EquipoNotasRecepcionRelationManager;
use App\Filament\Resources\UsuarioExternos\RelationManagers\NotasRecepcionRelationManager as UsuarioExternoNotasRecepcionRelationManager;
use App\Models\Equipo;
use App\Models\UsuarioExterno;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class NotaRecepcionEquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nota de recepción')
                    ->description('Registra la recepción de un equipo proveniente de un usuario externo.')
                    ->icon(Heroicon::OutlinedArrowLeftEndOnRectangle)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('equipo_id')
                                    ->label('Equipo')
                                    ->relationship('equipo', 'service_tag')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Equipo $record): string => trim("{$record->service_tag} — {$record->nombre_equipo}")
                                    )
                                    ->searchable(['service_tag', 'nombre_equipo'])
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(Equipo::class))
                                    ->hiddenOn(EquipoNotasRecepcionRelationManager::class),
                                Select::make('usuarios_externo_id')
                                    ->label('Usuario externo')
                                    ->relationship('usuarioExterno', 'nombre')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (UsuarioExterno $record): string => trim("{$record->nombre} — {$record->carnet}")
                                    )
                                    ->searchable(['nombre', 'carnet', 'empresa'])
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(UsuarioExterno::class))
                                    ->hiddenOn(UsuarioExternoNotasRecepcionRelationManager::class),
                                TextInput::make('numero')
                                    ->label('Número')
                                    ->required()
                                    ->maxLength(20),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required(),
                                Textarea::make('nota')
                                    ->label('Nota')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
