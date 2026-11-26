<?php

namespace App\Filament\Resources\UsuariosExternos\Schemas;

use App\Enums\PermisoDocumentoCompartido;
use App\Models\SitioSharepoint;
use App\Models\User;
use App\Rules\NotTrashed;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class UsuarioExternoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contacto externo')
                    ->icon(Heroicon::OutlinedUser)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                TextInput::make('numero')
                                    ->label('N° de formulario')
                                    ->placeholder('Se asigna automáticamente al guardar')
                                    ->helperText('Correlativo por año (001-2026, 002-2026...). No se puede editar.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                                TextInput::make('nombre_contacto')
                                    ->label('Nombre del contacto')
                                    ->placeholder('Ej. Juan Pérez')
                                    ->required()
                                    ->maxLength(200),
                                TextInput::make('empresa')
                                    ->label('Empresa')
                                    ->placeholder('Ej. Consultora XYZ')
                                    ->required()
                                    ->maxLength(150),
                                TextInput::make('cargo')
                                    ->label('Cargo')
                                    ->placeholder('Ej. Consultor externo')
                                    ->required()
                                    ->maxLength(150),
                                TextInput::make('correo')
                                    ->label('Correo')
                                    ->placeholder('usuario@consultora.com')
                                    ->email()
                                    ->required()
                                    ->maxLength(150),
                            ]),
                    ]),
                Section::make('Acceso')
                    ->icon(Heroicon::OutlinedShare)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                Select::make('responsable_id')
                                    ->label('Responsable interno')
                                    ->relationship(
                                        'responsable',
                                        'name',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query->withoutTrashed()->orderBy('name'),
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(User::class)),
                                TextInput::make('motivo')
                                    ->label('Motivo')
                                    ->placeholder('Ej. Auditoría')
                                    ->required()
                                    ->maxLength(255),
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha inicio')
                                    ->displayFormat('d/m/Y')
                                    ->default(fn (): string => now(config('app.display_timezone'))->toDateString())
                                    ->required()
                                    ->live(onBlur: true),
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha fin')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Ese día se avisa por correo a los administradores. Si la cambias, el aviso se reprograma.')
                                    ->afterOrEqual('fecha_inicio')
                                    ->validationMessages([
                                        'after_or_equal' => 'La fecha fin no puede ser anterior a la fecha inicio.',
                                    ]),
                                Select::make('permiso')
                                    ->label('Permiso sobre los documentos')
                                    ->options(PermisoDocumentoCompartido::class)
                                    ->default(PermisoDocumentoCompartido::Lectura)
                                    ->native(false)
                                    ->required(),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->helperText('Al desactivarlo se cancela el aviso de la fecha fin.')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),
                Section::make('Documentos compartidos')
                    ->description('SharePoint y OneDrive son opcionales e independientes, pero se necesita al menos uno.')
                    ->icon(Heroicon::OutlinedFolderOpen)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 2])
                            ->schema([
                                Select::make('sitio_sharepoint_id')
                                    ->label('Sitio SharePoint')
                                    ->relationship(
                                        'sitioSharepoint',
                                        'nombre_sitio',
                                        modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('nombre_sitio'),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (SitioSharepoint $record): string => $record->estado
                                        ? $record->nombre_sitio
                                        : "{$record->nombre_sitio} (inactivo)")
                                    ->searchable()
                                    ->preload()
                                    ->requiredWithout('onedrive')
                                    ->validationMessages([
                                        'required_without' => 'Indica un sitio SharePoint, un OneDrive o ambos.',
                                    ]),
                                TextInput::make('onedrive')
                                    ->label('OneDrive')
                                    ->placeholder('Nombre de la carpeta o URL')
                                    ->maxLength(500)
                                    ->requiredWithout('sitio_sharepoint_id')
                                    ->validationMessages([
                                        'required_without' => 'Indica un sitio SharePoint, un OneDrive o ambos.',
                                    ])
                                    ->rule(fn (): Closure => self::urlValidaSiLoParece()),
                            ]),
                    ]),
            ]);
    }

    /**
     * El OneDrive puede ser un nombre simple; si empieza como URL, debe ser una URL válida.
     */
    private static function urlValidaSiLoParece(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! preg_match('#^https?:#i', trim($value))) {
                return;
            }

            if (filter_var(trim($value), FILTER_VALIDATE_URL) === false) {
                $fail('El OneDrive parece una URL pero no es válida. Escribe la URL completa (https://...) o solo el nombre.');
            }
        };
    }
}
