<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Nombre completo')
                ->state(function (User $record): string {
                    $nombre = trim((string) ($record->name ?: trim("{$record->nombres} {$record->apellidos}")));

                    return $nombre !== '' ? $nombre : '—';
                }),
            ExportColumn::make('area.area')
                ->label('Área')
                ->state(fn (User $record): string => $record->area?->area ?: '—'),
            ExportColumn::make('cargo.cargo')
                ->label('Cargo')
                ->state(fn (User $record): string => $record->cargo?->cargo ?: '—'),
            ExportColumn::make('email')
                ->label('Correo')
                ->state(fn (User $record): string => filled($record->email) ? $record->email : '—'),
            ExportColumn::make('telefono')
                ->label('Celular')
                ->state(fn (User $record): string => filled($record->telefono) ? $record->telefono : '—')
                ->preventFormulaInjection(),
        ];
    }

    /**
     * Solo usuarios con la última alta vigente (sin Baja posterior).
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query
            ->with(['area:id_area,area', 'cargo:id_cargo,cargo'])
            ->whereHas(
                'ultimoMovimiento',
                fn (Builder $movimientoQuery): Builder => $movimientoQuery->where('tipo', 'Alta'),
            )
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->orderBy('name');
    }

    /**
     * @return array<ExportFormat>
     */
    public function getFormats(): array
    {
        return [
            ExportFormat::Xlsx,
        ];
    }

    public function getFileName(Export $export): string
    {
        return 'lista-usuarios-'.now()
            ->timezone(config('app.display_timezone'))
            ->format('Y-m-d');
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'La exportación de usuarios finalizó. Se exportaron '
            .Number::format($export->successful_rows).' '
            .str('fila')->plural($export->successful_rows).'.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' Fallaron '.Number::format($failedRowsCount).' '
                .str('fila')->plural($failedRowsCount).'.';
        }

        return $body;
    }
}
