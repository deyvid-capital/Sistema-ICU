<?php

namespace App\Filament\Resources\ReciboEquipos\Actions;

use App\Models\ReciboEquipo;
use Filament\Actions\Action;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReciboEquipoDocumentActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        return [
            self::view(),
            self::download(),
        ];
    }

    public static function view(): Action
    {
        return Action::make('viewDocument')
            ->label('Visualizar documento')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(fn (ReciboEquipo $record): bool => $record->hasStoredDocument())
            ->modalHeading('Documento del recibo')
            ->modalDescription(fn (ReciboEquipo $record): ?string => $record->storedDocumentFilename())
            ->modalWidth(Width::FiveExtraLarge)
            ->modalAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (ReciboEquipo $record): View => view(
                'filament.recibo-equipos.view-document',
                [
                    'url' => $record->storedDocumentUrl(),
                    'filename' => $record->storedDocumentFilename(),
                ],
            ));
    }

    public static function download(): Action
    {
        return Action::make('downloadDocument')
            ->label('Descargar documento')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (ReciboEquipo $record): bool => $record->hasStoredDocument())
            ->action(fn (ReciboEquipo $record): StreamedResponse => $record->downloadStoredDocument());
    }

    /**
     * Abre el documento directamente en una pestaña nueva (sin modal).
     * Se usa donde el modal de vista previa no está disponible, como en los
     * gestores de relaciones.
     */
    public static function viewFullScreen(): Action
    {
        return Action::make('verDocumentoPantallaCompleta')
            ->label('Ver en pantalla completa')
            ->icon(Heroicon::OutlinedArrowsPointingOut)
            ->color('gray')
            ->visible(fn (ReciboEquipo $record): bool => $record->hasStoredDocument())
            ->url(fn (ReciboEquipo $record): ?string => $record->storedDocumentUrl())
            ->openUrlInNewTab();
    }
}
