<?php

namespace App\Filament\Imports;

use App\Models\Cargo;
use App\Models\Movimiento;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

class MovimientoImporter extends Importer
{
    protected static ?string $model = Movimiento::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('usuario')
                ->label('Email')
                ->exampleHeader('email')
                ->relationship(resolveUsing: 'email')
                ->requiredMapping()
                ->rules(['required', 'email'])
                ->example('jperez@empresa.com'),
            ImportColumn::make('tipo')
                ->label('Tipo')
                ->requiredMapping()
                ->rules(['required', Rule::in(['Alta', 'Baja'])])
                ->example('Alta'),
            ImportColumn::make('fecha')
                ->label('Fecha')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->example('2026-01-15'),
            ImportColumn::make('cargo')
                ->label('Cargo')
                ->relationship(resolveUsing: function (string $state, array $data): ?Cargo {
                    $usuario = User::query()
                        ->where('email', $data['usuario'] ?? null)
                        ->first();

                    $query = Cargo::query()->where('cargo', $state);

                    if ($usuario?->id_area) {
                        $query->where('area_id', $usuario->id_area);
                    }

                    return $query->first();
                })
                ->requiredMapping()
                ->rules(['required'])
                ->example('Analista'),
        ];
    }

    public function resolveRecord(): Movimiento
    {
        return new Movimiento;
    }

    protected function beforeValidate(): void
    {
        $usuario = User::query()
            ->where('email', $this->data['usuario'] ?? null)
            ->first();

        $cargoQuery = Cargo::query()
            ->where('cargo', $this->data['cargo'] ?? null);

        if ($usuario?->id_area) {
            $cargoQuery->where('area_id', $usuario->id_area);
        }

        $cargo = $cargoQuery->first();

        $errores = Movimiento::erroresDeValidacion([
            'usuario_id' => $usuario?->id,
            'tipo' => $this->data['tipo'] ?? null,
            'fecha' => $this->data['fecha'] ?? null,
            'cargo_id' => $cargo?->id_cargo,
        ]);

        if ($errores !== []) {
            throw new RowImportFailedException(implode(' ', $errores));
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de movimientos finalizó: '.Number::format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }
}
