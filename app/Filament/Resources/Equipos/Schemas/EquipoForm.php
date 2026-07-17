<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Enums\EstadoEquipo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class EquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->description('Datos principales del equipo.')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('service_tag')
                                ->label('Service Tag')
                                ->required()
                                ->maxLength(50)
                                ->unique(
                                    table: 'equipos',
                                    column: 'service_tag',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                )
                                ->disabledOn('edit')
                                ->dehydrated(),
                            TextInput::make('nombre_equipo')
                                ->label('Nombre del equipo')
                                ->required()
                                ->maxLength(50)
                                ->default('BOCS'),
                            TextInput::make('codigo_activo')
                                ->label('Código de activo')
                                ->maxLength(100)
                                ->nullable()
                                ->unique(
                                    table: 'equipos',
                                    column: 'codigo_activo',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                ),
                            Select::make('estado')
                                ->label('Estado')
                                ->native(false)
                                ->options(EstadoEquipo::class)
                                ->default(EstadoEquipo::SinAsignar)
                                ->required()
                                ->helperText('Solo los equipos en estado «Sin asignar» pueden asignarse a un usuario.'),
                        ]),
                    ]),
                Section::make('Hardware')
                    ->icon(Heroicon::OutlinedCpuChip)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('modelo_id')
                                ->label('Modelo')
                                ->relationship('modelo', 'nombre')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('microprocesador_id')
                                ->label('Microprocesador')
                                ->relationship('microprocesador', 'nombre_micro')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('ram_id')
                                ->label('Memoria RAM')
                                ->relationship('ram', 'tipo_ram')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('gpu_id')
                                ->label('Tarjeta de video')
                                ->relationship('gpu', 'nombre_gpu')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('disco_duro')
                                ->label('Disco duro')
                                ->maxLength(50)
                                ->placeholder('Ej. 512GB SSD'),
                            TextInput::make('proveedor')
                                ->label('Proveedor')
                                ->maxLength(100),
                        ]),
                    ]),
                Section::make('Software y garantía')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('so')
                                ->label('Sistema operativo')
                                ->required()
                                ->maxLength(50)
                                ->default('Windows 11 PRO'),
                            TextInput::make('licencia_so')
                                ->label('Licencia SO')
                                ->maxLength(50),
                            DatePicker::make('inicio_garantia')
                                ->label('Inicio de garantía'),
                            DatePicker::make('venc_garantia')
                                ->label('Vencimiento de garantía')
                                ->afterOrEqual('inicio_garantia'),
                            Toggle::make('bloqueo_bios')
                                ->label('Bloqueo BIOS')
                                ->default(true)
                                ->required(),
                            Toggle::make('etiquetado')
                                ->label('Etiquetado')
                                ->default(false)
                                ->required(),
                            Toggle::make('usuario_admin')
                                ->label('Usuario administrador'),
                        ]),
                    ]),
                Section::make('Observaciones')
                    ->description('Notas internas del equipo. Puede usar negritas, listas y formato enriquecido.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('observaciones')
                            ->label('Observaciones')
                            ->nullable()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'strike', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList'],
                                ['undo', 'redo'],
                            ]),
                    ]),
            ]);
    }
}
