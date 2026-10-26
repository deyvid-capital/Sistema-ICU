<?php

namespace App\Services\Firma;

use App\Exceptions\FirmadorException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use SensitiveParameter;

/**
 * Cliente HTTP del servicio firmador (docker/firmador).
 *
 * Los PDF, certificados y contraseñas viajan solo en memoria por la red interna
 * de Docker. Las contraseñas se marcan como #[SensitiveParameter] para que no
 * aparezcan en trazas de error.
 */
class FirmadorClient
{
    /**
     * @return array{
     *     titular: array{cn: ?string, documento: ?string, tipo_documento: ?string, numero_serie: ?string, cargo: ?string, organizacion: ?string, unidad: ?string, pais: ?string},
     *     emisor: array{cn: ?string, organizacion: ?string, pais: ?string},
     *     numero_serie: string,
     *     vigente_desde: string,
     *     vigente_hasta: string,
     *     vigente: bool,
     *     huella_sha256: string,
     *     uso_clave: list<string>,
     *     clave_corresponde: bool,
     *     certificados_en_cadena: int
     * }
     *
     * @throws FirmadorException
     */
    public function inspeccionarCertificado(string $p12, #[SensitiveParameter] string $contrasena): array
    {
        return $this->enviar(
            fn (PendingRequest $request): Response => $request
                ->attach('p12', $p12, 'firma.p12')
                ->post('/certificados/inspeccionar', ['contrasena' => $contrasena]),
        )->json();
    }

    /**
     * @return array{
     *     paginas: list<array{numero: int, ancho: float, alto: float, rotacion: int}>,
     *     campos_firma: array<string, array{firmado: bool, rect: ?list<float>}>,
     *     firmas: int
     * }
     *
     * @throws FirmadorException
     */
    public function inspeccionarPdf(string $pdf): array
    {
        return $this->enviar(
            fn (PendingRequest $request): Response => $request
                ->attach('pdf', $pdf, 'documento.pdf')
                ->post('/pdf/inspeccionar'),
        )->json();
    }

    /**
     * Agrega campos de firma vacíos al PDF y devuelve el PDF resultante.
     *
     * @param  list<array{nombre: string, pagina: int, x: float, y: float, ancho: float, alto: float}>  $campos
     *
     * @throws FirmadorException
     */
    public function prepararPdf(string $pdf, array $campos): string
    {
        return $this->enviar(
            fn (PendingRequest $request): Response => $request
                ->attach('pdf', $pdf, 'documento.pdf')
                ->post('/pdf/preparar', ['campos' => json_encode($campos, JSON_THROW_ON_ERROR)]),
        )->body();
    }

    /**
     * Firma un campo del PDF (firma incremental) y devuelve el PDF firmado.
     *
     * @param  array{nombre: string, cargo: ?string, fecha: string}  $sello
     *
     * @throws FirmadorException
     */
    public function firmarPdf(
        string $pdf,
        string $p12,
        #[SensitiveParameter] string $contrasena,
        string $campo,
        array $sello,
        ?string $logo = null,
        string $motivo = 'Firma digital de documento',
    ): string {
        return $this->enviar(
            fn (PendingRequest $request): Response => $request
                ->attach('pdf', $pdf, 'documento.pdf')
                ->attach('p12', $p12, 'firma.p12')
                ->when($logo !== null, fn (PendingRequest $request): PendingRequest => $request->attach('logo', $logo, 'logo.png'))
                ->post('/pdf/firmar', [
                    'contrasena' => $contrasena,
                    'campo' => $campo,
                    'sello' => json_encode($sello, JSON_THROW_ON_ERROR),
                    'motivo' => $motivo,
                ]),
        )->body();
    }

    /**
     * @return list<array{
     *     campo: string,
     *     titular: array{cn: ?string, documento: ?string},
     *     emisor: array{cn: ?string},
     *     fecha_firma: ?string,
     *     intacta: bool,
     *     valida: bool,
     *     cobertura: ?string,
     *     modificaciones: ?string
     * }>
     *
     * @throws FirmadorException
     */
    public function verificarPdf(string $pdf): array
    {
        return $this->enviar(
            fn (PendingRequest $request): Response => $request
                ->attach('pdf', $pdf, 'documento.pdf')
                ->post('/pdf/verificar'),
        )->json('firmas');
    }

    public function disponible(): bool
    {
        try {
            return Http::baseUrl($this->url())->timeout(3)->get('/salud')->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    /**
     * @param  callable(PendingRequest): Response  $peticion
     *
     * @throws FirmadorException
     */
    private function enviar(callable $peticion): Response
    {
        try {
            $response = $peticion(
                Http::baseUrl($this->url())
                    ->timeout((int) config('firma.firmador.timeout'))
                    ->withHeaders(['X-Firmador-Token' => (string) config('firma.firmador.token')]),
            );
        } catch (ConnectionException) {
            throw FirmadorException::noDisponible();
        }

        if ($response->failed()) {
            throw FirmadorException::fromResponse($response);
        }

        return $response;
    }

    private function url(): string
    {
        return rtrim((string) config('firma.firmador.url'), '/');
    }
}
