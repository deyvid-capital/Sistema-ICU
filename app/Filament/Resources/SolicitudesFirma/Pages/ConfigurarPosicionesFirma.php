<?php

namespace App\Filament\Resources\SolicitudesFirma\Pages;

use App\Exceptions\FirmaDigitalException;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use App\Models\SolicitudFirmaFirma;
use App\Models\SolicitudFirmaFirmante;
use App\Services\Firma\EnviarSolicitudFirma;
use App\Services\Firma\GuardarPosicionesFirma;
use App\Services\Firma\PosicionesFirmaAnteriores;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;

/**
 * Canvas: se arrastra el recuadro de cada firmante a su lugar en cada documento y
 * se envía a firmar. Ver Docs/firma-digital/05-pantallas.md#canvas-de-posiciones.
 *
 * @property SolicitudFirma $record
 */
class ConfigurarPosicionesFirma extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SolicitudFirmaResource::class;

    protected string $view = 'filament.resources.solicitudes-firma.pages.configurar-posiciones';

    /**
     * Sincronizada con el canvas (Alpine): [{documento_id, firmante_id, pagina, x, y}].
     *
     * @var list<array{documento_id: int, firmante_id: int, pagina: int, x: float, y: float}>
     */
    public array $posiciones = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        Gate::authorize('prepararEnvio', $this->record);

        $this->posiciones = $this->record->documentos()->with('firmas')->get()
            ->flatMap(fn (SolicitudFirmaDocumento $documento) => $documento->firmas->map(fn (SolicitudFirmaFirma $firma): array => [
                'documento_id' => $documento->id,
                'firmante_id' => $firma->firmante_id,
                'pagina' => $firma->pagina,
                'x' => $firma->x,
                'y' => $firma->y,
            ]))
            ->values()
            ->all();
    }

    public function getTitle(): string|Htmlable
    {
        return "Ubicar firmas · {$this->record->codigo}";
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Elige un firmante y haz clic en el lugar donde debe firmar. Arrastra el recuadro para moverlo.';
    }

    /**
     * Datos para el componente Alpine.
     *
     * @return array<string, mixed>
     */
    public function datosCanvas(): array
    {
        return [
            'documentos' => $this->record->documentos()->orderBy('id')->get()
                ->map(fn (SolicitudFirmaDocumento $documento): array => [
                    'id' => $documento->id,
                    'nombre' => $documento->nombre,
                    'paginas' => $documento->totalPaginas(),
                    'url' => route('firma.documentos.ver', $documento),
                ])->all(),
            'firmantes' => $this->record->firmantes()->with('usuario.certificadoFirmaActivo')->get()
                ->map(fn (SolicitudFirmaFirmante $firmante): array => [
                    'id' => $firmante->id,
                    'orden' => $firmante->orden,
                    'nombre' => $firmante->usuario?->certificadoFirmaActivo?->nombre_en_sello ?? $firmante->usuario?->name,
                    'cargo' => $firmante->usuario?->certificadoFirmaActivo?->cargo_en_sello,
                ])->all(),
            'recuadro' => [
                'ancho' => (float) config('firma.sello.ancho'),
                'alto' => (float) config('firma.sello.alto'),
            ],
            'sugeridas' => app(PosicionesFirmaAnteriores::class)->para($this->record),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar posiciones')
                ->icon(Heroicon::OutlinedCheck)
                ->color('gray')
                ->action(fn () => $this->guardar()),
            Action::make('enviar')
                ->label('Enviar a firmar')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->requiresConfirmation()
                ->modalHeading('Enviar a firmar')
                ->modalDescription(fn (): string => $this->record->orden_obligatorio
                    ? 'Se avisará al primer firmante. Los demás recibirán el aviso cuando les toque.'
                    : 'Se avisará a todos los firmantes por la campana y por correo. Después ya no se pueden cambiar los documentos ni las posiciones.')
                ->modalSubmitActionLabel('Enviar')
                ->action(function (Action $action): void {
                    if (! $this->guardar(notificar: false)) {
                        $action->halt();
                    }

                    try {
                        app(EnviarSolicitudFirma::class)($this->record);
                    } catch (FirmaDigitalException $exception) {
                        Notification::make()->danger()->title('No se pudo enviar')->body($exception->getMessage())->persistent()->send();

                        $action->halt();
                    }

                    Notification::make()->success()->title('Envío realizado')->body('Los firmantes ya fueron notificados.')->send();

                    $this->redirect(SolicitudFirmaResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    public function guardar(bool $notificar = true): bool
    {
        try {
            app(GuardarPosicionesFirma::class)($this->record, $this->posiciones);
        } catch (FirmaDigitalException $exception) {
            Notification::make()->danger()->title('No se pudieron guardar las posiciones')->body($exception->getMessage())->send();

            return false;
        }

        if ($notificar) {
            Notification::make()->success()->title('Posiciones guardadas')->send();
        }

        return true;
    }
}
