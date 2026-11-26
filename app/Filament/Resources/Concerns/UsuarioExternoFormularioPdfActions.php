<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\UsuarioExternoFormularioPdfTipo;
use App\Jobs\GenerateUsuarioExternoFormularioPdfJob;
use App\Models\Concerns\HasDocumentoOficialUsuarioExternoPdf;
use App\Models\UsuarioExterno;
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

class UsuarioExternoFormularioPdfActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        $tipo = UsuarioExternoFormularioPdfTipo::Formulario;

        return [
            self::preview($tipo),
            self::generate($tipo),
            self::view($tipo),
            self::download($tipo),
            FirmaEnOrigenActions::enviarAFirmar(self::resolverPdf()),
            FirmaEnOrigenActions::subirFirmado(self::resolverPdf()),
        ];
    }

    /**
     * PDF oficial del usuario externo (para el estado de firma).
     */
    public static function resolverPdf(): Closure
    {
        return fn (Model $record): mixed => self::usesDocumentoOficial($record) ? $record->documentoOficial() : null;
    }

    public static function preview(UsuarioExternoFormularioPdfTipo $tipo): Action
    {
        return Action::make('vistaPrevia')
            ->label('Vista previa')
            ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
            ->color('gray')
            ->visible(fn (Model $record): bool => $record instanceof UsuarioExterno
                && (! method_exists($record, 'trashed') || ! $record->trashed()))
            ->disabled(fn (Model $record): bool => app(DocumentosEnFirma::class)->envioEnCurso($record) !== null)
            ->tooltip(fn (Model $record): ?string => ($envio = app(DocumentosEnFirma::class)->envioEnCurso($record)) !== null
                ? app(DocumentosEnFirma::class)->mensaje($envio)
                : null)
            ->modalHeading('Vista previa — '.$tipo->nombreOficial())
            ->modalWidth(Width::FiveExtraLarge)
            ->modalAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (UsuarioExterno $record): View => view(
                'filament.recibo-equipos.view-document',
                [
                    'url' => route('usuarios-externos.preview-formulario', $record),
                    'filename' => $tipo->nombreOficial(),
                ],
            ));
    }

    public static function generate(UsuarioExternoFormularioPdfTipo $tipo): Action
    {
        $resolverPdf = self::resolverPdf();
        $firmado = fn (Model $record): bool => $resolverPdf($record)?->estaFirmado() ?? false;

        return Action::make('generarPdf')
            ->label(fn (UsuarioExterno $record): string => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial()
                ? 'Volver a generar PDF'
                : 'Generar PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('primary')
            ->visible(fn (Model $record): bool => $record instanceof UsuarioExterno
                && (! method_exists($record, 'trashed') || ! $record->trashed()))
            ->disabled(fn (Model $record): bool => app(DocumentosEnFirma::class)->envioEnCurso($record) !== null)
            ->tooltip(fn (Model $record): ?string => ($envio = app(DocumentosEnFirma::class)->envioEnCurso($record)) !== null
                ? app(DocumentosEnFirma::class)->mensaje($envio)
                : null)
            ->modalIcon(Heroicon::OutlinedDocumentArrowDown)
            ->modalHeading(fn (Model $record): string => $firmado($record) ? 'Regenerar documento firmado' : 'Generar PDF')
            ->modalDescription(fn (UsuarioExterno $record): string => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial()
                ? 'Se reemplazará el PDF oficial existente ('.$tipo->nombreOficial().').'
                : 'Se generará y guardará el documento «'.$tipo->nombreOficial().'».')
            ->modalSubmitActionLabel('Generar y guardar')
            ->modalSubmitAction(fn (Action $action, Model $record): Action => $action->color($firmado($record) ? 'danger' : 'primary'))
            ->requiresConfirmation(fn (?Model $record): bool => ! ($record !== null && $firmado($record)))
            ->schema(fn (): array => FirmaEnOrigenActions::advertencia($resolverPdf))
            ->action(function (UsuarioExterno $record, array $data) use ($tipo): void {
                GenerateUsuarioExternoFormularioPdfJob::dispatch(
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

    public static function view(UsuarioExternoFormularioPdfTipo $tipo): Action
    {
        return Action::make('viewDocument')
            ->label('Visualizar documento')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(fn (Model $record): bool => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial())
            ->modalHeading($tipo->nombreOficial())
            ->modalDescription(fn (UsuarioExterno $record): ?string => $record->documentoOficial()?->storedDocumentFilename())
            ->modalWidth(Width::FiveExtraLarge)
            ->modalAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn (UsuarioExterno $record): View => view(
                'filament.recibo-equipos.view-document',
                [
                    'url' => $record->documentoOficial()?->storedDocumentUrl(),
                    'filename' => $record->documentoOficial()?->storedDocumentFilename(),
                ],
            ));
    }

    public static function download(UsuarioExternoFormularioPdfTipo $tipo): Action
    {
        return Action::make('downloadDocument')
            ->label('Descargar documento')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (Model $record): bool => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial())
            ->action(function (UsuarioExterno $record): StreamedResponse {
                $documento = $record->documentoOficial();

                abort_unless($documento !== null && $documento->hasStoredDocument(), 404);

                return $documento->downloadStoredDocument();
            });
    }

    /**
     * @phpstan-assert-if-true HasDocumentoOficialUsuarioExternoPdf&Model $record
     */
    private static function usesDocumentoOficial(Model $record): bool
    {
        return in_array(HasDocumentoOficialUsuarioExternoPdf::class, class_uses_recursive($record), true);
    }
}
