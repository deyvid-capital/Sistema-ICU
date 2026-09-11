<?php

namespace App\Filament\Clusters\Configuracion\Pages;

use App\Enums\SeccionConfiguracion;
use App\Filament\Clusters\Configuracion\ConfiguracionCluster;
use App\Models\AppearanceSetting;
use App\Services\Archivos\ArchivosService;
use App\Services\Configuracion\TemaService;
use App\Support\Auditoria\AuditorConfiguracion;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Logo, favicon y alto del logo del panel.
 */
class Marca extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.clusters.configuracion.pages.marca';

    protected static ?string $cluster = ConfiguracionCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Marca e identidad';

    protected static ?string $title = 'Marca e identidad';

    protected static ?string $slug = 'marca';

    protected static ?int $navigationSort = 2;

    /**
     * Colecciones de Media Library que gestiona esta página.
     *
     * @var list<string>
     */
    public const COLECCIONES = ['logo_claro', 'logo_oscuro', 'favicon'];

    public ?AppearanceSetting $record = null;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->record = AppearanceSetting::query()->first() ?? AppearanceSetting::query()->create([
            'tema' => config('tema.tema_por_defecto'),
        ]);

        $this->form->fill([
            'alto_logo' => $this->record->alto_logo ?: config('tema.logo.alto_por_defecto'),
            'fondo_logo' => $this->record->fondo_logo ?: 'auto',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Logo')
                    ->description('Si no se sube ninguno se usa el logo institucional incluido en la aplicación.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->schema([
                        Grid::make(2)->schema([
                            $this->campoDeImagen('logo_claro', 'Logo para modo claro'),
                            $this->campoDeImagen('logo_oscuro', 'Logo para modo oscuro')
                                ->helperText('Opcional. Si se omite se usa el logo del modo claro.'),
                        ]),
                        Select::make('fondo_logo')
                            ->label('Fondo detrás del logo')
                            ->helperText('El logo institucional es azul oscuro sobre transparente: sobre un sidebar oscuro necesita una placa clara. En automático se añade solo cuando hace falta.')
                            ->options([
                                'auto' => 'Automático (recomendado)',
                                'claro' => 'Siempre claro',
                                'ninguno' => 'Sin fondo',
                            ])
                            ->default('auto')
                            ->required()
                            ->native(false),
                        TextInput::make('alto_logo')
                            ->label('Alto del logo')
                            ->placeholder(config('tema.logo.alto_por_defecto'))
                            ->helperText('Valor CSS, por ejemplo 2.5rem o 40px.')
                            ->maxLength(20)
                            ->rule('regex:/^\d+(\.\d+)?(rem|px|em)$/')
                            ->validationMessages([
                                'regex' => 'Usa un valor como 2.5rem, 40px o 3em.',
                            ]),
                    ]),

                Section::make('Favicon')
                    ->description('Icono que aparece en la pestaña del navegador.')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('favicon')
                            ->label('Archivo')
                            ->collection('favicon')
                            ->disk(fn (): string => app(ArchivosService::class)->disco())
                            ->visibility(fn (): string => app(ArchivosService::class)->visibilidad())
                            ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml'])
                            ->maxSize(fn (): int => app(ArchivosService::class)->maxFaviconKb())
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => 'favicon-'.Str::random(8).'.'.$file->getClientOriginalExtension(),
                            )
                            ->validationMessages([
                                'max' => 'El archivo no debe superar los :max KB.',
                            ]),
                    ]),
            ])
            ->model($this->record)
            ->statePath('data');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar cambios')
                ->icon(Heroicon::OutlinedCheck)
                ->action('guardar'),
            Action::make('restaurar')
                ->label('Quitar personalización')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Quitar logo y favicon personalizados')
                ->modalDescription('El panel volverá a usar el logo y el favicon incluidos en la aplicación.')
                ->action('restaurar'),
        ];
    }

    public function guardar(): void
    {
        // getState() persiste los archivos subidos en la Media Library, así que
        // el muestreo previo tiene que hacerse antes de llamarlo.
        $original = $this->record->getOriginal();
        $mediaAntes = $this->estadoDeMedia();

        $datos = $this->form->getState();

        $this->record->fill([
            'alto_logo' => $datos['alto_logo'] ?? null,
            'fondo_logo' => $datos['fondo_logo'] ?? null,
        ])->save();

        // purgarArchivosHeredados() vuelve a guardar y refresca, así que los
        // cambios de este save() hay que capturarlos ahora o se pierden.
        $cambios = $this->record->getChanges();

        // Aplica subidas y eliminaciones: Media Library borra del disco el
        // archivo anterior de cada colección `singleFile()`.
        $this->form->model($this->record)->saveRelationships();

        $this->purgarArchivosHeredados();

        app(AuditorConfiguracion::class)->guardado(
            SeccionConfiguracion::Marca,
            $this->record,
            $original,
            extra: $this->diferenciaDeMedia($mediaAntes, $this->estadoDeMedia()),
            cambios: $cambios,
        );

        app(TemaService::class)->olvidar();

        Notification::make()
            ->success()
            ->title('Marca actualizada')
            ->send();

        $this->redirect(static::getUrl());
    }

    public function restaurar(): void
    {
        $configuracion = AppearanceSetting::query()->first();

        if ($configuracion !== null) {
            $original = $configuracion->getOriginal();
            $mediaAntes = $this->estadoDeMedia($configuracion);

            $configuracion->clearBrandMedia();

            foreach (self::COLECCIONES as $coleccion) {
                $configuracion->purgarArchivoHeredado($coleccion);
            }

            $configuracion->fill([
                'alto_logo' => null,
                'fondo_logo' => null,
            ])->save();

            app(AuditorConfiguracion::class)->restaurado(
                SeccionConfiguracion::Marca,
                $configuracion,
                $original,
                extra: $this->diferenciaDeMedia($mediaAntes, $this->estadoDeMedia($configuracion)),
            );
        }

        app(TemaService::class)->olvidar();

        Notification::make()
            ->success()
            ->title('Marca restaurada')
            ->send();

        $this->redirect(static::getUrl());
    }

    /**
     * Cuando una colección ya tiene su archivo en Media Library, la ruta de la
     * columna heredada queda obsoleta: se borra del disco para no acumular
     * logos ni favicons huérfanos.
     */
    /**
     * Archivo vigente en cada colección gestionada.
     *
     * getFirstMedia() memoriza la relación `media`, así que hay que soltarla o
     * el segundo muestreo devolvería el primero y el diff saldría vacío.
     *
     * @return array<string, string|null>
     */
    protected function estadoDeMedia(?AppearanceSetting $configuracion = null): array
    {
        $configuracion ??= $this->record;

        if ($configuracion === null) {
            return [];
        }

        $configuracion->unsetRelation('media');

        $estado = [];

        foreach (self::COLECCIONES as $coleccion) {
            $estado[$coleccion] = $configuracion->getFirstMedia($coleccion)?->file_name;
        }

        return $estado;
    }

    /**
     * @param  array<string, string|null>  $antes
     * @param  array<string, string|null>  $despues
     * @return array{archivos?: array{subidos?: array<string, string>, eliminados?: array<string, string>}}
     */
    protected function diferenciaDeMedia(array $antes, array $despues): array
    {
        $subidos = [];
        $eliminados = [];

        foreach (self::COLECCIONES as $coleccion) {
            $anterior = $antes[$coleccion] ?? null;
            $actual = $despues[$coleccion] ?? null;

            if ($anterior === $actual) {
                continue;
            }

            if ($actual !== null) {
                $subidos[$coleccion] = $actual;
            }

            if ($anterior !== null) {
                $eliminados[$coleccion] = $anterior;
            }
        }

        $archivos = array_filter([
            'subidos' => $subidos,
            'eliminados' => $eliminados,
        ]);

        return $archivos === [] ? [] : ['archivos' => $archivos];
    }

    protected function purgarArchivosHeredados(): void
    {
        $this->record->refresh();

        $purgados = false;

        foreach (self::COLECCIONES as $coleccion) {
            if (! $this->record->hasMedia($coleccion)) {
                continue;
            }

            $purgados = $this->record->purgarArchivoHeredado($coleccion) || $purgados;
        }

        if ($purgados) {
            $this->record->save();
        }
    }

    protected function campoDeImagen(string $nombre, string $etiqueta): SpatieMediaLibraryFileUpload
    {
        return SpatieMediaLibraryFileUpload::make($nombre)
            ->label($etiqueta)
            ->collection($nombre)
            ->image()
            ->disk(fn (): string => app(ArchivosService::class)->disco())
            ->visibility(fn (): string => app(ArchivosService::class)->visibilidad())
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
            ->maxSize(fn (): int => app(ArchivosService::class)->maxLogoKb())
            ->imagePreviewHeight('120')
            ->conversion('thumb')
            ->getUploadedFileNameForStorageUsing(
                fn (TemporaryUploadedFile $file): string => Str::slug($nombre).'-'.Str::random(8).'.'.$file->getClientOriginalExtension(),
            )
            ->validationMessages([
                'max' => 'La imagen no debe superar los :max KB.',
            ]);
    }
}
