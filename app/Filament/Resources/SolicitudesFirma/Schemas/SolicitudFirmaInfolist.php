<?php

namespace App\Filament\Resources\SolicitudesFirma\Schemas;

use App\Enums\EstadoDocumentoFirma;
use App\Models\SolicitudFirma;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
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
                        TextEntry::make('motivo_cancelacion')
                            ->label('Motivo de cancelación')
                            ->visible(fn (SolicitudFirma $record): bool => filled($record->motivo_cancelacion))
                            ->columnSpanFull(),
                        TextEntry::make('asunto')
                            ->label('Asunto')
                            ->columnSpanFull(),
                        TextEntry::make('mensaje')
                            ->label('Mensaje')
                            ->visible(fn (SolicitudFirma $record): bool => filled($record->mensaje))
                            ->columnSpanFull(),
                        TextEntry::make('fecha_limite')
                            ->label('Fecha límite')
                            ->date('d/m/Y')
                            ->placeholder('Sin fecha límite'),
                        TextEntry::make('recordatorio_cada_horas')
                            ->label('Recordatorio')
                            ->formatStateUsing(fn (?int $state): string => "Cada {$state} horas")
                            ->placeholder('Sin recordatorios'),
                        TextEntry::make('orden_obligatorio')
                            ->label('Orden de firma')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Orden obligatorio' : 'En cualquier orden'),
                    ]),
                Section::make('Documentos')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->description(fn (SolicitudFirma $record): string => self::resumenDocumentos($record))
                    ->schema(fn (SolicitudFirma $record): array => [
                        ViewEntry::make('detalle_documentos')
                            ->hiddenLabel()
                            ->view('filament.resources.solicitudes-firma.documentos', [
                                'documentos' => $record->documentos()->with('firmas.firmante.usuario')->orderBy('id')->get(),
                            ]),
                    ]),
                Section::make('Firmantes')
                    ->icon(Heroicon::OutlinedUsers)
                    ->description(fn (SolicitudFirma $record): string => $record->orden_obligatorio
                        ? 'Firman uno tras otro, en este orden.'
                        : 'Firman en cualquier orden, cuando estén disponibles.')
                    ->schema(fn (SolicitudFirma $record): array => [
                        ViewEntry::make('detalle_firmantes')
                            ->hiddenLabel()
                            ->view('filament.resources.solicitudes-firma.firmantes', [
                                'firmantes' => $record->firmantes()->with(['usuario.certificadoFirmaActivo', 'firmas'])->get(),
                            ]),
                    ]),
            ]);
    }

    private static function resumenDocumentos(SolicitudFirma $record): string
    {
        $documentos = $record->documentos()->count();
        $completados = $record->documentos()->where('estado', EstadoDocumentoFirma::Completado)->count();

        return $documentos === 1
            ? ($completados === 1 ? 'El documento tiene todas sus firmas.' : '1 documento en este envío.')
            : "{$completados} de {$documentos} documentos con todas sus firmas.";
    }
}
