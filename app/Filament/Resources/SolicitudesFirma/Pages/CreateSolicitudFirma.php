<?php

namespace App\Filament\Resources\SolicitudesFirma\Pages;

use App\Exceptions\FirmaDigitalException;
use App\Filament\Resources\SolicitudesFirma\Schemas\SolicitudFirmaWizard;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\User;
use App\Services\Firma\CrearSolicitudFirma;
use App\Services\Firma\DocumentosFirmables;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateSolicitudFirma extends CreateRecord
{
    use HasWizard;

    protected static string $resource = SolicitudFirmaResource::class;

    protected static ?string $title = 'Preparar envío a firmar';

    protected function getSteps(): array
    {
        return SolicitudFirmaWizard::steps();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $subidos = $this->archivosSubidos($data['documentos_subidos'] ?? []);

        try {
            return app(CrearSolicitudFirma::class)(
                $this->usuario(),
                $data,
                [...$this->documentosDelSistema($data['documentos_sistema'] ?? []), ...$this->documentosSubidos($subidos)],
                $this->idsFirmantes($data),
            );
        } catch (FirmaDigitalException $exception) {
            Notification::make()
                ->danger()
                ->title('No se pudo preparar el envío')
                ->body($exception->getMessage())
                ->persistent()
                ->send();

            throw new Halt;
        } finally {
            foreach ($subidos as $archivo) {
                $archivo->delete();
            }
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Envío guardado como borrador';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    /**
     * @param  list<string>  $claves
     * @return list<array{nombre: string, contenido: string, origen: Model}>
     *
     * @throws FirmaDigitalException
     */
    private function documentosDelSistema(array $claves): array
    {
        $catalogo = app(DocumentosFirmables::class);
        $documentos = [];

        foreach ($claves as $clave) {
            $origen = $catalogo->resolver($clave);

            if ($origen === null) {
                continue;
            }

            $documentos[] = [
                'nombre' => $catalogo->nombre($origen),
                'contenido' => $catalogo->contenido($origen),
                'origen' => $origen,
            ];
        }

        return $documentos;
    }

    /**
     * @param  list<TemporaryUploadedFile>  $archivos
     * @return list<array{nombre: string, contenido: string}>
     */
    private function documentosSubidos(array $archivos): array
    {
        return array_map(fn (TemporaryUploadedFile $archivo): array => [
            'nombre' => $archivo->getClientOriginalName(),
            'contenido' => (string) $archivo->get(),
        ], $archivos);
    }

    /**
     * @return list<TemporaryUploadedFile>
     */
    private function archivosSubidos(mixed $archivos): array
    {
        return array_values(array_filter(
            (array) $archivos,
            fn (mixed $archivo): bool => $archivo instanceof TemporaryUploadedFile,
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function idsFirmantes(array $data): array
    {
        $ids = ($data['orden_obligatorio'] ?? false)
            ? ($data['firmantes_en_orden'] ?? [])
            : ($data['firmantes'] ?? []);

        return array_values(array_map('strval', array_filter($ids)));
    }

    private function usuario(): User
    {
        /** @var User $usuario */
        $usuario = auth()->user();

        return $usuario;
    }
}
