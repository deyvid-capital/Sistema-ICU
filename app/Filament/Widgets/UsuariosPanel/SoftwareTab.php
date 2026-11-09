<?php

namespace App\Filament\Widgets\UsuariosPanel;

use App\Models\Software;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Software activo asignado a cada usuario.
 */
class SoftwareTab extends PestanaUsuarios
{
    public static function getEtiqueta(): string
    {
        return 'Software';
    }

    public static function getIcono(): Heroicon
    {
        return Heroicon::OutlinedCodeBracketSquare;
    }

    protected function columnasDeBadges(): array
    {
        return [
            ColumnaDeBadges::make('software', 'Software', 'softwareUsuarios', 'software.nombre_software', 'info'),
        ];
    }

    protected function filtrosPropios(): array
    {
        return [
            SelectFilter::make('software')
                ->label('Software')
                ->options(fn (): array => Software::query()->orderBy('nombre_software')->pluck('nombre_software', 'id_software')->all())
                ->searchable()
                ->multiple()
                ->query(fn (Builder $query, array $data): Builder => blank($data['values'] ?? null)
                    ? $query
                    : $query->whereHas('softwareUsuarios', fn (Builder $query): Builder => $query
                        ->where('estado', true)
                        ->whereIn('id_software', $data['values']))),
        ];
    }
}
