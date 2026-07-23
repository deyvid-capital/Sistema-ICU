<?php

namespace App\Filament\Resources\PdfCelulars\Actions;

use App\Enums\CelularFormularioPdfTipo;
use App\Jobs\GenerateCelularFormularioPdfJob;
use App\Models\PdfCelular;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfCelularDocumentActions
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
            ->visible(fn (PdfCelular $record): bool => $record->hasStoredDocument())
            ->modalHeading('Documento PDF')
            ->modalDescription(fn (PdfCelular $record): ?string => $record->storedDocumentFilename())
            ->modalWidth(Width::FiveExtraLarge)
            ->modalAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (PdfCelular $record): View => view(
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
            ->visible(fn (PdfCelular $record): bool => $record->hasStoredDocument())
            ->action(fn (PdfCelular $record): StreamedResponse => $record->downloadStoredDocument());
    }

    public static function regenerate(): Action
    {
        return Action::make('regenerate')
            ->label('Generar de nuevo')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->visible(fn (PdfCelular $record): bool => self::resolveTipo($record) !== null
                && $record->documento !== null)
            ->modalIcon(Heroicon::OutlinedArrowPath)
            ->modalHeading('Generar PDF de nuevo')
            ->modalDescription('Se generará nuevamente el documento en segundo plano y reemplazará el archivo actual. Se te avisará cuando esté listo.')
            ->modalSubmitActionLabel('Generar y guardar')
            ->requiresConfirmation()
            ->action(function (PdfCelular $record): void {
                $tipo = self::resolveTipo($record);
                $documento = $record->documento;

                abort_if($tipo === null || $documento === null, 404);

                GenerateCelularFormularioPdfJob::dispatch(
                    $documento,
                    $tipo,
                    auth()->id(),
                );

                Notification::make()
                    ->title('Generando PDF…')
                    ->body('El documento «'.$tipo->nombreOficial().'» se está generando en segundo plano. Se te avisará cuando esté listo.')
                    ->info()
                    ->send();
            });
    }

    private static function resolveTipo(PdfCelular $record): ?CelularFormularioPdfTipo
    {
        return CelularFormularioPdfTipo::tryFromNombreOficial($record->nombre);
    }
}
