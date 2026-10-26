<?php

namespace App\Services\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Exceptions\FirmaDigitalException;
use App\Exceptions\FirmadorException;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Crea un envío a firmar en estado borrador: guarda una copia propia de cada PDF
 * (snapshot + hash) y la lista de firmantes. Ubicar los recuadros y enviarlo a
 * los firmantes es el paso siguiente.
 */
class CrearSolicitudFirma
{
    public const MAX_DOCUMENTOS = 20;

    public function __construct(private FirmadorClient $firmador) {}

    /**
     * @param  array{asunto: string, mensaje?: ?string, orden_obligatorio?: bool, fecha_limite?: mixed, recordatorio_cada_horas?: ?int}  $datos
     * @param  list<array{nombre: string, contenido: string, origen?: ?Model}>  $documentos
     * @param  list<string>  $firmantes  IDs de usuario, en el orden de firma.
     *
     * @throws FirmaDigitalException
     */
    public function __invoke(User $creador, array $datos, array $documentos, array $firmantes): SolicitudFirma
    {
        $this->validarDocumentos($documentos);
        $usuarios = $this->validarFirmantes($firmantes);
        $paginas = array_map(fn (array $documento): array => $this->paginas($documento), $documentos);

        $directorio = null;

        try {
            return DB::transaction(function () use ($creador, $datos, $documentos, $usuarios, $paginas, &$directorio): SolicitudFirma {
                $solicitud = SolicitudFirma::query()->create([
                    'asunto' => $datos['asunto'],
                    'mensaje' => $datos['mensaje'] ?? null,
                    'orden_obligatorio' => (bool) ($datos['orden_obligatorio'] ?? false),
                    'estado' => EstadoSolicitudFirma::Borrador,
                    'fecha_limite' => $datos['fecha_limite'] ?? null,
                    'recordatorio_cada_horas' => $datos['recordatorio_cada_horas'] ?? null,
                    'creado_por' => $creador->getKey(),
                ]);
                $solicitud->update(['codigo' => sprintf('SF-%d-%05d', now()->year, $solicitud->id)]);
                $directorio = $solicitud->directorio();

                foreach ($documentos as $indice => $documento) {
                    $this->guardarDocumento($solicitud, $documento, $paginas[$indice]);
                }

                foreach ($usuarios->values() as $indice => $usuario) {
                    $solicitud->firmantes()->create([
                        'usuario_id' => $usuario->getKey(),
                        'orden' => $indice + 1,
                    ]);
                }

                return $solicitud;
            });
        } catch (Throwable $exception) {
            if ($directorio !== null) {
                Storage::disk(SolicitudFirmaDocumento::DISCO)->deleteDirectory($directorio);
            }

            throw $exception;
        }
    }

    /**
     * Usuarios que hoy pueden ser firmantes: firma activa y vigente.
     *
     * @return Builder<User>
     */
    public static function firmantesDisponibles(): Builder
    {
        return User::query()->whereHas('certificadosFirma', fn (Builder $query) => $query
            ->where('estado', EstadoCertificadoFirma::Activo)
            ->where('vigente_hasta', '>', now()));
    }

    /**
     * @param  list<array{nombre: string, contenido: string, origen?: ?Model}>  $documentos
     *
     * @throws FirmaDigitalException
     */
    private function validarDocumentos(array $documentos): void
    {
        if ($documentos === []) {
            throw FirmaDigitalException::sinDocumentos();
        }

        if (count($documentos) > self::MAX_DOCUMENTOS) {
            throw FirmaDigitalException::demasiadosDocumentos(self::MAX_DOCUMENTOS);
        }
    }

    /**
     * @param  list<string>  $firmantes
     * @return Collection<int, User> En el orden recibido.
     *
     * @throws FirmaDigitalException
     */
    private function validarFirmantes(array $firmantes): Collection
    {
        if ($firmantes === []) {
            throw FirmaDigitalException::sinFirmantes();
        }

        if (count($firmantes) !== count(array_unique($firmantes))) {
            throw FirmaDigitalException::firmanteRepetido();
        }

        $disponibles = self::firmantesDisponibles()->whereKey($firmantes)->get()->keyBy(fn (User $usuario): string => (string) $usuario->getKey());
        $sinFirma = array_diff($firmantes, $disponibles->keys()->all());

        if ($sinFirma !== []) {
            $nombres = User::query()->whereKey($sinFirma)->pluck('name')->implode(', ');

            throw FirmaDigitalException::firmanteSinFirmaActiva($nombres !== '' ? $nombres : 'usuario desconocido');
        }

        return new Collection(array_map(fn (string $id): User => $disponibles[$id], $firmantes));
    }

    /**
     * @param  array{nombre: string, contenido: string}  $documento
     * @return list<array{numero: int, ancho: float, alto: float, rotacion: int}>
     *
     * @throws FirmaDigitalException
     */
    private function paginas(array $documento): array
    {
        try {
            return $this->firmador->inspeccionarPdf($documento['contenido'])['paginas'];
        } catch (FirmadorException $exception) {
            throw match ($exception->codigo) {
                FirmadorException::PDF_CIFRADO => FirmaDigitalException::pdfCifrado($documento['nombre']),
                FirmadorException::NO_DISPONIBLE => FirmaDigitalException::firmadorNoDisponible(),
                default => FirmaDigitalException::pdfInvalido($documento['nombre']),
            };
        }
    }

    /**
     * @param  array{nombre: string, contenido: string, origen?: ?Model}  $documento
     * @param  list<array<string, mixed>>  $paginas
     */
    private function guardarDocumento(SolicitudFirma $solicitud, array $documento, array $paginas): void
    {
        $origen = $documento['origen'] ?? null;

        $registro = $solicitud->documentos()->create([
            'nombre' => $documento['nombre'],
            'origen_type' => $origen?->getMorphClass(),
            'origen_id' => $origen?->getKey(),
            'archivo_original' => '',
            'archivo_actual' => '',
            'hash_actual' => hash('sha256', $documento['contenido']),
            'version' => 0,
            'paginas' => $paginas,
            'estado' => EstadoDocumentoFirma::Borrador,
        ]);

        $ruta = "{$solicitud->directorio()}/{$registro->id}/original.pdf";
        Storage::disk(SolicitudFirmaDocumento::DISCO)->put($ruta, $documento['contenido']);

        $registro->update(['archivo_original' => $ruta, 'archivo_actual' => $ruta]);
    }
}
