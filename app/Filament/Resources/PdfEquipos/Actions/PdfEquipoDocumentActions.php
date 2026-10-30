<?php

namespace App\Filament\Resources\PdfEquipos\Actions;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\Concerns\FirmaEnOrigenActions;
use App\Jobs\GenerateEquipoFormularioPdfJob;
use App\Models\PdfEquipo;
use App\Models\User;
use App\Services\Firma\DocumentosEnFirma;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfEquipoDocumentActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(): array
    {
        $propio = fn (Model $record): Model => $record;

        return [
            self::view(),
            self::download(),
            FirmaEnOrigenActions::enviarAFirmar($propio),
            FirmaEnOrigenActions::subirFirmado($propio),
        ];
    }

    public static function regenerate(): Action
    {
        return Action::make('regenerate')
            ->label('Generar de nuevo')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->visible(fn (PdfEquipo $record): bool => self::resolveTipo($record) !== null
                && $record->documento !== null)
            ->disabled(fn (PdfEquipo $record): bool => app(DocumentosEnFirma::class)->envioEnCurso($record) !== null)
            ->modalSubmitAction(fn (Action $action, PdfEquipo $record): Action => $action->color($record->estaFirmado() ? 'danger' : 'primary'))
            ->modalIcon(Heroicon::OutlinedArrowPath)
            ->modalHeading('Generar PDF de nuevo')
            ->modalDescription('Se generará nuevamente el documento en segundo plano y reemplazará el archivo actual. Se te avisará cuando esté listo.')
            ->modalSubmitActionLabel('Generar y guardar')
            ->schema(fn (PdfEquipo $record): array => [
                ...(self::resolveTipo($record) === EquipoFormularioPdfTipo::NotaRecepcion
                    ? [
                        Select::make('firmante_id')
                            ->label('Usuario que firma')
                            ->helperText('Aparecerá como firma derecha del documento. No se guarda en la base de datos.')
                            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                    ]
                    : []),
                ...FirmaEnOrigenActions::advertencia(fn (Model $record): Model => $record),
            ])
            ->action(function (PdfEquipo $record, array $data): void {
                $tipo = self::resolveTipo($record);
                $documento = $record->documento;

                abort_if($tipo === null || $documento === null, 404);

                GenerateEquipoFormularioPdfJob::dispatch(
                    $documento,
                    $tipo,
                    auth()->id(),
                    $data['firmante_id'] ?? null,
                    FirmaEnOrigenActions::confirmado($data),
                );

                Notification::make()
                    ->title('Generando PDF…')
                    ->body('El documento «'.$tipo->nombreOficial().'» se está generando en segundo plano. Se te avisará cuando esté listo.')
                    ->info()
                    ->send();
            });
    }

    private static function resolveTipo(PdfEquipo $record): ?EquipoFormularioPdfTipo
    {
        return EquipoFormularioPdfTipo::tryFromNombreOficial($record->nombre);
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
