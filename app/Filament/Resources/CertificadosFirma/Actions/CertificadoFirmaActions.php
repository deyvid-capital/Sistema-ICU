<?php

namespace App\Filament\Resources\CertificadosFirma\Actions;

use App\Enums\EstadoCertificadoFirma;
use App\Exceptions\FirmaDigitalException;
use App\Filament\Resources\CertificadosFirma\CertificadoFirmaResource;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Notifications\Firma\FirmaActivacionPendienteNotification;
use App\Services\Firma\AsignarCertificadoFirma;
use App\Services\Firma\RevocarCertificadoFirma;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CertificadoFirmaActions
{
    /**
     * Tamaño máximo del .p12 en KB (un .p12 típico pesa ~15 KB).
     */
    private const MAX_P12_KB = 100;

    public static function asignar(): Action
    {
        return Action::make('asignar')
            ->label('Asignar firma')
            ->icon(Heroicon::OutlinedPlus)
            ->authorize(fn (): bool => CertificadoFirmaResource::canCreate())
            ->modalHeading('Asignar firma digital')
            ->modalDescription('Sube el .p12 del usuario. Después, el titular debe activarlo con la contraseña del .p12 y su contraseña del sistema.')
            ->modalSubmitActionLabel('Asignar')
            ->schema([
                Select::make('usuario_id')
                    ->label('Usuario')
                    ->helperText('Solo aparecen usuarios sin una firma asignada.')
                    ->options(fn (): array => User::query()
                        ->whereDoesntHave('certificadosFirma', fn ($query) => $query->vigentes())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $usuario = $state !== null ? User::query()->with('cargo')->find($state) : null;

                        $set('nombre_en_sello', $usuario !== null ? self::nombreParaSello($usuario) : null);
                        $set('cargo_en_sello', $usuario?->cargo?->cargo);
                    }),
                self::campoArchivo(),
                ...self::camposSello(),
            ])
            ->action(function (array $data, Action $action): void {
                $titular = User::query()->findOrFail($data['usuario_id']);

                self::ejecutar($action, $data['p12'], fn (string $contenido): CertificadoFirma => app(AsignarCertificadoFirma::class)(
                    $titular,
                    $contenido,
                    $data['nombre_en_sello'],
                    $data['cargo_en_sello'] ?? null,
                    self::administrador(),
                ));

                Notification::make()
                    ->success()
                    ->title('Firma asignada')
                    ->body("Se avisó a {$titular->name} para que active su firma.")
                    ->send();
            });
    }

    public static function renovar(): Action
    {
        return Action::make('renovar')
            ->label(fn (CertificadoFirma $record): string => $record->estado === EstadoCertificadoFirma::PendienteActivacion
                ? 'Reemplazar archivo .p12'
                : 'Renovar (nuevo .p12)')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->visible(fn (CertificadoFirma $record): bool => in_array($record->estado, EstadoCertificadoFirma::vigentes(), true)
                && CertificadoFirmaResource::canCreate())
            ->modalHeading('Subir nuevo .p12')
            ->modalDescription('Si la firma está activa, sigue funcionando hasta que el titular active la nueva.')
            ->modalSubmitActionLabel('Subir')
            ->schema([self::campoArchivo()])
            ->action(function (CertificadoFirma $record, array $data, Action $action): void {
                $titular = $record->usuario;

                self::ejecutar($action, $data['p12'], fn (string $contenido): CertificadoFirma => app(AsignarCertificadoFirma::class)(
                    $titular,
                    $contenido,
                    $record->nombre_en_sello,
                    $record->cargo_en_sello,
                    self::administrador(),
                ));

                Notification::make()
                    ->success()
                    ->title('Nuevo .p12 cargado')
                    ->body("Se avisó a {$titular->name} para que lo active.")
                    ->send();
            });
    }

    public static function editarSello(): Action
    {
        return Action::make('editarSello')
            ->label('Editar sello')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->visible(fn (CertificadoFirma $record): bool => in_array($record->estado, EstadoCertificadoFirma::vigentes(), true)
                && CertificadoFirmaResource::canEdit($record))
            ->modalHeading('Datos del sello visible')
            ->modalDescription('Se usan en el recuadro de firma. Corrige aquí tildes o mayúsculas que el certificado no trae.')
            ->fillForm(fn (CertificadoFirma $record): array => [
                'nombre_en_sello' => $record->nombre_en_sello,
                'cargo_en_sello' => $record->cargo_en_sello,
            ])
            ->schema(self::camposSello())
            ->action(function (CertificadoFirma $record, array $data): void {
                $record->update([
                    'nombre_en_sello' => $data['nombre_en_sello'],
                    'cargo_en_sello' => $data['cargo_en_sello'] ?? null,
                ]);

                Notification::make()->success()->title('Sello actualizado')->send();
            });
    }

    public static function reenviarAviso(): Action
    {
        return Action::make('reenviarAviso')
            ->label('Reenviar aviso de activación')
            ->icon(Heroicon::OutlinedEnvelope)
            ->visible(fn (CertificadoFirma $record): bool => $record->estado === EstadoCertificadoFirma::PendienteActivacion
                && CertificadoFirmaResource::canEdit($record))
            ->requiresConfirmation()
            ->modalHeading('Reenviar aviso de activación')
            ->action(function (CertificadoFirma $record): void {
                $record->usuario->notify(new FirmaActivacionPendienteNotification($record->reemplaza_a_id !== null));

                Notification::make()->success()->title('Aviso reenviado')->send();
            });
    }

    public static function revocar(): Action
    {
        return Action::make('revocar')
            ->label('Eliminar asignación')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->visible(fn (CertificadoFirma $record): bool => in_array($record->estado, EstadoCertificadoFirma::vigentes(), true)
                && CertificadoFirmaResource::canDelete($record))
            ->requiresConfirmation()
            ->modalHeading('Eliminar firma digital')
            ->modalDescription('El archivo .p12 se borra del servidor y el usuario ya no podrá firmar con él. El registro queda como "revocado" para la auditoría.')
            ->modalSubmitActionLabel('Eliminar')
            ->action(function (CertificadoFirma $record): void {
                app(RevocarCertificadoFirma::class)($record);

                Notification::make()->success()->title('Firma digital eliminada')->send();
            });
    }

    private static function campoArchivo(): FileUpload
    {
        return FileUpload::make('p12')
            ->label('Archivo .p12 / .pfx')
            ->helperText('El archivo se guarda cifrado. No se pide la contraseña: la escribe el titular al activar.')
            ->storeFiles(false)
            ->maxSize(self::MAX_P12_KB)
            ->rules(['extensions:p12,pfx'])
            ->required();
    }

    /**
     * @return array<int, TextInput>
     */
    private static function camposSello(): array
    {
        return [
            TextInput::make('nombre_en_sello')
                ->label('Nombre en el sello')
                ->helperText('Ej.: Vladimir Adalid Calderón Sarmiento')
                ->required()
                ->maxLength(255),
            TextInput::make('cargo_en_sello')
                ->label('Cargo en el sello')
                ->maxLength(255),
        ];
    }

    /**
     * Lee el .p12 subido y borra el temporal de Livewire pase lo que pase: no debe
     * quedar una copia sin cifrar en storage/app/private/livewire-tmp.
     *
     * @param  callable(string): CertificadoFirma  $operacion
     */
    private static function ejecutar(Action $action, mixed $archivo, callable $operacion): void
    {
        $archivo = is_array($archivo) ? reset($archivo) : $archivo;

        if (! $archivo instanceof TemporaryUploadedFile) {
            Notification::make()->danger()->title('Sube el archivo .p12')->send();
            $action->halt();
        }

        try {
            $operacion((string) $archivo->get());
        } catch (FirmaDigitalException $exception) {
            Notification::make()
                ->danger()
                ->title('No se pudo cargar la firma')
                ->body($exception->getMessage())
                ->send();

            $action->halt();
        } finally {
            $archivo->delete();
        }
    }

    private static function nombreParaSello(User $usuario): string
    {
        $nombre = trim("{$usuario->nombres} {$usuario->apellidos}");

        return $nombre !== '' ? $nombre : $usuario->name;
    }

    private static function administrador(): ?User
    {
        $usuario = auth()->user();

        return $usuario instanceof User ? $usuario : null;
    }
}
