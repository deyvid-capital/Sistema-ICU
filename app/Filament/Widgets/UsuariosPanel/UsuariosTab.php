<?php

namespace App\Filament\Widgets\UsuariosPanel;

use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Pestaña general: movimiento, rol y 2FA de cada usuario.
 */
class UsuariosTab extends PestanaUsuarios
{
    public static function getEtiqueta(): string
    {
        return 'Usuarios';
    }

    public static function getIcono(): Heroicon
    {
        return Heroicon::OutlinedUsers;
    }

    protected function relaciones(): array
    {
        return ['ultimoMovimiento', 'roles', 'breezySession'];
    }

    protected function columnasPropias(): array
    {
        return [
            TextColumn::make('ultimoMovimiento.tipo')
                ->label('Movimiento')
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    'Alta' => 'success',
                    'Baja' => 'danger',
                    default => 'gray',
                })
                ->placeholder('Sin movimiento')
                ->toggleable(),
            TextColumn::make('roles.name')
                ->label('Rol')
                ->badge()
                ->color('info')
                ->formatStateUsing(fn (string $state): string => Str::headline($state))
                ->placeholder('Sin rol')
                ->visibleFrom('lg')
                ->toggleable(),
            IconColumn::make('dos_factores')
                ->label('2FA')
                ->state(fn (User $record): bool => (bool) $record->breezySession?->is_confirmed)
                ->boolean()
                ->tooltip(fn (bool $state): string => $state ? '2FA activado' : '2FA sin activar')
                ->alignCenter()
                ->toggleable(),
        ];
    }

    protected function filtrosPropios(): array
    {
        return [
            SelectFilter::make('roles')
                ->label('Rol')
                ->relationship('roles', 'name')
                ->getOptionLabelFromRecordUsing(fn ($record): string => Str::headline($record->name))
                ->multiple()
                ->preload(),
            TernaryFilter::make('dos_factores')
                ->label('2FA')
                ->placeholder('Todos')
                ->trueLabel('Activado')
                ->falseLabel('Sin activar')
                ->queries(
                    true: fn (Builder $query): Builder => $query->whereHas('breezySessions', $this->dosFactoresConfirmado(...)),
                    false: fn (Builder $query): Builder => $query->whereDoesntHave('breezySessions', $this->dosFactoresConfirmado(...)),
                    blank: fn (Builder $query): Builder => $query,
                ),
        ];
    }

    /**
     * Mismo criterio que BreezySession::isConfirmed: secreto generado y confirmado.
     */
    private function dosFactoresConfirmado(Builder $query): Builder
    {
        return $query->whereNotNull('two_factor_secret')->whereNotNull('two_factor_confirmed_at');
    }
}
