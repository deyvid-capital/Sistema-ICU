<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\EquipoFormularioPdfTipo;
use App\Jobs\GenerateEquipoFormularioPdfJob;
use App\Models\Concerns\HasDocumentoOficialPdf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquipoFormularioPdfActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(EquipoFormularioPdfTipo $tipo): array
    {
        return [
            self::generate($tipo),
            self::view($tipo),
            self::download($tipo),
        ];
    }

    public static function generate(EquipoFormularioPdfTipo $tipo): Action
    {
        return Action::make('generarPdf')
            ->label('Generar PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('primary')
            ->visible(fn (Model $record): bool => ! method_exists($record, 'trashed') || ! $record->trashed())
            ->requiresConfirmation()
            ->modalHeading('Generar PDF')
            ->modalDescription(fn (Model $record): string => self::usesDocumentoOficial($record) && $record->hasDocumentoOficial()
                ? 'Se reemplazará el PDF oficial existente ('.$tipo->nombreOficial().').'
                : 'Se generará y guardará el documento «'.$tipo->nombreOficial().'».')
            ->modalSubmitActionLabel('Generar y guardar')
            ->action(function (Model $record) use ($tipo): void {
                GenerateEquipoFormularioPdfJob::dispatch($record, $tipo, auth()->id());

                Notification::make()
                    ->title('Generando PDF…')
                    ->body('El documento «'.$tipo->nombreOficial().'» se está generando en segundo plano. Se te avisará cuando esté listo.')
                    ->info()
                    ->send();
            });
    }

    public static function view(EquipoFormularioPdfTipo $tipo): Action
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

    public static function download(EquipoFormularioPdfTipo $tipo): Action
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
     * @phpstan-assert-if-true HasDocumentoOficialPdf&Model $record
     */
    private static function usesDocumentoOficial(Model $record): bool
    {
        return in_array(HasDocumentoOficialPdf::class, class_uses_recursive($record), true);
    }
}
