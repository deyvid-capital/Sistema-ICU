<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Str;

/**
 * Ambientes de DAZA. El valor es el texto que se guarda en `accesos_daza.ambiente`.
 */
enum AmbienteDaza: string implements HasLabel
{
    case Produccion = 'Producción';
    case Contingencia = 'Contingencia';
    case Test = 'Test';

    public function getLabel(): string
    {
        return $this->value;
    }

    /**
     * Prefijo con el que se rotula el acceso en los formularios (DazProd, DazCont, DazTest).
     */
    public function abreviatura(): string
    {
        return match ($this) {
            self::Produccion => 'DazProd',
            self::Contingencia => 'DazCont',
            self::Test => 'DazTest',
        };
    }

    /**
     * Resuelve el ambiente desde el texto guardado, tolerando tildes, mayúsculas
     * y los nombres usados históricamente («Pruebas»).
     */
    public static function tryFromTexto(?string $texto): ?self
    {
        $normalizado = Str::lower(Str::ascii(trim((string) $texto)));

        return match (true) {
            $normalizado === '' => null,
            str_starts_with($normalizado, 'prod') => self::Produccion,
            str_starts_with($normalizado, 'cont') => self::Contingencia,
            str_starts_with($normalizado, 'test'), str_starts_with($normalizado, 'prueba') => self::Test,
            default => null,
        };
    }
}
