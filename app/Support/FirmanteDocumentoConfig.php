<?php

namespace App\Support;

use App\Enums\FirmantePosicion;
use App\Models\EntregaCelular;
use App\Models\EntregaEquipo;
use App\Models\NotaRecepcionEquipo;
use App\Models\NotaSalidaEquipo;
use App\Models\RecepcionCelular;
use App\Models\RecepcionEquipo;

class FirmanteDocumentoConfig
{
    /**
     * @return list<FirmantePosicion>
     */
    public static function allowedPosiciones(?string $documentoType): array
    {
        return match ($documentoType) {
            EntregaCelular::class, RecepcionCelular::class => [
                FirmantePosicion::Izquierda,
                FirmantePosicion::Centro,
                FirmantePosicion::Derecha,
            ],
            EntregaEquipo::class,
            RecepcionEquipo::class,
            NotaSalidaEquipo::class,
            NotaRecepcionEquipo::class => [
                FirmantePosicion::Izquierda,
                FirmantePosicion::Derecha,
            ],
            default => [
                FirmantePosicion::Izquierda,
                FirmantePosicion::Derecha,
            ],
        };
    }

    public static function maxFirmantes(?string $documentoType): int
    {
        return count(self::allowedPosiciones($documentoType));
    }

    public static function allows(FirmantePosicion $posicion, ?string $documentoType): bool
    {
        return in_array($posicion, self::allowedPosiciones($documentoType), true);
    }

    /**
     * @return array<string, string>
     */
    public static function optionsFor(?string $documentoType): array
    {
        $options = [];

        foreach (self::allowedPosiciones($documentoType) as $posicion) {
            $options[$posicion->value] = $posicion->getLabel();
        }

        return $options;
    }
}
