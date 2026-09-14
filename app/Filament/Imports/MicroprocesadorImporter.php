<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\HasImportGuide;
use App\Models\Microprocesador;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class MicroprocesadorImporter extends Importer
{
    use HasImportGuide;

    protected static ?string $model = Microprocesador::class;

    public static function getImportGuideSummary(): ?string
    {
        return 'CSV con coma (,). Máximo 200 filas. Si el nombre del procesador ya existe se omite el duplicado; si no, se crea.';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nombre_micro')
                ->label('Nombre del procesador')
                ->requiredMapping()
                ->rules(['required', 'min:2', 'max:100'])
                ->helperText('Clave de upsert: si el nombre ya existe no se duplica.')
                ->example('Intel Core i7-1365U')
                ->castStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),
        ];
    }

    public function resolveRecord(): Microprocesador
    {
        return Microprocesador::query()->firstOrNew([
            'nombre_micro' => trim((string) ($this->data['nombre_micro'] ?? '')),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de microprocesadores finalizó: '.Number::format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }
}
