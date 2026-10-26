<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\EquipoFormularioPdfTipo;
use App\Jobs\GenerateEquipoFormularioPdfJob;
use App\Models\AsignacionEquipo;
use App\Models\Concerns\HasDocumentoOficialPdf;
use App\Models\EntregaEquipo;
use App\Models\User;
use App\Services\Firma\DocumentosEnFirma;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EquipoFormularioPdfActions
{
    /**
     * @param  Closure(Model): (?Model)|null  $resolveDocument
     * @return array<int, Action>
     */
    public static function make(EquipoFormularioPdfTipo $tipo, ?Closure $resolveDocument = null): array
    {
        $resolve = $resolveDocument ?? fn (Model $record): Model => $record;

        return [
            self::generate($tipo, $resolve),
            self::view($tipo, $resolve),
            self::download($tipo, $resolve),
        ];
    }

    /**
     * Acciones PDF del formulario de entrega asociado a una asignación.
     *
     * @return array<int, Action>
     */
    public static function makeForAsignacionEntrega(): array
    {
        return self::make(
            EquipoFormularioPdfTipo::Entrega,
            fn (Model $record): ?EntregaEquipo => $record instanceof AsignacionEquipo
                ? $record->entrega
                : null,
        );
    }

    /**
     * @param  Closure(Model): (?Model)|null  $resolveDocument
     */
    public static function generate(EquipoFormularioPdfTipo $tipo, ?Closure $resolveDocument = null): Action
    {
        $resolveDocument ??= fn (Model $record): Model => $record;
        $schema = self::generateSchema($tipo);

        $action = Action::make('generarPdf')
            ->label(fn (Model $record): string => self::documentHasOficial($resolveDocument($record))
                ? 'Volver a generar PDF'
                : 'Generar PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('primary')
            ->visible(fn (Model $record): bool => self::documentIsActionable($resolveDocument($record)))
            ->disabled(fn (Model $record): bool => self::enFirmaDigital($resolveDocument($record)) !== null)
            ->tooltip(fn (Model $record): ?string => self::enFirmaDigital($resolveDocument($record)))
            ->modalIcon(Heroicon::OutlinedDocumentArrowDown)
            ->modalHeading(fn (Model $record): string => self::documentHasOficial($resolveDocument($record))
                ? 'Volver a generar PDF'
                : 'Generar PDF')
            ->modalDescription(fn (Model $record): string => self::documentHasOficial($resolveDocument($record))
                ? 'Se reemplazará el PDF oficial existente ('.$tipo->nombreOficial().').'
                : 'Se generará y guardará el documento «'.$tipo->nombreOficial().'».')
            ->modalSubmitActionLabel(fn (Model $record): string => self::documentHasOficial($resolveDocument($record))
                ? 'Volver a generar'
                : 'Generar y guardar')
            ->action(function (Model $record, array $data) use ($tipo, $resolveDocument): void {
                $document = $resolveDocument($record);

                abort_unless($document instanceof Model, 404);

                GenerateEquipoFormularioPdfJob::dispatch(
                    $document,
                    $tipo,
                    auth()->id(),
                    $data['firmante_id'] ?? null,
                );

                Notification::make()
                    ->title('Generando PDF…')
                    ->body('El documento «'.$tipo->nombreOficial().'» se está generando en segundo plano. Se te avisará cuando esté listo.')
                    ->info()
                    ->send();
            });

        // Cuando hay campos que capturar (p. ej. el firmante), el modal con schema
        // ya actúa como confirmación. requiresConfirmation() no debe combinarse con
        // un schema porque oculta los campos del formulario.
        if ($schema === []) {
            $action->requiresConfirmation();
        } else {
            $action->schema($schema);
        }

        return $action;
    }

    /**
     * Mensaje si el documento se está firmando digitalmente (no se puede regenerar), o null.
     */
    private static function enFirmaDigital(?Model $document): ?string
    {
        $envio = $document instanceof Model ? app(DocumentosEnFirma::class)->envioEnCurso($document) : null;

        return $envio !== null ? app(DocumentosEnFirma::class)->mensaje($envio) : null;
    }

    /**
     * Campos que se solicitan en el modal antes de generar el PDF.
     * El firmante seleccionado solo se usa para el documento; no se persiste.
     *
     * @return array<int, Component>
     */
    private static function generateSchema(EquipoFormularioPdfTipo $tipo): array
    {
        if ($tipo !== EquipoFormularioPdfTipo::NotaRecepcion) {
            return [];
        }

        return [
            Select::make('firmante_id')
                ->label('Usuario que firma')
                ->helperText('Aparecerá como firma derecha del documento. No se guarda en la base de datos.')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload(),
        ];
    }

    /**
     * @param  Closure(Model): (?Model)|null  $resolveDocument
     */
    public static function view(EquipoFormularioPdfTipo $tipo, ?Closure $resolveDocument = null): Action
    {
        $resolveDocument ??= fn (Model $record): Model => $record;

        return Action::make('viewDocument')
            ->label('Visualizar documento')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->visible(fn (Model $record): bool => self::documentHasOficial($resolveDocument($record)))
            ->modalHeading($tipo->nombreOficial())
            ->modalDescription(fn (Model $record): ?string => self::documentoOficial($resolveDocument($record))?->storedDocumentFilename())
            ->modalWidth(Width::FiveExtraLarge)
            ->modalAlignment(Alignment::Center)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(function (Model $record) use ($resolveDocument): View {
                $documento = self::documentoOficial($resolveDocument($record));

                return view(
                    'filament.recibo-equipos.view-document',
                    [
                        'url' => $documento?->storedDocumentUrl(),
                        'filename' => $documento?->storedDocumentFilename(),
                    ],
                );
            });
    }

    /**
     * @param  Closure(Model): (?Model)|null  $resolveDocument
     */
    public static function download(EquipoFormularioPdfTipo $tipo, ?Closure $resolveDocument = null): Action
    {
        $resolveDocument ??= fn (Model $record): Model => $record;

        return Action::make('downloadDocument')
            ->label('Descargar documento')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (Model $record): bool => self::documentHasOficial($resolveDocument($record)))
            ->action(function (Model $record) use ($resolveDocument): StreamedResponse {
                $documento = self::documentoOficial($resolveDocument($record));

                abort_unless($documento !== null && $documento->hasStoredDocument(), 404);

                return $documento->downloadStoredDocument();
            });
    }

    private static function documentIsActionable(?Model $document): bool
    {
        if ($document === null) {
            return false;
        }

        return ! method_exists($document, 'trashed') || ! $document->trashed();
    }

    /**
     * @phpstan-assert-if-true HasDocumentoOficialPdf&Model $document
     */
    private static function documentHasOficial(?Model $document): bool
    {
        if ($document === null || ! self::usesDocumentoOficial($document)) {
            return false;
        }

        return $document->hasDocumentoOficial();
    }

    private static function documentoOficial(?Model $document): mixed
    {
        if ($document === null || ! self::usesDocumentoOficial($document)) {
            return null;
        }

        return $document->documentoOficial();
    }

    /**
     * @phpstan-assert-if-true HasDocumentoOficialPdf&Model $record
     */
    private static function usesDocumentoOficial(Model $record): bool
    {
        return in_array(HasDocumentoOficialPdf::class, class_uses_recursive($record), true);
    }
}
