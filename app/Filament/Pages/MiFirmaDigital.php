<?php

namespace App\Filament\Pages;

use App\Enums\EstadoCertificadoFirma;
use App\Exceptions\FirmaDigitalException;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Services\Firma\FirmarDocumentoDePrueba;
use App\Services\Firma\VincularCertificadoFirma;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cada usuario ve su firma digital y puede probarla. La activa el admin; el usuario
 * solo la vincula (sola al iniciar sesión, o aquí si ya tenía la sesión abierta).
 * Ver Docs/firma-digital/05-pantallas.md#pantalla-1b-mi-firma-digital-cada-usuario
 */
class MiFirmaDigital extends Page
{
    protected string $view = 'filament.pages.mi-firma-digital';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?string $title = 'Mi firma digital';

    protected static ?string $slug = 'mi-firma-digital';

    protected static bool $shouldRegisterNavigation = false;

    public static function tieneFirmaAsignada(): bool
    {
        $usuario = auth()->user();

        return $usuario instanceof User && $usuario->certificadosFirma()->vigentes()->exists();
    }

    /**
     * Firma con la que el usuario firma hoy (o la que quedó en pausa).
     */
    public function certificadoEnUso(): ?CertificadoFirma
    {
        return $this->usuario()->certificadosFirma()
            ->whereIn('estado', [EstadoCertificadoFirma::Activo, EstadoCertificadoFirma::RequiereReactivacion])
            ->latest()
            ->first();
    }

    /**
     * .p12 subido con el flujo anterior y aún no activado por el admin.
     */
    public function certificadoPendiente(): ?CertificadoFirma
    {
        return $this->usuario()->certificadosFirma()
            ->where('estado', EstadoCertificadoFirma::PendienteActivacion)
            ->latest()
            ->first();
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->vincularAction(),
            $this->probarAction(),
        ];
    }

    /**
     * Normalmente la firma se vincula sola al iniciar sesión; esto cubre al usuario
     * que ya tenía la sesión abierta cuando el admin la activó o se restableció su contraseña.
     */
    protected function vincularAction(): Action
    {
        return Action::make('vincular')
            ->label(fn (): string => $this->certificadoEnUso()?->estado === EstadoCertificadoFirma::RequiereReactivacion
                ? 'Reactivar mi firma'
                : 'Vincular mi firma')
            ->icon(Heroicon::OutlinedKey)
            ->visible(fn (): bool => (bool) $this->certificadoEnUso()?->puedeVincularse())
            ->modalHeading('Vincular mi firma digital')
            ->modalDescription('Desde ahora firmarás con tu contraseña del sistema, la misma con la que entras al Sistema ICU.')
            ->modalSubmitActionLabel('Vincular')
            ->schema([
                TextInput::make('contrasena_sistema')
                    ->label('Tu contraseña del sistema ICU')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule('current_password')
                    ->validationMessages(['current_password' => 'La contraseña del sistema no es correcta.']),
            ])
            ->action(function (array $data, Action $action): void {
                $certificado = app(VincularCertificadoFirma::class)($this->usuario(), (string) $data['contrasena_sistema']);

                if ($certificado === null) {
                    Notification::make()->danger()->title('No se pudo vincular la firma')->body('Tu firma no está pendiente de vincular. Si el problema sigue, comunícate con el administrador.')->send();

                    $action->halt();
                }

                Notification::make()
                    ->success()
                    ->title('Firma digital lista')
                    ->body('Vigente hasta el '.$certificado->vigente_hasta?->format('d/m/Y').'. Firmas con tu contraseña del sistema.')
                    ->send();
            });
    }

    protected function probarAction(): Action
    {
        return Action::make('probar')
            ->label('Probar mi firma')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->visible(fn (): bool => $this->certificadoEnUso()?->estado === EstadoCertificadoFirma::Activo)
            ->modalHeading('Probar mi firma')
            ->modalDescription('Se firmará un documento de ejemplo con tu firma para que veas cómo queda. No se guarda en el sistema.')
            ->modalSubmitActionLabel('Firmar y descargar')
            ->schema([
                TextInput::make('contrasena_sistema')
                    ->label('Tu contraseña del sistema ICU')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->action(function (array $data, Action $action): ?StreamedResponse {
                try {
                    $pdf = app(FirmarDocumentoDePrueba::class)(
                        $this->certificadoEnUso(),
                        $this->usuario(),
                        (string) $data['contrasena_sistema'],
                    );
                } catch (FirmaDigitalException $exception) {
                    Notification::make()->danger()->title('No se pudo firmar')->body($exception->getMessage())->send();

                    $action->halt();

                    return null;
                }

                return response()->streamDownload(function () use ($pdf): void {
                    echo $pdf;
                }, 'prueba-firma-digital.pdf', [
                    'Content-Type' => 'application/pdf',
                ]);
            });
    }

    private function usuario(): User
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        return $usuario;
    }
}
