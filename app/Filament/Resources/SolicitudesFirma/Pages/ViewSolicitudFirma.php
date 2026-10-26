<?php

namespace App\Filament\Resources\SolicitudesFirma\Pages;

use App\Enums\EstadoFirma;
use App\Exceptions\FirmaDigitalException;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaFirma;
use App\Models\SolicitudFirmaFirmante;
use App\Models\User;
use App\Services\Firma\AbrirCredencialesFirma;
use App\Services\Firma\AjustarFirmantesEnCurso;
use App\Services\Firma\CancelarSolicitudFirma;
use App\Services\Firma\CrearSolicitudFirma;
use App\Services\Firma\CredencialesFirma;
use App\Services\Firma\EliminarBorradorSolicitudFirma;
use App\Services\Firma\FirmarDocumento;
use App\Services\Firma\FirmarLote;
use App\Services\Firma\RechazarFirma;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Pantalla 3: ver el envío y firmar. Ver Docs/firma-digital/05-pantallas.md#pantalla-3-firmar.
 *
 * @property SolicitudFirma $record
 */
class ViewSolicitudFirma extends ViewRecord
{
    protected static string $resource = SolicitudFirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->firmarAction(),
            $this->rechazarAction(),
            Action::make('ubicarFirmas')
                ->label('Ubicar firmas y enviar')
                ->icon(Heroicon::OutlinedCursorArrowRays)
                ->visible(fn (): bool => Gate::allows('prepararEnvio', $this->record))
                ->url(fn (): string => SolicitudFirmaResource::getUrl('posiciones', ['record' => $this->record])),
            Action::make('descargarTodo')
                ->label('Descargar todo (ZIP)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => $this->record->documentos()->count() > 1)
                ->url(fn (): string => route('firma.documentos.zip', $this->record)),
            ActionGroup::make([
                $this->reemplazarFirmanteAction(),
                $this->quitarOrdenAction(),
                $this->cancelarAction(),
                DeleteAction::make()
                    ->label('Eliminar borrador')
                    ->icon(Heroicon::OutlinedTrash)
                    ->modalHeading('Eliminar borrador')
                    ->modalDescription('Se eliminan el envío y sus copias de los documentos. Los documentos originales del sistema no se tocan.')
                    ->using(function (SolicitudFirma $record): bool {
                        app(EliminarBorradorSolicitudFirma::class)($record);

                        return true;
                    })
                    ->successNotificationTitle('Borrador eliminado'),
            ])
                ->label('Más acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('gray')
                ->button(),
        ];
    }

    protected function firmarAction(): Action
    {
        return Action::make('firmar')
            ->label(fn (): string => $this->firmasQueMeTocan()->count() > 1
                ? 'Firmar ('.$this->firmasQueMeTocan()->count().')'
                : 'Firmar')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->visible(fn (): bool => $this->misFirmasPendientes()->isNotEmpty())
            ->disabled(fn (): bool => $this->firmasQueMeTocan()->isEmpty())
            ->tooltip(fn (): ?string => $this->firmasQueMeTocan()->isEmpty() ? $this->motivoEspera() : null)
            ->modalHeading('Firmar documentos')
            ->modalDescription('Se firmará con tu firma digital. Escribe tu contraseña del sistema ICU (la misma con la que entras).')
            ->modalSubmitActionLabel('Firmar')
            ->schema([
                CheckboxList::make('documentos')
                    ->label('Documentos a firmar')
                    ->options(fn (): array => $this->opcionesFirmasQueMeTocan())
                    ->default(fn (): array => array_keys($this->opcionesFirmasQueMeTocan()))
                    ->visible(fn (): bool => count($this->opcionesFirmasQueMeTocan()) > 1)
                    ->required()
                    ->bulkToggleable(),
                TextInput::make('contrasena')
                    ->label('Tu contraseña del sistema ICU')
                    ->password()
                    ->revealable()
                    ->required(),
                TextInput::make('codigo')
                    ->label('Código de verificación en dos pasos')
                    ->numeric()
                    ->length(6)
                    ->required()
                    ->visible(fn (): bool => AbrirCredencialesFirma::exigeDosPasos()),
                Checkbox::make('declaro')
                    ->label('Declaro haber revisado el/los documento(s) que voy a firmar.')
                    ->accepted()
                    ->validationMessages(['accepted' => 'Debes confirmar que revisaste los documentos.']),
            ])
            ->action(function (array $data, Action $action): void {
                $usuario = $this->usuario();

                try {
                    $credenciales = app(AbrirCredencialesFirma::class)($usuario, (string) $data['contrasena'], $data['codigo'] ?? null);
                } catch (FirmaDigitalException $exception) {
                    Notification::make()->danger()->title('No se pudo firmar')->body($exception->getMessage())->send();

                    $action->halt();
                }

                $documentos = array_map('intval', $data['documentos'] ?? array_keys($this->opcionesFirmasQueMeTocan()));

                if (count($documentos) === 1) {
                    $this->firmarUno($documentos[0], $usuario, $credenciales);
                } else {
                    $resultado = app(FirmarLote::class)($this->record, $usuario, $credenciales, $documentos);

                    Notification::make()
                        ->{$resultado->fallidos === [] ? 'success' : 'warning'}()
                        ->title($resultado->fallidos === [] ? 'Documentos firmados' : 'Firma parcial')
                        ->body($resultado->resumen())
                        ->persistent($resultado->fallidos !== [])
                        ->send();
                }

                $this->refrescar();
            });
    }

