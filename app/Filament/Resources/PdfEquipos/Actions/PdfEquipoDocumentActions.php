<?php

namespace App\Filament\Resources\PdfEquipos\Actions;

use App\Models\PdfEquipo;
use Filament\Actions\Action;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfEquipoDocumentActions
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
            ->visible(fn (PdfEquipo $record): bool => $record->hasStoredDocument())
            ->modalHeading('Documento PDF')
            ->modalDescription(fn (PdfEquipo $record): ?string => $record->storedDocumentFilename())
            ->modalWidth(Width::FiveExtraLarge)
            ->modalAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (PdfEquipo $record): View => view(
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
            ->visible(fn (PdfEquipo $record): bool => $record->hasStoredDocument())
            ->action(fn (PdfEquipo $record): StreamedResponse => $record->downloadStoredDocument());
    }
}
