<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\MovimientoFormularioPdfTipo;
use App\Jobs\GenerateMovimientoFormularioPdfJob;
use App\Models\Concerns\HasDocumentoOficialMovimientoPdf;
use App\Models\Movimiento;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MovimientoFormularioPdfActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        $tipo = MovimientoFormularioPdfTipo::AltaBaja;

        return [
            self::preview($tipo),
            self::generate($tipo),
            self::view($tipo),
            self::download($tipo),
        ];
    }

    public static function preview(MovimientoFormularioPdfTipo $tipo): Action
    {
        return Action::make('vistaPrevia')
            ->label('Vista previa')
            ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
            ->color('gray')
            ->visible(fn (Model $record): bool => $record instanceof Movimiento
                && (! method_exists($record, 'trashed') || ! $record->trashed()))
            ->modalHeading('Vista previa — '.$tipo->nombreOficial())
            ->modalWidth(Width::FiveExtraLarge)
            ->modalAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (Movimiento $record): View => view(
                'filament.recibo-equipos.view-document',
                [
                    'url' => route('movimientos.preview-alta-baja', $record),
                    'filename' => $tipo->nombreOficial(),
                ],
            ));
    }

    public static function generate(MovimientoFormularioPdfTipo $tipo): Action
    {
        return Action::make('generarPdf')
            ->label('Generar PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('primary')
            ->visible(fn (Model $record): bool => $record instanceof Movimiento
                && (! method_exists($record, 'trashed') || ! $record->trashed()))
            ->modalIcon(Heroicon::OutlinedDocumentArrowDown)
            ->modalHeading('Generar PDF')
            ->modalDescription(fn (Movimiento $record): string => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial()
                ? 'Se reemplazará el PDF oficial existente ('.$tipo->nombreOficial().').'
                : 'Se generará y guardará el documento «'.$tipo->nombreOficial().'».')
            ->modalSubmitActionLabel('Generar y guardar')
            ->requiresConfirmation()
            ->action(function (Movimiento $record) use ($tipo): void {
                GenerateMovimientoFormularioPdfJob::dispatch(
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

    public static function view(MovimientoFormularioPdfTipo $tipo): Action
    {
        return Action::make('viewDocument')
            ->label('Visualizar documento')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(fn (Model $record): bool => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial())
            ->modalHeading($tipo->nombreOficial())
            ->modalDescription(fn (Movimiento $record): ?string => $record->documentoOficial()?->storedDocumentFilename())
            ->modalWidth(Width::FiveExtraLarge)
            ->modalAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (Movimiento $record): View => view(
                'filament.recibo-equipos.view-document',
                [
                    'url' => $record->documentoOficial()?->storedDocumentUrl(),
                    'filename' => $record->documentoOficial()?->storedDocumentFilename(),
                ],
            ));
    }

    public static function download(MovimientoFormularioPdfTipo $tipo): Action
    {
        return Action::make('downloadDocument')
            ->label('Descargar documento')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (Model $record): bool => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial())
            ->action(function (Movimiento $record): StreamedResponse {
                $documento = $record->documentoOficial();

                abort_unless($documento !== null && $documento->hasStoredDocument(), 404);

                return $documento->downloadStoredDocument();
            });
    }

    /**
     * @phpstan-assert-if-true HasDocumentoOficialMovimientoPdf&Model $record
     */
    private static function usesDocumentoOficial(Model $record): bool
    {
        return in_array(HasDocumentoOficialMovimientoPdf::class, class_uses_recursive($record), true);
    }
}
