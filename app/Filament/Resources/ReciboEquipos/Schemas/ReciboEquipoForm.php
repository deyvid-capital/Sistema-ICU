<?php

namespace App\Filament\Resources\ReciboEquipos\Schemas;

use App\Filament\Resources\Equipos\RelationManagers\RecibosEquipoRelationManager;
use App\Models\Equipo;
use App\Models\ReciboEquipo;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ReciboEquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Recibo de equipo')
                    ->description('Registra el recibo asociado a un equipo y, si aplica, el enlace al documento.')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
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
                                    ->live()
                                    ->rule(new NotTrashed(Equipo::class))
                                    ->hiddenOn(RecibosEquipoRelationManager::class),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required()
                                    ->live(),
                                FileUpload::make('url')
                                    ->label('Documento Recibo')
                                    ->disk('public')
                                    ->directory('recibo-equipos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(10240)
                                    ->downloadable()
                                    ->openable()
                                    ->getUploadedFileNameForStorageUsing(
                                        fn (TemporaryUploadedFile $file, Get $get, Component $livewire, ?ReciboEquipo $record): string => self::buildStoredDocumentFilename(
                                            self::resolveServiceTagForFilename($get, $livewire, $record),
                                            $get('fecha'),
                                        ),
                                    )
                                    ->required()
                                    ->columnSpanFull(),
                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    /**
     * Resuelve el service_tag desde el formulario, el equipo dueño del
     * relation manager o el recibo en edición.
     */
    public static function resolveServiceTagForFilename(
        Get $get,
        ?Component $livewire = null,
        ?ReciboEquipo $record = null,
    ): string {
        $serviceTag = $get('equipo_id');

        if (blank($serviceTag) && $livewire instanceof RelationManager) {
            $owner = $livewire->getOwnerRecord();

            if ($owner instanceof Equipo) {
                $serviceTag = $owner->service_tag;
            }
        }

        if (blank($serviceTag) && filled($record?->equipo_id)) {
            $serviceTag = $record->equipo_id;
        }

        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($serviceTag ?: ''));

        return filled($sanitized) ? $sanitized : 'sin-equipo';
    }

    public static function buildStoredDocumentFilename(string $serviceTag, mixed $fecha = null): string
    {
        $fechaFormateada = filled($fecha)
            ? Carbon::parse($fecha)->format('Y-m-d')
            : now()->format('Y-m-d');

        return "{$serviceTag}_{$fechaFormateada}.pdf";
    }
}
