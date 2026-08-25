<?php

namespace App\Filament\Clusters\Configuracion\Pages;

use App\Filament\Clusters\Configuracion\ConfiguracionCluster;
use App\Models\AppearanceSetting;
use App\Services\Apariencia\AparienciaService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * @var array<string, string|null>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $configuracion = AppearanceSetting::actual();

        $this->form->fill([
            'logo_claro' => $configuracion->logo_claro,
            'logo_oscuro' => $configuracion->logo_oscuro,
            'favicon' => $configuracion->favicon,
            'alto_logo' => $configuracion->alto_logo ?: config('apariencia.logo.alto_por_defecto'),
            'fondo_logo' => $configuracion->fondo_logo ?: 'auto',
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
                            ->placeholder(config('apariencia.logo.alto_por_defecto'))
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
                        FileUpload::make('favicon')
                            ->label('Archivo')
                            ->disk(config('apariencia.logo.disco'))
                            ->directory(config('apariencia.logo.directorio'))
                            ->visibility('public')
                            ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/svg+xml'])
                            ->maxSize(config('apariencia.favicon.max_kb'))
                            ->getUploadedFileNameForStorageUsing(
                                fn ($file): string => 'favicon-'.Str::random(8).'.'.$file->getClientOriginalExtension(),
                            )
                            ->deleteUploadedFileUsing($this->eliminarArchivo())
                            ->validationMessages([
                                'max' => 'El archivo no debe superar los :max KB.',
                            ]),
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
        $datos = $this->form->getState();

        $configuracion = AppearanceSetting::query()->first() ?? new AppearanceSetting([
            'tema' => config('apariencia.tema_por_defecto'),
        ]);
        $configuracion->fill($datos)->save();

        app(AparienciaService::class)->olvidar();

        Notification::make()
            ->success()
            ->title('Marca actualizada')
            ->send();

        $this->redirect(static::getUrl());
    }

    public function restaurar(): void
    {
        $configuracion = AppearanceSetting::query()->first();

        $configuracion?->update([
            'logo_claro' => null,
            'logo_oscuro' => null,
            'favicon' => null,
            'alto_logo' => null,
            'fondo_logo' => null,
        ]);

        app(AparienciaService::class)->olvidar();

        Notification::make()
            ->success()
            ->title('Marca restaurada')
            ->send();

        $this->redirect(static::getUrl());
    }

    protected function campoDeImagen(string $nombre, string $etiqueta): FileUpload
    {
        return FileUpload::make($nombre)
            ->label($etiqueta)
            ->image()
            ->disk(config('apariencia.logo.disco'))
            ->directory(config('apariencia.logo.directorio'))
            ->visibility('public')
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
            ->maxSize(config('apariencia.logo.max_kb'))
            ->imagePreviewHeight('120')
            ->getUploadedFileNameForStorageUsing(
                fn ($file): string => Str::slug($nombre).'-'.Str::random(8).'.'.$file->getClientOriginalExtension(),
            )
            ->deleteUploadedFileUsing($this->eliminarArchivo())
            ->validationMessages([
                'max' => 'La imagen no debe superar los :max KB.',
            ]);
    }

    protected function eliminarArchivo(): \Closure
    {
        return function (?string $file): void {
            if (filled($file)) {
                Storage::disk(config('apariencia.logo.disco'))->delete($file);
            }
        };
    }
}
