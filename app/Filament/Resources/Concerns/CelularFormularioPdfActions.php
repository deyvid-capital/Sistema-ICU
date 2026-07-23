<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\CelularFormularioPdfTipo;
use App\Jobs\GenerateCelularFormularioPdfJob;
use App\Models\Concerns\HasDocumentoOficialCelularPdf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CelularFormularioPdfActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(CelularFormularioPdfTipo $tipo): array
    {
        return [
            self::generate($tipo),
            self::view($tipo),
            self::download($tipo),
        ];
    }

    public static function generate(CelularFormularioPdfTipo $tipo): Action
    {
        return Action::make('generarPdf')
            ->label('Generar PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('primary')
            ->visible(fn (Model $record): bool => ! method_exists($record, 'trashed') || ! $record->trashed())
            ->modalIcon(Heroicon::OutlinedDocumentArrowDown)
            ->modalHeading('Generar PDF')
            ->modalDescription(fn (Model $record): string => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial()
                ? 'Se reemplazará el PDF oficial existente ('.$tipo->nombreOficial().').'
                : 'Se generará y guardará el documento «'.$tipo->nombreOficial().'».')
            ->modalSubmitActionLabel('Generar y guardar')
            ->requiresConfirmation()
            ->action(function (Model $record) use ($tipo): void {
                GenerateCelularFormularioPdfJob::dispatch(
                    $record,
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

    public static function view(CelularFormularioPdfTipo $tipo): Action
    {
        return Action::make('viewDocument')
            ->label('Visualizar documento')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(fn (Model $record): bool => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial())
            ->modalHeading($tipo->nombreOficial())
            ->modalDescription(fn (Model $record): ?string => $record->documentoOficial()?->storedDocumentFilename())
            ->modalWidth(Width::FiveExtraLarge)
            ->modalAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (Model $record): View => view(
                'filament.recibo-equipos.view-document',
                [
                    'url' => $record->documentoOficial()?->storedDocumentUrl(),
                    'filename' => $record->documentoOficial()?->storedDocumentFilename(),
                ],
            ));
    }

    public static function download(CelularFormularioPdfTipo $tipo): Action
    {
        return Action::make('downloadDocument')
            ->label('Descargar documento')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (Model $record): bool => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial())
            ->action(function (Model $record): StreamedResponse {
                $documento = $record->documentoOficial();

                abort_unless($documento !== null && $documento->hasStoredDocument(), 404);

                return $documento->downloadStoredDocument();
            });
    }

    /**
     * @phpstan-assert-if-true HasDocumentoOficialCelularPdf&Model $record
     */
    private static function usesDocumentoOficial(Model $record): bool
    {
        return in_array(HasDocumentoOficialCelularPdf::class, class_uses_recursive($record), true);
    }
}
