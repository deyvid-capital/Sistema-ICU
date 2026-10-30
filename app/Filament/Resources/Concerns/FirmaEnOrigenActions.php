<?php

namespace App\Filament\Resources\Concerns;

use App\Enums\EstadoFirmaOrigen;
use App\Exceptions\FirmaDigitalException;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\PdfCelular;
use App\Models\PdfEquipo;
use App\Models\PdfMovimiento;
use App\Services\Archivos\ArchivosService;
use App\Services\Firma\DocumentosEnFirma;
use App\Services\Firma\DocumentosFirmables;
use App\Services\Firma\ReemplazoDocumentoFirmado;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Component as Livewire;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Piezas de firma digital en los módulos de origen de los PDF (entregas, recepciones,
 * notas, celulares, movimientos). Ver Docs/firma-digital/09-documentos-firmados-en-origen.md.
 *
 * Cada pieza recibe un `$resolverPdf`: Closure(Model $registro): PdfEquipo|PdfCelular|PdfMovimiento|null.
 */
class FirmaEnOrigenActions
{
    public const CAMPO_CONFIRMACION = 'confirmar_reemplazo_firmado';

    /**
     * Advertencia + casilla obligatoria para las acciones que reemplazan el archivo.
     * Solo aparecen si el PDF está firmado.
     *
     * @return array<int, Component|Checkbox>
     */
    public static function advertencia(Closure $resolverPdf): array
    {
        $firmado = fn (Model $record): bool => $resolverPdf($record)?->estaFirmado() ?? false;

        return [
            Callout::make('Este documento está firmado')
                ->danger()
                ->description(fn (Model $record): HtmlString => self::textoFirmas($resolverPdf($record)))
                ->visible($firmado),
            Checkbox::make(self::CAMPO_CONFIRMACION)
                ->label('Entiendo que se perderán las firmas de este documento')
                ->accepted()
                ->validationMessages(['accepted' => 'Confirma que se perderán las firmas para continuar.'])
                ->visible($firmado),
        ];
    }

    public static function confirmado(array $data): bool
    {
        return (bool) ($data[self::CAMPO_CONFIRMACION] ?? false);
    }

    public static function subirFirmado(Closure $resolverPdf): Action
    {
        return Action::make('subirFirmado')
            ->label('Subir firmado a mano')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->visible(fn (Model $record): bool => ($pdf = $resolverPdf($record)) !== null
                && $pdf->hasStoredDocument()
                && (bool) auth()->user()?->can(self::permisoSubir($pdf)))
            ->disabled(fn (Model $record): bool => self::enFirma($resolverPdf($record)) !== null)
            ->tooltip(fn (Model $record): ?string => self::enFirma($resolverPdf($record)))
            ->modalIcon(Heroicon::OutlinedArrowUpTray)
            ->modalHeading('Subir documento firmado a mano')
            ->modalDescription('Sube el escaneo del documento firmado en papel. Reemplaza al PDF actual en el sistema y queda marcado como «Firmado a mano».')
            ->modalSubmitActionLabel('Subir y reemplazar')
            ->schema(fn (): array => [
                FileUpload::make('archivo')
                    ->label('PDF escaneado y firmado')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(app(ArchivosService::class)->maxPdfKb())
                    ->storeFiles(false)
                    ->required(),
                ...self::advertencia($resolverPdf),
            ])
            ->action(function (Model $record, array $data) use ($resolverPdf): void {
                $pdf = $resolverPdf($record);
                $archivo = self::archivoSubido($data['archivo'] ?? null);

                abort_unless($pdf !== null && $archivo !== null && auth()->user()?->can(self::permisoSubir($pdf)), 403);

                try {
                    app(ReemplazoDocumentoFirmado::class)->subirFirmadoManual($pdf, (string) $archivo->get(), self::confirmado($data));
                } catch (FirmaDigitalException $exception) {
                    Notification::make()->danger()->title('No se pudo subir el documento')->body($exception->getMessage())->send();

                    return;
                } finally {
                    $archivo->delete();
                }

                Notification::make()->success()->title('Documento firmado a mano guardado')->send();
            });
    }

    public static function enviarAFirmar(Closure $resolverPdf): Action
    {
        return Action::make('enviarAFirmar')
            ->label('Enviar a firmar')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('primary')
            ->visible(fn (Model $record): bool => ($pdf = $resolverPdf($record)) !== null
                && $pdf->hasStoredDocument()
                && $pdf->esFirmable()
                && SolicitudFirmaResource::canCreate())
            ->url(fn (Model $record): string => SolicitudFirmaResource::getUrl('create', [
                'documentos' => DocumentosFirmables::clave($resolverPdf($record)),
            ]));
    }

