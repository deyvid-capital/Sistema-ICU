<?php

namespace App\Services\Firma;

use App\Exceptions\FirmaDigitalException;
use App\Exceptions\FirmadorException;
use App\Models\CertificadoFirma;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

/**
 * "Probar mi firma": firma un PDF de ejemplo con la contraseña del sistema del
 * titular, recorriendo el mismo camino que una firma real (envoltura → .p12 →
 * firmador). No guarda nada: devuelve el PDF firmado para descargarlo.
 */
class FirmarDocumentoDePrueba
{
    private const DOCUMENTO = 'firma/documento-prueba.pdf';

    private const CAMPO = 'firma_prueba';

    public function __construct(
        private FirmadorClient $firmador,
        private BovedaCertificados $boveda,
        private EnvolturaContrasena $envoltura,
    ) {}

    /**
     * @throws FirmaDigitalException
     */
    public function __invoke(CertificadoFirma $certificado, User $usuario, #[SensitiveParameter] string $contrasenaSistema): string
    {
        if ($certificado->usuario_id !== $usuario->getKey()) {
            throw FirmaDigitalException::noPerteneceAlUsuario();
        }

        if (! $certificado->estaVigente()) {
            throw FirmaDigitalException::certificadoVencido($certificado->vigente_hasta?->format('d/m/Y'));
        }

        if (! Hash::check($contrasenaSistema, $usuario->password)) {
            throw FirmaDigitalException::contrasenaSistemaIncorrecta();
        }

        if (! $certificado->puedeFirmar()) {
            throw FirmaDigitalException::envolturaInvalida();
        }

        $contrasenaP12 = $this->envoltura->desenvolver($certificado->contrasena_p12_envuelta, $contrasenaSistema);

        try {
            $preparado = $this->firmador->prepararPdf((string) file_get_contents(resource_path(self::DOCUMENTO)), [[
                'nombre' => self::CAMPO,
                'pagina' => 1,
                'x' => 372,
                'y' => 70,
                'ancho' => (float) config('firma.sello.ancho'),
                'alto' => (float) config('firma.sello.alto'),
            ]]);

            return $this->firmador->firmarPdf(
                $preparado,
                $this->boveda->leer($certificado->archivo),
                $contrasenaP12,
                self::CAMPO,
                $certificado->datosSello(),
                $this->logo(),
                'Prueba de firma digital',
            );
        } catch (FirmadorException $exception) {
            throw match ($exception->codigo) {
                FirmadorException::CERTIFICADO_VENCIDO => FirmaDigitalException::certificadoVencido(),
                FirmadorException::CONTRASENA_INCORRECTA => FirmaDigitalException::envolturaInvalida(),
                default => FirmaDigitalException::firmadorNoDisponible(),
            };
        }
    }

    private function logo(): ?string
    {
        $ruta = (string) config('firma.sello.logo');

        return is_file($ruta) ? (string) file_get_contents($ruta) : null;
    }
}
