<?php

namespace App\Filament\Widgets\UsuariosPanel;

use App\Models\Licencia;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Licencias activas asignadas a cada usuario.
 */
class LicenciasTab extends PestanaUsuarios
{
    public static function getEtiqueta(): string
    {
        return 'Licencias';
    }

    public static function getIcono(): Heroicon
    {
        return Heroicon::OutlinedKey;
    }

    protected function columnasDeBadges(): array
    {
        return [
            ColumnaDeBadges::make('licencias', 'Licencias', 'licenciasUsuarios', 'licencia.nombre_licencia'),
        ];
    }

    protected function filtrosPropios(): array
    {
        return [
            SelectFilter::make('licencia')
                ->label('Licencia')
                ->options(fn (): array => Licencia::query()->orderBy('nombre_licencia')->pluck('nombre_licencia', 'id')->all())
                ->searchable()
                ->multiple()
                ->query(fn (Builder $query, array $data): Builder => blank($data['values'] ?? null)
                    ? $query
                    : $query->whereHas('licenciasUsuarios', fn (Builder $query): Builder => $query
                        ->where('estado', true)
                        ->whereIn('licencia_id', $data['values']))),
        ];
    }
}
