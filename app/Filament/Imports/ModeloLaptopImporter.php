<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\HasImportGuide;
use App\Models\ModeloLaptop;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class ModeloLaptopImporter extends Importer
{
    use HasImportGuide;

    protected static ?string $model = ModeloLaptop::class;

    public static function getImportGuideSummary(): ?string
    {
        return 'CSV con coma (,). Máximo 200 filas. Si el nombre del modelo ya existe se omite el duplicado; si no, se crea.';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nombre')
                ->label('Nombre del modelo')
                ->requiredMapping()
                ->rules(['required', 'min:2', 'max:100'])
                ->helperText('Clave de upsert: si el nombre ya existe no se duplica.')
                ->example('Latitude 5540')
                ->castStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),
        ];
    }

    public function resolveRecord(): ModeloLaptop
    {
        return ModeloLaptop::query()->firstOrNew([
            'nombre' => trim((string) ($this->data['nombre'] ?? '')),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de modelos de laptop finalizó: '.Number::format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }
}
