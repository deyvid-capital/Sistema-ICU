<?php

namespace App\Filament\Resources\MantenimientoEquipos\Schemas;

use App\Models\Equipo;
use App\Models\MantenimientoEquipo;
use App\Rules\NotTrashed;
use App\Services\Archivos\ArchivosService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MantenimientoEquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mantenimiento de equipo')
                    ->description('Registra el soporte y las capturas antes y después del mantenimiento.')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('service_tag')
                                    ->label('Equipo')
                                    ->relationship('equipo', 'service_tag')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Equipo $record): string => trim("{$record->service_tag} — {$record->nombre_equipo}")
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->rule(new NotTrashed(Equipo::class)),
                                DatePicker::make('fecha_soporte')
                                    ->label('Fecha de soporte')
                                    ->default(now())
                                    ->required()
                                    ->live(),
                                self::capturaUpload('cap_antes', 'antes')
                                    ->label('Captura antes del mantenimiento'),
                                self::capturaUpload('cap_despues', 'despues')
                                    ->label('Captura después del mantenimiento'),
                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    /**
     * Campo de imagen (PNG/JPG) para las capturas del mantenimiento.
     */
    protected static function capturaUpload(string $name, string $momento): SpatieMediaLibraryFileUpload
    {
        $archivos = app(ArchivosService::class);

        return SpatieMediaLibraryFileUpload::make($name)
            ->collection($name)
            ->image()
            ->disk($archivos->disco())
            ->visibility($archivos->visibilidad())
            ->acceptedFileTypes(MantenimientoEquipo::IMAGE_MIME_TYPES)
            ->maxSize($archivos->maxImagenKb())
            ->helperText('Formatos permitidos: PNG, JPG o JPEG. Tamaño máximo: '.number_format($archivos->maxImagenKb() / 1024, 0).' MB.')
            ->validationMessages([
                'max' => 'La imagen no debe superar los :max KB.',
                'mimetypes' => 'La imagen debe ser un archivo PNG, JPG o JPEG.',
            ])
            ->imagePreviewHeight('180')
            ->downloadable()
            ->openable()
            ->conversion('thumb')
            ->getUploadedFileNameForStorageUsing(
                fn (TemporaryUploadedFile $file, Get $get): string => self::buildStoredImageFilename(
                    $momento,
                    $get('service_tag'),
                    $get('fecha_soporte'),
                    $file->getClientOriginalExtension(),
                ),
            );
    }

    /**
     * Construye el nombre de archivo: {momento}_{service_tag}_{fecha}.{extensión}
     */
    public static function buildStoredImageFilename(
        string $momento,
        mixed $serviceTag,
        mixed $fechaSoporte,
        ?string $extension = null,
    ): string {
        $sanitizedServiceTag = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($serviceTag ?: ''));
        $sanitizedServiceTag = filled($sanitizedServiceTag) ? $sanitizedServiceTag : 'sin-equipo';

        $fechaFormateada = filled($fechaSoporte)
            ? Carbon::parse($fechaSoporte)->format('Y-m-d')
            : now()->format('Y-m-d');

        $normalizedExtension = strtolower((string) ($extension ?: 'png'));
        $normalizedExtension = $normalizedExtension === 'jpeg' ? 'jpg' : $normalizedExtension;

        return "{$momento}_{$sanitizedServiceTag}_{$fechaFormateada}.{$normalizedExtension}";
    }
}
