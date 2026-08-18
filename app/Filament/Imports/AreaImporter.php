<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\HasImportGuide;
use App\Models\Area;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class AreaImporter extends Importer
{
    use HasImportGuide;

    protected static ?string $model = Area::class;

    public static function getImportGuideSummary(): ?string
    {
        return 'CSV con coma (,). Máximo 200 filas. Si el nombre de área ya existe se actualiza; si no, se crea.';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('area')
                ->label('Área')
                ->requiredMapping()
                ->rules(['required', 'max:100'])
                ->helperText('Clave de upsert: si el nombre ya existe se actualiza.')
                ->example('Tecnología'),
            ImportColumn::make('descripcion')
                ->label('Descripción')
                ->rules(['nullable', 'max:255'])
                ->example('Área de sistemas'),
        ];
    }

    public function resolveRecord(): Area
    {
        return Area::query()->firstOrNew([
            'area' => $this->data['area'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de áreas finalizó: '.Number::format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }
}