    protected function rechazarAction(): Action
    {
        return Action::make('rechazar')
            ->label('Rechazar')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (): bool => $this->misFirmasPendientes()->isNotEmpty())
            ->modalHeading('Rechazar documento')
            ->modalDescription('El documento quedará rechazado y se avisará a quien lo envió. Los demás documentos del envío siguen su curso.')
            ->modalSubmitActionLabel('Rechazar')
            ->schema([
                Select::make('documento')
                    ->label('Documento')
                    ->options(fn (): array => $this->misFirmasPendientes()
                        ->mapWithKeys(fn (SolicitudFirmaFirma $firma): array => [$firma->documento_id => $firma->documento->nombre])
                        ->all())
                    ->default(fn (): ?int => $this->misFirmasPendientes()->count() === 1 ? $this->misFirmasPendientes()->first()->documento_id : null)
                    ->required(),
                Textarea::make('motivo')
                    ->label('Motivo')
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (array $data, Action $action): void {
                $firma = $this->misFirmasPendientes()->firstWhere('documento_id', (int) $data['documento']);

                try {
                    app(RechazarFirma::class)($firma ?? throw FirmaDigitalException::firmaNoPendiente(), $this->usuario(), (string) $data['motivo']);
                } catch (FirmaDigitalException $exception) {
                    Notification::make()->danger()->title('No se pudo rechazar')->body($exception->getMessage())->send();

                    $action->halt();
                }

                Notification::make()->success()->title('Documento rechazado')->send();
                $this->refrescar();
            });
    }

