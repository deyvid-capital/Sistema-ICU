<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\HasImportGuide;
use App\Models\MemoriaRam;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class MemoriaRamImporter extends Importer
{
    use HasImportGuide;

    protected static ?string $model = MemoriaRam::class;

    public static function getImportGuideSummary(): ?string
    {
        return 'CSV con coma (,). Máximo 200 filas. Si el tipo de RAM ya existe se omite el duplicado; si no, se crea.';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('tipo_ram')
                ->label('Tipo de RAM')
                ->requiredMapping()
                ->rules(['required', 'min:2', 'max:50'])
                ->helperText('Clave de upsert: si el tipo ya existe no se duplica.')
                ->example('16GB DDR4')
                ->castStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),
        ];
    }

    public function resolveRecord(): MemoriaRam
    {
        return MemoriaRam::query()->firstOrNew([
            'tipo_ram' => trim((string) ($this->data['tipo_ram'] ?? '')),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de memorias RAM finalizó: '.Number::format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }
}
