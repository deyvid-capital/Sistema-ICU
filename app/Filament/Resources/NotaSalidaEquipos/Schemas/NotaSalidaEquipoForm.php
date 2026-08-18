<?php

namespace App\Filament\Resources\NotaSalidaEquipos\Schemas;

use App\Filament\Resources\Concerns\ConfiguresFormularioEquipoNumeroField;
use App\Filament\Resources\Equipos\RelationManagers\NotasSalidaRelationManager as EquipoNotasSalidaRelationManager;
use App\Filament\Resources\UsuarioExternos\RelationManagers\NotasSalidaRelationManager as UsuarioExternoNotasSalidaRelationManager;
use App\Models\Equipo;
use App\Models\NotaSalidaEquipo;
use App\Models\UsuarioExterno;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class NotaSalidaEquipoForm
{
    use ConfiguresFormularioEquipoNumeroField;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nota de salida')
                    ->description('Registra la salida de un equipo hacia un usuario externo.')
                    ->icon(Heroicon::OutlinedArrowRightStartOnRectangle)
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
                                    ->hiddenOn(EquipoNotasSalidaRelationManager::class),
                                Select::make('usuario_externo_id')
                                    ->label('Usuario externo')
                                    ->relationship('usuarioExterno', 'nombre')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (UsuarioExterno $record): string => trim("{$record->nombre} — {$record->carnet}")
                                    )
                                    ->searchable(['nombre', 'carnet', 'empresa'])
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(UsuarioExterno::class))
                                    ->hiddenOn(UsuarioExternoNotasSalidaRelationManager::class),
                                static::formularioEquipoNumeroField(NotaSalidaEquipo::class, 'notas_salida_equipos'),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(
                                        static::syncFormularioEquipoNumeroOnFechaChange(NotaSalidaEquipo::class),
                                    ),
                                Textarea::make('nota')
                                    ->label('Nota')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
