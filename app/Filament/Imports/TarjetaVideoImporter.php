<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\HasImportGuide;
use App\Models\TarjetaVideo;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class TarjetaVideoImporter extends Importer
{
    use HasImportGuide;

    protected static ?string $model = TarjetaVideo::class;

    public static function getImportGuideSummary(): ?string
    {
        return 'CSV con coma (,). Máximo 200 filas. Si el nombre de la GPU ya existe se omite el duplicado; si no, se crea.';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nombre_gpu')
                ->label('Nombre de la GPU')
                ->requiredMapping()
                ->rules(['required', 'min:2', 'max:100'])
                ->helperText('Clave de upsert: si el nombre ya existe no se duplica.')
                ->example('NVIDIA RTX 4050')
                ->castStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),
        ];
    }

    public function resolveRecord(): TarjetaVideo
    {
        return TarjetaVideo::query()->firstOrNew([
            'nombre_gpu' => trim((string) ($this->data['nombre_gpu'] ?? '')),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de tarjetas de video finalizó: '.Number::format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }
}
