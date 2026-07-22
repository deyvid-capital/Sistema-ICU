<?php

namespace App\Services\Formularios;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class FormularioEquipoNumeroGenerator
{
    public const PATTERN = '/^\d{3,}-\d{4}$/';

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function next(string $modelClass, int $year): string
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new InvalidArgumentException("{$modelClass} must be an Eloquent model.");
        }

        $suffix = '-'.$year;

        $maxSequence = $modelClass::query()
            ->withTrashed()
            ->where('numero', 'like', '%'.$suffix)
            ->pluck('numero')
            ->reduce(function (int $max, mixed $numero) use ($suffix): int {
                if (! is_string($numero) || ! str_ends_with($numero, $suffix)) {
                    return $max;
                }

                $sequence = substr($numero, 0, -strlen($suffix));

                if (! ctype_digit($sequence)) {
                    return $max;
                }

                return max($max, (int) $sequence);
            }, 0);

        return sprintf('%03d-%d', $maxSequence + 1, $year);
    }

    public function matchesFormat(?string $numero): bool
    {
        return is_string($numero) && preg_match(self::PATTERN, $numero) === 1;
    }

    public function yearFromNumero(?string $numero): ?int
    {
        if (! $this->matchesFormat($numero)) {
            return null;
        }

        return (int) substr((string) $numero, -4);
    }

    /**
     * True when the current value looks like an auto-suggested correlative
     * for the previous year (or is empty), so it is safe to replace.
     */
    public function shouldResuggest(?string $currentNumero, int $previousYear, int $newYear): bool
    {
        if ($previousYear === $newYear) {
            return false;
        }

        if (blank($currentNumero)) {
            return true;
        }

        return $this->yearFromNumero($currentNumero) === $previousYear;
    }
}
