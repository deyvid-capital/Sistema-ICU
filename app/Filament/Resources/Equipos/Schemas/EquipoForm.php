<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Enums\EstadoEquipo;
use App\Filament\Forms\Components\CrudSelect;
use App\Models\Equipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\TarjetaVideo;
use App\Services\CambiarEstadoEquipo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

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
                            Hidden::make('confirmar_cierre_asignacion')
                                ->dehydrated(false)
                                ->default(false),
                            Select::make('estado')
                                ->label('Estado')
                                ->native(false)
                                ->options(fn (?Equipo $record): array => static::opcionesDeEstado($record))
                                ->default(EstadoEquipo::SinAsignar)
                                ->required()
                                ->live()
                                ->helperText('Solo los equipos en estado «Sin asignar» pueden asignarse a un usuario.')
                                ->afterStateUpdated(function (
                                    ?string $state,
                                    ?string $old,
                                    Set $set,
                                    Component $livewire,
                                    ?Equipo $record,
                                ): void {
                                    $set('confirmar_cierre_asignacion', false);

                                    if (blank($state) || ! $record instanceof Equipo) {
                                        return;
                                    }

                                    $nuevo = EstadoEquipo::tryFrom($state);

                                    if ($nuevo === null || ! app(CambiarEstadoEquipo::class)->requiereConfirmacion($record, $nuevo)) {
                                        return;
                                    }

                                    $set('estado', $old);
                                    $livewire->mountAction('confirmarCierreAsignacionEquipo', [
                                        'estado' => $state,
                                    ]);
                                }),
                        ]),
                    ]),
                Section::make('Hardware')
                    ->description('Si un componente no está en la lista, créalo con «+ Crear» dentro del desplegable; con ⋮ lo editas o eliminas.')
                    ->icon(Heroicon::OutlinedCpuChip)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            static::selectorDeCatalogo(
                                campo: 'modelo_id',
                                etiqueta: 'Modelo',
                                modelo: ModeloLaptop::class,
                                atributo: 'nombre',
                                icono: Heroicon::OutlinedComputerDesktop,
                                singular: 'modelo de laptop',
                                femenino: false,
                                ejemplo: 'Latitude 5540',
                                largoMaximo: 100,
                            ),
                            static::selectorDeCatalogo(
                                campo: 'microprocesador_id',
                                etiqueta: 'Microprocesador',
                                modelo: Microprocesador::class,
                                atributo: 'nombre_micro',
                                icono: Heroicon::OutlinedCpuChip,
                                singular: 'microprocesador',
                                femenino: false,
                                ejemplo: 'Intel Core i7-1365U',
                                largoMaximo: 100,
                            ),
                            static::selectorDeCatalogo(
                                campo: 'ram_id',
                                etiqueta: 'Memoria RAM',
                                modelo: MemoriaRam::class,
                                atributo: 'tipo_ram',
                                icono: Heroicon::OutlinedCircleStack,
                                singular: 'memoria RAM',
                                femenino: true,
                                ejemplo: '16GB DDR4',
                                largoMaximo: 50,
                            ),
                            static::selectorDeCatalogo(
                                campo: 'gpu_id',
                                etiqueta: 'Tarjeta de video',
                                modelo: TarjetaVideo::class,
                                atributo: 'nombre_gpu',
                                icono: Heroicon::OutlinedTv,
                                singular: 'tarjeta de video',
                                femenino: true,
                                ejemplo: 'NVIDIA RTX 4050',
                                largoMaximo: 100,
                            ),
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
                            Toggle::make('litigation_hold')
                                ->label('Litigation Hold')
                                ->default(false)
                                ->required(),
                            Toggle::make('bitlocker')
                                ->label('BitLocker')
                                ->default(false)
                                ->required(),
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

    /**
     * Selector de un catálogo de hardware (modelo, procesador, RAM o GPU) que
     * permite crear, editar y eliminar opciones sin salir del formulario.
     * Solo se ofrece «Eliminar» cuando ningún equipo (ni eliminado) la usa.
     *
     * @param  class-string<Model>  $modelo
     */
    protected static function selectorDeCatalogo(
        string $campo,
        string $etiqueta,
        string $modelo,
        string $atributo,
        Heroicon $icono,
        string $singular,
        bool $femenino,
        string $ejemplo,
        int $largoMaximo,
    ): CrudSelect {
        $articulo = $femenino ? 'una' : 'un';
        $nuevo = $femenino ? 'Nueva' : 'Nuevo';

        $formulario = [
            TextInput::make($atributo)
                ->label($etiqueta)
                ->placeholder("Ej. {$ejemplo}")
                ->helperText("Debe ser único. Máximo {$largoMaximo} caracteres.")
                ->prefixIcon($icono)
                ->required()
                ->minLength(2)
                ->maxLength($largoMaximo)
                ->unique(ignoreRecord: true)
                ->validationMessages([
                    'unique' => "Ya existe {$articulo} {$singular} con este nombre.",
                ])
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                ->autocomplete(false),
        ];

        return CrudSelect::make($campo)
            ->label($etiqueta)
            ->optionsFromModel(
                $modelo,
                $atributo,
                fn (Builder $query): Builder => $query->withCount(['equipos' => fn (Builder $equipos): Builder => $equipos->withTrashed()]),
            )
            ->optionIcon($icono)
            ->placeholder("Elige {$articulo} {$singular}")
            ->noOptionsMessage('Aún no hay registros. Usa «+ Crear» para agregar el primero.')
            ->createOptionForm($formulario)
            ->editOptionForm($formulario)
            ->createOptionModalHeading("{$nuevo} {$singular}")
            ->editOptionModalHeading('Editar '.$singular)
            ->canDeleteOption(fn (?Model $record): bool => $record !== null
                && (int) $record->getAttribute('equipos_count') === 0
                && Gate::allows('delete', $record))
            ->required();
    }

    /**
     * «Asignado» se deriva de las asignaciones vigentes, así que no se ofrece;
     * se conserva únicamente si es el estado actual del registro.
     *
     * @return array<string, string>
     */
    protected static function opcionesDeEstado(?Equipo $record): array
    {
        $actual = $record?->estado;

        return collect(EstadoEquipo::cases())
            ->filter(fn (EstadoEquipo $estado): bool => $estado->esSeleccionableManualmente() || $estado === $actual)
            ->mapWithKeys(fn (EstadoEquipo $estado): array => [$estado->value => $estado->getLabel()])
            ->all();
    }
}
