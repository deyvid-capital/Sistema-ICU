<?php

namespace Tests\Feature\Firma\Concerns;

use App\Enums\EstadoCertificadoFirma;
use App\Models\CertificadoFirma;
use App\Models\User;
use App\Services\Firma\BovedaCertificados;
use App\Services\Firma\EnvolturaContrasena;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Simula el servicio firmador: un único .p12 con contraseña CONTRASENA_P12 cuyo
 * titular tiene el CI DOCUMENTO.
 */
trait FakeFirmador
{
    protected const CONTRASENA_P12 = 'clave-del-p12';

    protected const CONTRASENA_SISTEMA = '12345678';

    protected const DOCUMENTO = '8347375';

    protected const CONTENIDO_P12 = 'contenido-binario-del-p12';

    /**
     * @param  array<string, mixed>  $datosCertificado  Sobrescribe la respuesta de /certificados/inspeccionar.
     */
    protected function fakeFirmador(array $datosCertificado = [], bool $p12Valido = true): void
    {
        Http::fake(function (Request $request) use ($datosCertificado, $p12Valido) {
            return match (parse_url($request->url(), PHP_URL_PATH)) {
                '/certificados/inspeccionar' => $this->respuestaCertificado($request, $datosCertificado, $p12Valido),
                '/pdf/inspeccionar' => $this->respuestaPdf($request),
                '/pdf/preparar' => Http::response('%PDF-preparado'),
                '/pdf/firmar' => Http::response('%PDF-firmado'),
                default => Http::response(['codigo' => 'error_interno', 'mensaje' => 'Ruta no simulada'], 500),
            };
        });
    }

    /**
     * @param  array<string, mixed>  $sobrescribir
     * @return array<string, mixed>
     */
    protected function datosCertificado(array $sobrescribir = []): array
    {
        $datos = [
            'titular' => [
                'cn' => 'VLADIMIR ADALID CALDERON SARMIENTO',
                'documento' => self::DOCUMENTO,
                'tipo_documento' => 'CI',
                'numero_serie' => '148886022',
                'cargo' => 'OFICIAL DE INFRAESTRUCTURA',
                'organizacion' => 'CAPITAL + SAFI S.A.',
                'unidad' => 'TECNOLOGÍA DE LA INFORMACIÓN',
                'pais' => 'BO',
            ],
            'emisor' => ['cn' => 'Entidad Certificadora Autorizada Digicert', 'organizacion' => 'Digicert', 'pais' => 'BO'],
            'numero_serie' => '1a2b3c',
            'vigente_desde' => now()->subMonth()->toIso8601String(),
            'vigente_hasta' => now()->addYear()->toIso8601String(),
            'vigente' => true,
            'huella_sha256' => str_repeat('a', 64),
            'uso_clave' => ['digital_signature', 'non_repudiation'],
            'clave_corresponde' => true,
            'certificados_en_cadena' => 2,
        ];

        // Las listas (uso_clave) se reemplazan completas; los sub-arreglos se mezclan.
        foreach ($sobrescribir as $clave => $valor) {
            $datos[$clave] = is_array($valor) && ! array_is_list($valor)
                ? array_replace($datos[$clave] ?? [], $valor)
                : $valor;
        }

        return $datos;
    }

    /**
     * Firma subida por el admin y pendiente de activación, con el .p12 cifrado en disco.
     */
    protected function certificadoPendiente(User $titular, array $atributos = []): CertificadoFirma
    {
        return CertificadoFirma::factory()->pendienteActivacion()->create([
            'usuario_id' => $titular->getKey(),
            'archivo' => app(BovedaCertificados::class)->guardar($titular, self::CONTENIDO_P12),
            ...$atributos,
        ]);
    }

    /**
     * Firma ya activada con la contraseña del sistema actual del titular.
     */
    protected function certificadoActivo(User $titular, array $atributos = []): CertificadoFirma
    {
        $envoltura = app(EnvolturaContrasena::class);

        return CertificadoFirma::factory()->activo()->create([
            'usuario_id' => $titular->getKey(),
            'archivo' => app(BovedaCertificados::class)->guardar($titular, self::CONTENIDO_P12),
            'contrasena_p12_envuelta' => $envoltura->envolver(self::CONTRASENA_P12, self::CONTRASENA_SISTEMA),
            'contrasena_usuario_huella' => $envoltura->huellaContrasenaUsuario($titular->password),
            'estado' => EstadoCertificadoFirma::Activo,
            ...$atributos,
        ]);
    }

    protected function titular(array $atributos = []): User
    {
        return User::factory()->create(['carnet' => self::DOCUMENTO.' LP', ...$atributos]);
    }

    protected function campoMultipart(Request $request, string $nombre): ?string
    {
        foreach ($request->data() as $parte) {
            if (($parte['name'] ?? null) === $nombre) {
                return (string) $parte['contents'];
            }
        }

        return null;
    }

    /**
     * PDF de prueba: si el contenido incluye "CIFRADO" o "DANADO" el firmador lo rechaza.
     */
    protected function pdfDePrueba(string $marca = '', int $paginas = 1): string
    {
        return "%PDF-1.7 {$marca} paginas={$paginas}";
    }

    private function respuestaPdf(Request $request): mixed
    {
        $contenido = (string) $this->campoMultipart($request, 'pdf');

        if (str_contains($contenido, 'CIFRADO')) {
            return Http::response(['codigo' => 'pdf_cifrado', 'mensaje' => 'El PDF está protegido con contraseña.'], 422);
        }

        if (str_contains($contenido, 'DANADO') || ! str_starts_with($contenido, '%PDF')) {
            return Http::response(['codigo' => 'pdf_invalido', 'mensaje' => 'El PDF está dañado.'], 422);
        }

        preg_match('/paginas=(\d+)/', $contenido, $coincidencia);
        $total = (int) ($coincidencia[1] ?? 1);

        return Http::response([
            'paginas' => array_map(
                fn (int $numero): array => ['numero' => $numero, 'ancho' => 612.0, 'alto' => 792.0, 'rotacion' => 0],
                range(1, $total),
            ),
            'campos_firma' => [],
            'firmas' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $datosCertificado
     */
    private function respuestaCertificado(Request $request, array $datosCertificado, bool $p12Valido): mixed
    {
        if (! $p12Valido) {
            return Http::response(['codigo' => 'p12_invalido', 'mensaje' => 'El archivo no es un certificado .p12 válido.'], 422);
        }

        if ($this->campoMultipart($request, 'contrasena') !== self::CONTRASENA_P12) {
            return Http::response(['codigo' => 'contrasena_incorrecta', 'mensaje' => 'La contraseña del certificado .p12 no es correcta.'], 422);
        }

        return Http::response($this->datosCertificado($datosCertificado));
    }
}
