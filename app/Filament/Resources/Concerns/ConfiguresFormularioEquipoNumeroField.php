<?php

namespace App\Filament\Resources\Concerns;

use App\Services\Formularios\FormularioEquipoNumeroGenerator;
use Carbon\CarbonInterface;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rules\Unique;

trait ConfiguresFormularioEquipoNumeroField
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    protected static function formularioEquipoNumeroField(string $modelClass, string $table): TextInput
    {
        $generator = app(FormularioEquipoNumeroGenerator::class);

        return TextInput::make('numero')
            ->label('Número')
            ->required()
            ->maxLength(20)
            ->helperText('Formato: 001-2026')
            ->default(fn (): string => $generator->next($modelClass, (int) now()->format('Y')))
            ->rules(['regex:'.FormularioEquipoNumeroGenerator::PATTERN])
            ->validationMessages([
                'regex' => 'El número debe tener el formato 001-2026.',
            ])
            ->unique(
                table: $table,
                column: 'numero',
                ignoreRecord: true,
                modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
            );
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected static function syncFormularioEquipoNumeroOnFechaChange(string $modelClass): \Closure
    {
        return function (Set $set, Get $get, mixed $state, mixed $old) use ($modelClass): void {
            $generator = app(FormularioEquipoNumeroGenerator::class);

            $previousYear = self::yearFromFechaState($old) ?? (int) now()->format('Y');
            $newYear = self::yearFromFechaState($state) ?? (int) now()->format('Y');

            $currentNumero = $get('numero');

            if (! $generator->shouldResuggest(
                is_string($currentNumero) ? $currentNumero : null,
                $previousYear,
                $newYear,
            )) {
                return;
            }

            $set('numero', $generator->next($modelClass, $newYear));
        };
    }

    protected static function yearFromFechaState(mixed $state): ?int
    {
        if ($state instanceof CarbonInterface) {
            return (int) $state->format('Y');
        }

        if (blank($state) || ! is_string($state)) {
            return null;
        }

        try {
            return (int) Carbon::parse($state)->format('Y');
        } catch (\Throwable) {
            return null;
        }
    }
}