    protected function reemplazarFirmanteAction(): Action
    {
        return Action::make('reemplazarFirmante')
            ->label('Reemplazar firmante')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->visible(fn (): bool => Gate::allows('ajustarFirmantes', $this->record))
            ->modalDescription('Para un firmante que todavía no firmó nada (de viaje, de baja...). El nuevo firmante ocupa su lugar y sus recuadros.')
            ->schema([
                Select::make('firmante')
                    ->label('Reemplazar a')
                    ->options(fn (): array => $this->record->firmantes()->with('usuario')->get()
                        ->filter(fn (SolicitudFirmaFirmante $firmante): bool => ! $firmante->firmas()->where('estado', '!=', EstadoFirma::Pendiente)->exists())
                        ->mapWithKeys(fn (SolicitudFirmaFirmante $firmante): array => [$firmante->id => $firmante->usuario?->name])
                        ->all())
                    ->required(),
                Select::make('nuevo')
                    ->label('Por')
                    ->options(fn (): array => CrearSolicitudFirma::firmantesDisponibles()
                        ->whereNotIn('id', $this->record->firmantes()->pluck('usuario_id'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, Action $action): void {
                try {
                    app(AjustarFirmantesEnCurso::class)->reemplazar(
                        $this->record->firmantes()->findOrFail($data['firmante']),
                        User::query()->findOrFail($data['nuevo']),
                    );
                } catch (FirmaDigitalException $exception) {
                    Notification::make()->danger()->title('No se pudo reemplazar')->body($exception->getMessage())->send();

                    $action->halt();
                }

                Notification::make()->success()->title('Firmante reemplazado')->send();
                $this->refrescar();
            });
    }

    protected function quitarOrdenAction(): Action
    {
        return Action::make('quitarOrden')
            ->label('Quitar orden obligatorio')
            ->icon(Heroicon::OutlinedArrowsUpDown)
            ->visible(fn (): bool => $this->record->orden_obligatorio && Gate::allows('ajustarFirmantes', $this->record))
            ->requiresConfirmation()
            ->modalDescription('Todos los firmantes pendientes podrán firmar en cualquier orden y se les avisará.')
            ->action(function (): void {
                app(AjustarFirmantesEnCurso::class)->quitarOrden($this->record);

                Notification::make()->success()->title('Ahora se firma en cualquier orden')->send();
                $this->refrescar();
            });
    }

    protected function cancelarAction(): Action
    {
        return Action::make('cancelar')
            ->label('Cancelar envío')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (): bool => Gate::allows('cancelar', $this->record))
            ->modalDescription('Los documentos pendientes ya no se podrán firmar. Las firmas ya hechas quedan en el historial.')
            ->schema([
                Textarea::make('motivo')
                    ->label('Motivo')
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (array $data, Action $action): void {
                try {
                    app(CancelarSolicitudFirma::class)($this->record, $this->usuario(), (string) $data['motivo']);
                } catch (FirmaDigitalException $exception) {
                    Notification::make()->danger()->title('No se pudo cancelar')->body($exception->getMessage())->send();

                    $action->halt();
                }

                Notification::make()->success()->title('Envío cancelado')->send();
                $this->refrescar();
            });
    }

    private function firmarUno(int $documentoId, User $usuario, CredencialesFirma $credenciales): void
    {
        $firma = $this->firmasQueMeTocan()->firstWhere('documento_id', $documentoId);

        try {
            app(FirmarDocumento::class)($firma ?? throw FirmaDigitalException::firmaNoPendiente(), $usuario, $credenciales);
        } catch (FirmaDigitalException $exception) {
            Notification::make()->danger()->title('No se pudo firmar')->body($exception->getMessage())->persistent()->send();

            return;
        }

        Notification::make()->success()->title('Documento firmado')->body($firma->documento->nombre)->send();
    }

    /**
     * @return Collection<int, SolicitudFirmaFirma>
     */
    private function misFirmasPendientes(): Collection
    {
        return $this->record->estaEnCurso()
            ? $this->record->firmasPendientesDe($this->usuario())->orderBy('documento_id')->get()
            : collect();
    }

    /**
     * @return Collection<int, SolicitudFirmaFirma>
     */
    private function firmasQueMeTocan(): Collection
    {
        return $this->misFirmasPendientes()->filter(fn (SolicitudFirmaFirma $firma): bool => $firma->leToca())->values();
    }

    /**
     * @return array<int, string>
     */
    private function opcionesFirmasQueMeTocan(): array
    {
        return $this->firmasQueMeTocan()
            ->mapWithKeys(fn (SolicitudFirmaFirma $firma): array => [$firma->documento_id => $firma->documento->nombre])
            ->all();
    }

    private function motivoEspera(): string
    {
        $anterior = $this->misFirmasPendientes()->first()?->anteriorPendiente();

        return $anterior !== null
            ? "Esperando la firma de {$anterior->firmante->usuario?->name}"
            : 'No tienes documentos por firmar ahora.';
    }

    private function refrescar(): void
    {
        $this->record->refresh();
        $this->redirect(SolicitudFirmaResource::getUrl('view', ['record' => $this->record]));
    }

    private function usuario(): User
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        return $usuario;
    }
}
