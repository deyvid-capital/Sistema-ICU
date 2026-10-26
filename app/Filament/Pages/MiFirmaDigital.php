<?php

namespace App\Filament\Pages;

use App\Enums\EstadoCertificadoFirma;
use App\Exceptions\FirmaDigitalException;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Services\Firma\ActivarCertificadoFirma;
use App\Services\Firma\FirmarDocumentoDePrueba;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Cada usuario ve su firma digital, la activa (o reactiva / activa la renovación)
 * y puede probarla. Ver Docs/firma-digital/05-pantallas.md#pantalla-1b-mi-firma-digital-cada-usuario
 */
class MiFirmaDigital extends Page
{
    protected string $view = 'filament.pages.mi-firma-digital';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?string $title = 'Mi firma digital';

    protected static ?string $slug = 'mi-firma-digital';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Resumen del certificado validado en el paso 1 del asistente.
     */
    public ?string $resumenCertificado = null;

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
     * .p12 subido por el admin y aún no activado (asignación o renovación).
     */
    public function certificadoPendiente(): ?CertificadoFirma
    {
        return $this->usuario()->certificadosFirma()
            ->where('estado', EstadoCertificadoFirma::PendienteActivacion)
            ->latest()
            ->first();
    }

    /**
     * La renovación pendiente tiene prioridad sobre reactivar el certificado anterior.
     */
    public function certificadoPorActivar(): ?CertificadoFirma
    {
        $enUso = $this->certificadoEnUso();

        return $this->certificadoPendiente()
            ?? ($enUso?->estado === EstadoCertificadoFirma::RequiereReactivacion ? $enUso : null);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->activarAction(),
            $this->probarAction(),
        ];
    }

    protected function activarAction(): Action
    {
        return Action::make('activar')
            ->label(fn (): string => match (true) {
                $this->certificadoPorActivar()?->estado === EstadoCertificadoFirma::RequiereReactivacion => 'Reactivar mi firma',
                $this->certificadoPorActivar()?->reemplaza_a_id !== null => 'Activar renovación',
                default => 'Activar mi firma',
            })
            ->icon(Heroicon::OutlinedKey)
            ->visible(fn (): bool => $this->certificadoPorActivar() !== null)
            ->modalHeading('Activar mi firma digital')
            ->modalSubmitActionLabel('Activar')
            ->steps([
                Step::make('p12')
                    ->label('Contraseña del .p12')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->schema([
                        Text::make('🔐 Es la contraseña que definiste al descargar tu firma de Digicert (la misma que usas en Viafirma). NO es tu contraseña del sistema ICU.'),
                        TextInput::make('contrasena_p12')
                            ->label('Contraseña de tu archivo de firma (.p12)')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(fn (): Closure => $this->validarContrasenaP12(...)),
                    ]),
                Step::make('sistema')
                    ->label('Contraseña del sistema')
                    ->icon(Heroicon::OutlinedFingerPrint)
                    ->schema([
                        Text::make(fn (): string => '✅ Archivo validado: '.($this->resumenCertificado ?? '')),
                        Text::make('✍️ Desde ahora firmarás con esta contraseña, la misma con la que entras al sistema.'),
                        TextInput::make('contrasena_sistema')
                            ->label('Tu contraseña del sistema ICU')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule('current_password')
                            ->validationMessages(['current_password' => 'La contraseña del sistema no es correcta.']),
                    ]),
            ])
            ->action(function (array $data, Action $action): void {
                try {
                    $certificado = app(ActivarCertificadoFirma::class)(
                        $this->certificadoPorActivar() ?? throw FirmaDigitalException::noActivable(),
                        $this->usuario(),
                        (string) $data['contrasena_p12'],
                        (string) $data['contrasena_sistema'],
                    );
                } catch (FirmaDigitalException $exception) {
                    Notification::make()->danger()->title('No se pudo activar la firma')->body($exception->getMessage())->send();

                    $action->halt();
                }

                $this->resumenCertificado = null;

                Notification::make()
                    ->success()
                    ->title('Firma digital activada')
                    ->body('Vigente hasta el '.$certificado->vigente_hasta?->format('d/m/Y').'. Desde ahora firmas con tu contraseña del sistema.')
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

    /**
     * Regla del paso 1: abre el .p12 en el firmador y muestra el error bajo el campo.
     */
    private function validarContrasenaP12(string $attribute, mixed $value, Closure $fail): void
    {
        $certificado = $this->certificadoPorActivar();

        if ($certificado === null) {
            $fail(FirmaDigitalException::noActivable()->getMessage());

            return;
        }

        try {
            $datos = app(ActivarCertificadoFirma::class)->validarContrasenaP12($certificado, $this->usuario(), (string) $value);
        } catch (FirmaDigitalException $exception) {
            $fail($exception->getMessage());

            return;
        }

        $this->resumenCertificado = sprintf(
            '%s · %s · vigente hasta %s',
            $datos['titular']['cn'] ?? 'Titular',
            $datos['emisor']['cn'] ?? 'Emisor',
            Carbon::parse($datos['vigente_hasta'])->format('d/m/Y'),
        );
    }

    private function usuario(): User
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        return $usuario;
    }
}
