<?php

namespace App\Filament\Imports;

use App\Enums\EstadoEquipo;
use App\Filament\Imports\Concerns\HasImportGuide;
use App\Models\Equipo;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;

class EquipoImporter extends Importer
{
    use HasImportGuide;

    protected static ?string $model = Equipo::class;

    public static function getImportGuideSummary(): ?string
    {
        return 'CSV con coma (,). Máximo 200 filas. Importa el catálogo de inventario antes (modelo, procesador, RAM y GPU). Si el service tag ya existe se actualiza. No se importan campos booleanos (BIOS, etiquetado, usuario admin, litigation hold, BitLocker): quedan los valores por defecto o los ya guardados. Observaciones es opcional.';
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('service_tag')
                ->label('Service Tag')
                ->requiredMapping()
                ->rules(['required', 'max:50'])
                ->helperText('Clave de upsert: si el service tag ya existe se actualiza el equipo.')
                ->example('ABC12345')
                ->castStateUsing(self::trimState(...)),
            ImportColumn::make('nombre_equipo')
                ->label('Nombre del equipo')
                ->rules(['nullable', 'max:50'])
                ->helperText('Opcional. Si se omite, queda BOCS.')
                ->example('BOCS-001')
                ->ignoreBlankState()
                ->castStateUsing(self::trimState(...)),
            ImportColumn::make('codigo_activo')
                ->label('Código de activo')
                ->rules(['nullable', 'max:100'])
                ->helperText('Opcional. Debe ser único entre equipos.')
                ->example('ACT-0001')
                ->ignoreBlankState()
                ->castStateUsing(self::trimState(...)),
            ImportColumn::make('estado')
                ->label('Estado')
                ->rules(['nullable', Rule::enum(EstadoEquipo::class)->except([EstadoEquipo::Asignado])])
                ->helperText('Opcional. No usar «asignado»; se deriva de las asignaciones. Si se omite, queda «sin asignar».')
                ->example('sin asignar')
                ->ignoreBlankState()
                ->castStateUsing(function (?string $state): ?string {
                    return self::normalizarEstado($state)?->value;
                }),
            ImportColumn::make('modelo')
                ->label('Modelo')
                ->relationship(resolveUsing: 'nombre')
                ->requiredMapping()
                ->rules(['required'])
                ->helperText('Debe coincidir con un modelo de laptop ya cargado.')
                ->example('Latitude 5540'),
            ImportColumn::make('microprocesador')
                ->label('Microprocesador')
                ->relationship(resolveUsing: 'nombre_micro')
                ->requiredMapping()
                ->rules(['required'])
                ->helperText('Debe coincidir con un procesador ya cargado.')
                ->example('Intel Core i7-1365U'),
            ImportColumn::make('ram')
                ->label('Memoria RAM')
                ->relationship(resolveUsing: 'tipo_ram')
                ->requiredMapping()
                ->rules(['required'])
                ->helperText('Debe coincidir con un tipo de RAM ya cargado.')
                ->example('16GB DDR4'),
            ImportColumn::make('gpu')
                ->label('Tarjeta de video')
                ->relationship(resolveUsing: 'nombre_gpu')
                ->requiredMapping()
                ->rules(['required'])
                ->helperText('Debe coincidir con una GPU ya cargada.')
                ->example('NVIDIA RTX 4050'),
            ImportColumn::make('disco_duro')
                ->label('Disco duro')
                ->rules(['nullable', 'max:50'])
                ->example('512GB SSD')
                ->ignoreBlankState()
                ->castStateUsing(self::trimState(...)),
            ImportColumn::make('proveedor')
                ->label('Proveedor')
                ->rules(['nullable', 'max:100'])
                ->example('Dell')
                ->ignoreBlankState()
                ->castStateUsing(self::trimState(...)),
            ImportColumn::make('so')
                ->label('Sistema operativo')
                ->rules(['nullable', 'max:50'])
                ->helperText('Opcional. Si se omite, queda Windows 11 PRO.')
                ->example('Windows 11 PRO')
                ->ignoreBlankState()
                ->castStateUsing(self::trimState(...)),
            ImportColumn::make('licencia_so')
                ->label('Licencia SO')
                ->rules(['nullable', 'max:50'])
                ->example('XXXXX-XXXXX-XXXXX-XXXXX-XXXXX')
                ->ignoreBlankState()
                ->castStateUsing(self::trimState(...)),
            ImportColumn::make('inicio_garantia')
                ->label('Inicio de garantía')
                ->rules(['nullable', 'date'])
                ->example('2025-01-15')
                ->ignoreBlankState(),
            ImportColumn::make('venc_garantia')
                ->label('Vencimiento de garantía')
                ->rules(['nullable', 'date'])
                ->helperText('Opcional. Si hay inicio, no puede ser anterior.')
                ->example('2028-01-15')
                ->ignoreBlankState(),
            ImportColumn::make('observaciones')
                ->label('Observaciones')
                ->rules(['nullable'])
                ->helperText('Opcional. Texto o HTML sencillo.')
                ->example('Equipo listo para inventario')
                ->ignoreBlankState()
                ->castStateUsing(self::trimState(...)),
        ];
    }

    public function resolveRecord(): Equipo
    {
        $serviceTag = trim((string) ($this->data['service_tag'] ?? ''));

        $equipo = Equipo::query()
            ->withTrashed()
            ->firstOrNew([
                'service_tag' => $serviceTag,
            ]);

        if ($equipo->trashed()) {
            throw new RowImportFailedException('El equipo está eliminado. Restáuralo antes de importarlo.');
        }

        return $equipo;
    }

    protected function beforeValidate(): void
    {
        if (filled($this->data['estado'] ?? null)) {
            $estado = self::normalizarEstado((string) $this->data['estado']);

            if ($estado === EstadoEquipo::Asignado) {
                throw new RowImportFailedException('El estado «asignado» no se importa; se deriva de las asignaciones.');
            }

            if ($estado instanceof EstadoEquipo) {
                $this->data['estado'] = $estado->value;
            }
        }

        $codigoActivo = trim((string) ($this->data['codigo_activo'] ?? ''));

        if ($codigoActivo !== '') {
            $duplicado = Equipo::query()
                ->where('codigo_activo', $codigoActivo)
                ->where('service_tag', '!=', trim((string) ($this->data['service_tag'] ?? '')))
                ->exists();

            if ($duplicado) {
                throw new RowImportFailedException('El código de activo ya está en uso.');
            }
        }

        $inicio = $this->data['inicio_garantia'] ?? null;
        $vencimiento = $this->data['venc_garantia'] ?? null;

        if (filled($inicio) && filled($vencimiento) && $vencimiento < $inicio) {
            throw new RowImportFailedException('El vencimiento de garantía no puede ser anterior al inicio.');
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de equipos finalizó: '.Number::format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }

    private static function trimState(?string $state): ?string
    {
        return filled($state) ? trim($state) : null;
    }

    private static function normalizarEstado(?string $state): ?EstadoEquipo
    {
        if (blank($state)) {
            return null;
        }

        $valor = trim($state);

        return EstadoEquipo::tryFrom($valor)
            ?? collect(EstadoEquipo::cases())->first(
                fn (EstadoEquipo $estado): bool => strcasecmp($estado->getLabel(), $valor) === 0,
            );
    }
}
