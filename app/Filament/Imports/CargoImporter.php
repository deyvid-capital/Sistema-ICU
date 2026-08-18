<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\HasImportGuide;
use App\Models\Area;
use App\Models\Cargo;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class CargoImporter extends Importer
{
    use HasImportGuide;

    protected static ?string $model = Cargo::class;

    public static function getImportGuideSummary(): ?string
    {
        return 'CSV con coma (,). Máximo 200 filas. Importa áreas antes. El área debe existir; no se crea desde este archivo.';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('area')
                ->label('Área')
                ->relationship(resolveUsing: 'area')
                ->requiredMapping()
                ->rules(['required'])
                ->helperText('Debe coincidir con el nombre de un área ya cargada.')
                ->example('Tecnología'),
            ImportColumn::make('cargo')
                ->label('Cargo')
                ->requiredMapping()
                ->rules(['required', 'max:150'])
                ->helperText('Si el cargo ya existe en esa área se actualiza; si no, se crea.')
                ->example('Analista'),
            ImportColumn::make('descripcion')
                ->label('Descripción')
                ->rules(['nullable', 'max:255'])
                ->example('Analista de sistemas'),
        ];
    }

    public function resolveRecord(): Cargo
    {
        $area = Area::query()
            ->where('area', $this->data['area'] ?? null)
            ->first();

        if (! $area) {
            return new Cargo;
        }

        return Cargo::query()->firstOrNew([
            'area_id' => $area->id_area,
            'cargo' => $this->data['cargo'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de cargos finalizó: '.Number::format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }
}