    /**
     * Acción masiva en las tablas de PDF: abre el asistente con los firmables elegidos.
     */
    public static function enviarAFirmarMasivo(): BulkAction
    {
        return BulkAction::make('enviarAFirmar')
            ->label('Enviar a firmar')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->visible(fn (): bool => SolicitudFirmaResource::canCreate())
            ->action(function (Collection $records, Livewire $livewire): void {
                $claves = $records
                    ->filter(fn (Model $pdf): bool => $pdf->esFirmable() && $pdf->hasStoredDocument())
                    ->sortBy(fn (Model $pdf): mixed => $pdf->getKey())
                    ->map(fn (Model $pdf): ?string => DocumentosFirmables::clave($pdf))
                    ->filter()
                    ->values();

                if ($claves->isEmpty()) {
                    Notification::make()->warning()->title('Ninguno se puede enviar')->body('Los documentos elegidos ya están en firma, firmados o sin archivo.')->send();

                    return;
                }

                $livewire->redirect(SolicitudFirmaResource::getUrl('create', ['documentos' => $claves->implode(',')]));
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * Badge del estado de firma. Sin `$resolverPdf`, la fila es el propio PDF.
     */
    public static function columnaEstado(?Closure $resolverPdf = null): TextColumn
    {
        $nombre = $resolverPdf === null ? 'estado_firma' : 'estado_firma_pdf';
        $resolverPdf ??= fn (Model $record): Model => $record;

        return TextColumn::make($nombre)
            ->label('Firma')
            ->state(fn (Model $record): ?EstadoFirmaOrigen => $resolverPdf($record)?->estado_firma)
            ->badge()
            ->formatStateUsing(fn (Model $record): ?string => $resolverPdf($record)?->etiquetaEstadoFirma())
            ->placeholder('Sin PDF')
            ->toggleable();
    }

    /**
     * @param  string|null  $relacion  Relación hacia los PDF (`pdfs`, `entrega.pdfs`); null si la fila es el PDF.
     */
    public static function filtroEstado(?string $relacion = 'pdfs'): SelectFilter
    {
        return SelectFilter::make('estado_firma')
            ->label('Estado de firma')
            ->options(EstadoFirmaOrigen::class)
            ->query(function (Builder $query, array $data) use ($relacion): Builder {
                if (blank($data['value'] ?? null)) {
                    return $query;
                }

                return $relacion === null
                    ? $query->where('estado_firma', $data['value'])
                    : $query->whereHas($relacion, fn (Builder $query) => $query->where('estado_firma', $data['value']));
            });
    }

    /**
     * Permiso para subir un firmado a mano: `UploadSigned:PdfEquipo`, etc.
     */
    public static function permisoSubir(PdfEquipo|PdfCelular|PdfMovimiento $pdf): string
    {
        return 'UploadSigned:'.class_basename($pdf);
    }

    private static function enFirma(?Model $pdf): ?string
    {
        $envio = $pdf !== null ? app(DocumentosEnFirma::class)->envioEnCurso($pdf) : null;

        return $envio !== null ? app(DocumentosEnFirma::class)->mensaje($envio) : null;
    }

    private static function textoFirmas(PdfEquipo|PdfCelular|PdfMovimiento|null $pdf): HtmlString
    {
        if ($pdf === null) {
            return new HtmlString('');
        }

        $lineas = $pdf->estado_firma === EstadoFirmaOrigen::FirmadoManual
            ? ['Se subió firmado a mano el '.e($pdf->firmado_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i')).'.']
            : $pdf->firmasDigitales()->map(fn (array $firma): string => '• '.e($firma['nombre']).' — '.e($firma['fecha']))->all();

        $envio = $pdf->documentoFirma?->solicitud?->codigo;

        $lineas[] = '';
        $lineas[] = 'Si continúas, el archivo se reemplaza por uno nuevo <strong>sin firmas</strong> y tendrás que enviarlo a firmar otra vez.';

        if ($envio !== null) {
            $lineas[] = 'La versión firmada queda guardada en el envío '.e($envio).'.';
        }

        return new HtmlString(implode('<br>', $lineas));
    }

    private static function archivoSubido(mixed $valor): ?TemporaryUploadedFile
    {
        $archivo = is_array($valor) ? reset($valor) : $valor;

        return $archivo instanceof TemporaryUploadedFile ? $archivo : null;
    }
}
