<?php

namespace App\Filament\Resources\SolicitudesFirma\Schemas;

use App\Models\SolicitudFirmaDocumento;
use App\Models\SolicitudFirmaFirmante;
use App\Services\Firma\DocumentosFirmables;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class SolicitudFirmaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Envío')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('codigo')
                            ->label('Código')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge(),
                        TextEntry::make('creador.name')
                            ->label('Enviado por'),
                        TextEntry::make('asunto')
                            ->label('Asunto')
                            ->columnSpanFull(),
                        TextEntry::make('mensaje')
                            ->label('Mensaje')
                            ->placeholder('Sin mensaje')
                            ->columnSpanFull(),
                        TextEntry::make('fecha_limite')
                            ->label('Fecha límite')
                            ->date('d/m/Y')
                            ->placeholder('Sin fecha límite'),
                        TextEntry::make('recordatorio_cada_horas')
                            ->label('Recordatorio')
                            ->formatStateUsing(fn (?int $state): string => "Cada {$state} horas")
                            ->placeholder('Sin recordatorios'),
                        IconEntry::make('orden_obligatorio')
                            ->label('Orden obligatorio')
                            ->boolean(),
                    ]),
                Section::make('Documentos')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->schema([
                        RepeatableEntry::make('documentos')
                            ->hiddenLabel()
                            ->columns(['default' => 1, 'md' => 4])
                            ->schema([
                                TextEntry::make('nombre')
                                    ->label('Archivo')
                                    ->icon(Heroicon::OutlinedDocument)
                                    ->url(fn (SolicitudFirmaDocumento $record): string => route('firma.documentos.ver', $record), shouldOpenInNewTab: true)
                                    ->color('primary'),
                                TextEntry::make('origen_type')
                                    ->label('Origen')
                                    ->state(fn (SolicitudFirmaDocumento $record): string => app(DocumentosFirmables::class)->etiquetaOrigen($record->origen_type)),
                                TextEntry::make('paginas')
                                    ->label('Páginas')
                                    ->state(fn (SolicitudFirmaDocumento $record): int => $record->totalPaginas()),
                                TextEntry::make('estado')
                                    ->label('Estado')
                                    ->badge(),
                            ]),
                    ]),
                Section::make('Firmantes')
                    ->icon(Heroicon::OutlinedUsers)
                    ->description(fn ($record): string => $record->orden_obligatorio
                        ? 'Firman uno tras otro, en este orden.'
                        : 'Firman en cualquier orden, cuando estén disponibles.')
                    ->schema([
                        RepeatableEntry::make('firmantes')
                            ->hiddenLabel()
                            ->columns(['default' => 1, 'md' => 3])
                            ->schema([
                                TextEntry::make('orden')
                                    ->label('#'),
                                TextEntry::make('usuario.name')
                                    ->label('Nombre'),
                                TextEntry::make('cargo')
                                    ->label('Cargo en el sello')
                                    ->state(fn (SolicitudFirmaFirmante $record): ?string => $record->usuario?->certificadoFirmaActivo?->cargo_en_sello)
                                    ->placeholder('—'),
                            ]),
                    ]),
            ]);
    }
}
