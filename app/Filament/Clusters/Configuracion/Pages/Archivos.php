<?php

namespace App\Filament\Clusters\Configuracion\Pages;

use App\Filament\Clusters\Configuracion\ConfiguracionCluster;
use App\Models\MediaSetting;
use App\Services\Archivos\ArchivosService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Disco, carpetas y optimización de archivos del panel.
 */
class Archivos extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.clusters.configuracion.pages.archivos';

    protected static ?string $cluster = ConfiguracionCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?string $navigationLabel = 'Archivos';

    protected static ?string $title = 'Archivos y almacenamiento';

    protected static ?string $slug = 'archivos';

    protected static ?int $navigationSort = 3;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $configuracion = MediaSetting::actual();
        $servicio = app(ArchivosService::class)->resolver();

        $this->form->fill([
            'disco' => $configuracion->disco ?: $servicio['disco'],
            'prefijo' => $configuracion->prefijo ?: $servicio['prefijo'],
            'umbral_webp_kb' => $configuracion->umbral_webp_kb ?: $servicio['umbral_webp_kb'],
            'calidad_webp' => $configuracion->calidad_webp ?: $servicio['calidad_webp'],
            'thumb_ancho' => $configuracion->thumb_ancho ?: $servicio['thumb_ancho'],
            'max_imagen_kb' => $configuracion->max_imagen_kb ?: $servicio['max_imagen_kb'],
            'max_pdf_kb' => $configuracion->max_pdf_kb ?: $servicio['max_pdf_kb'],
            'max_logo_kb' => $configuracion->max_logo_kb ?: $servicio['max_logo_kb'],
            'max_favicon_kb' => $configuracion->max_favicon_kb ?: $servicio['max_favicon_kb'],
            'visibilidad' => $configuracion->visibilidad ?: $servicio['visibilidad'],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dónde se guardan')
                    ->description('Disco de Laravel y prefijo de carpetas. Cambiar a s3 requiere las variables AWS_* en el entorno.')
                    ->icon(Heroicon::OutlinedCloudArrowUp)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('disco')
                                ->label('Disco')
                                ->options([
                                    'public' => 'public (local)',
                                    'local' => 'local (privado)',
                                    's3' => 's3 (nube)',
                                ])
                                ->required()
                                ->native(false),
                            TextInput::make('prefijo')
                                ->label('Prefijo de carpeta')
                                ->helperText('Se usa en Media Library: {prefijo}/{colección}/…')
                                ->required()
                                ->maxLength(100)
                                ->rule('regex:/^[A-Za-z0-9_\\-\\/]+$/')
                                ->validationMessages([
                                    'regex' => 'Solo letras, números, guiones, guiones bajos y barras.',
                                ]),
                            Select::make('visibilidad')
                                ->label('Visibilidad')
                                ->options([
                                    'public' => 'Pública',
                                    'private' => 'Privada',
                                ])
                                ->required()
                                ->native(false),
                        ]),
                    ]),

                Section::make('Optimización de imágenes')
                    ->description('Solo aplica a PNG/JPEG/WebP. SVG e ICO no se convierten. El original no se modifica.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('umbral_webp_kb')
                                ->label('Umbral WebP (KB)')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->helperText('Si la imagen pesa más, se genera conversión webp.'),
                            TextInput::make('calidad_webp')
                                ->label('Calidad WebP')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(100),
                            TextInput::make('thumb_ancho')
                                ->label('Ancho del thumb (px)')
                                ->numeric()
                                ->required()
                                ->minValue(50)
                                ->maxValue(2000),
                        ]),
                    ]),

                Section::make('Límites de subida (KB)')
                    ->icon(Heroicon::OutlinedScale)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('max_imagen_kb')
                                ->label('Máx. capturas')
                                ->numeric()
                                ->required()
                                ->minValue(100),
                            TextInput::make('max_pdf_kb')
                                ->label('Máx. PDF')
                                ->numeric()
                                ->required()
                                ->minValue(100),
                            TextInput::make('max_logo_kb')
                                ->label('Máx. logo')
                                ->numeric()
                                ->required()
                                ->minValue(50),
                            TextInput::make('max_favicon_kb')
                                ->label('Máx. favicon')
                                ->numeric()
                                ->required()
                                ->minValue(10),
                        ]),
                    ]),

                Section::make('Catálogo')
                    ->description('Colecciones de imagen (Media Library) y carpetas PDF (ruta en columna). Solo lectura.')
                    ->icon(Heroicon::OutlinedQueueList)
                    ->schema([
                        View::make('filament.clusters.configuracion.partials.archivos-catalogo')
                            ->columnSpanFull(),
                    ]),
            ])
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
        ];
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();

        $configuracion = MediaSetting::query()->first() ?? new MediaSetting;
        $configuracion->fill($datos)->save();

        app(ArchivosService::class)->olvidar();

        Notification::make()
            ->success()
            ->title('Configuración de archivos actualizada')
            ->send();

        $this->redirect(static::getUrl());
    }
}
