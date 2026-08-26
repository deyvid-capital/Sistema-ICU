<?php

namespace App\Filament\Resources\PdfCelulars\Schemas;

use App\Filament\Resources\EntregaCelulars\RelationManagers\PdfsRelationManager as EntregaCelularPdfsRelationManager;
use App\Filament\Resources\RecepcionCelulars\RelationManagers\PdfsRelationManager as RecepcionCelularPdfsRelationManager;
use App\Models\PdfCelular;
use App\Services\Archivos\ArchivosService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PdfCelularForm
{
    /**
     * @var list<class-string>
     */
    private const OWNER_RELATION_MANAGERS = [
        EntregaCelularPdfsRelationManager::class,
        RecepcionCelularPdfsRelationManager::class,
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Documento PDF')
                    ->description('Archivo PDF asociado al formulario de entrega o recepción de celular.')
                    ->icon(Heroicon::OutlinedDocument)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(50)
                                    ->live(onBlur: true),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now()),
                                TextInput::make('documento_type')
                                    ->label('Tipo de documento')
                                    ->required()
                                    ->hiddenOn(self::OWNER_RELATION_MANAGERS),
                                TextInput::make('documento_id')
                                    ->label('ID del documento')
                                    ->required()
                                    ->numeric()
                                    ->hiddenOn(self::OWNER_RELATION_MANAGERS),
                                FileUpload::make('url')
                                    ->label('Archivo PDF')
                                    ->disk(fn (): string => PdfCelular::documentDisk())
                                    ->directory(fn (): string => PdfCelular::documentDirectory())
                                    ->visibility(fn (): string => app(ArchivosService::class)->visibilidad())
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(fn (): int => app(ArchivosService::class)->maxPdfKb())
                                    ->downloadable()
                                    ->openable()
                                    ->getUploadedFileNameForStorageUsing(
                                        function (TemporaryUploadedFile $file, Get $get): string {
                                            $nombre = Str::slug((string) ($get('nombre') ?: 'documento'), '_');
                                            $fecha = $get('fecha');
                                            $fechaFormateada = filled($fecha)
                                                ? Carbon::parse($fecha)->format('Y-m-d')
                                                : now()->format('Y-m-d');

                                            return "{$nombre}_{$fechaFormateada}.pdf";
                                        },
                                    )
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
