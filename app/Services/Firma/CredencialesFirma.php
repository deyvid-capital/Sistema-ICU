<?php

namespace App\Services\Firma;

use App\Models\CertificadoFirma;
use LogicException;

/**
 * .p12 y su contraseña ya abiertos para firmar en esta petición. Viven solo en
 * memoria: no se pueden serializar (ni encolar) y no aparecen en volcados.
 */
final class CredencialesFirma
{
    public function __construct(
        public readonly CertificadoFirma $certificado,
        private readonly string $p12,
        private readonly string $contrasenaP12,
    ) {}

    public function p12(): string
    {
        return $this->p12;
    }

    public function contrasenaP12(): string
    {
        return $this->contrasenaP12;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return ['certificado' => $this->certificado->getKey()];
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        throw new LogicException('Las credenciales de firma no se pueden serializar.');
    }
}
