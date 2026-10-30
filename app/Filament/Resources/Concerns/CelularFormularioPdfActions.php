<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\CelularFormularioPdfTipo;
use App\Jobs\GenerateCelularFormularioPdfJob;
use App\Models\Concerns\HasDocumentoOficialCelularPdf;
use App\Services\Firma\DocumentosEnFirma;
use Closure;
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
            FirmaEnOrigenActions::enviarAFirmar(self::resolverPdf()),
            FirmaEnOrigenActions::subirFirmado(self::resolverPdf()),
        ];
    }

    /**
     * PDF oficial del registro (para el estado de firma).
     */
    public static function resolverPdf(): Closure
    {
        return fn (Model $record): mixed => self::usesDocumentoOficial($record) ? $record->documentoOficial() : null;
    }

    public static function generate(CelularFormularioPdfTipo $tipo): Action
    {
        $resolverPdf = self::resolverPdf();
        $firmado = fn (Model $record): bool => $resolverPdf($record)?->estaFirmado() ?? false;

        return Action::make('generarPdf')
            ->label('Generar PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('primary')
            ->visible(fn (Model $record): bool => ! method_exists($record, 'trashed') || ! $record->trashed())
            ->disabled(fn (Model $record): bool => app(DocumentosEnFirma::class)->envioEnCurso($record) !== null)
            ->tooltip(fn (Model $record): ?string => ($envio = app(DocumentosEnFirma::class)->envioEnCurso($record)) !== null
                ? app(DocumentosEnFirma::class)->mensaje($envio)
                : null)
            ->modalIcon(Heroicon::OutlinedDocumentArrowDown)
            ->modalHeading(fn (Model $record): string => $firmado($record) ? 'Regenerar documento firmado' : 'Generar PDF')
            ->modalDescription(fn (Model $record): string => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial()
                ? 'Se reemplazará el PDF oficial existente ('.$tipo->nombreOficial().').'
                : 'Se generará y guardará el documento «'.$tipo->nombreOficial().'».')
            ->modalSubmitActionLabel('Generar y guardar')
            ->modalSubmitAction(fn (Action $action, Model $record): Action => $action->color($firmado($record) ? 'danger' : 'primary'))
            ->requiresConfirmation(fn (?Model $record): bool => ! ($record !== null && $firmado($record)))
            ->schema(fn (): array => FirmaEnOrigenActions::advertencia($resolverPdf))
            ->action(function (Model $record, array $data) use ($tipo): void {
                GenerateCelularFormularioPdfJob::dispatch(
                    $record,
                    $tipo,
                    auth()->id(),
                    FirmaEnOrigenActions::confirmado($data),
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
