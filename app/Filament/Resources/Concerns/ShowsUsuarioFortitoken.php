<?php

namespace App\Filament\Resources\Concerns;

use App\Models\User;
use App\Models\UsuarioFortitoken;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * Indicador de solo lectura de los FortiToken del colaborador (puede tener varios).
 * El alta/edición vive en Seguridad del usuario.
 */
trait ShowsUsuarioFortitoken
{
    protected static function fortitokenFormPlaceholder(): Placeholder
    {
        return Placeholder::make('fortitoken_indicador')
            ->label('FortiToken')
            ->content(function (Get $get, mixed $livewire): string {
                $usuarioId = $get('usuario_id');

                if (blank($usuarioId) && is_object($livewire) && method_exists($livewire, 'getOwnerRecord')) {
                    $usuarioId = $livewire->getOwnerRecord()?->getKey();
                }

                if (blank($usuarioId)) {
                    return 'Selecciona un usuario para ver si tiene FortiToken.';
                }

                $usuario = User::query()->with('fortitokens')->find($usuarioId);

                return self::fortitokenEtiqueta($usuario);
            })
            ->helperText('Se gestiona en Seguridad → FortiToken del colaborador.');
    }

    protected static function fortitokenTableColumn(): TextColumn
    {
        return TextColumn::make('fortitoken_indicador')
            ->label('FortiToken')
            ->badge()
            ->state(fn (Model $record): string => self::fortitokenEtiquetaCorta($record->usuario))
            ->color(fn (Model $record): string => self::fortitokenColor($record->usuario))
            ->icon(fn (Model $record): Heroicon => self::fortitokenIcon($record->usuario))
            ->sortable(false)
            ->searchable(false);
    }

    protected static function fortitokenInfolistEntry(): TextEntry
    {
        return TextEntry::make('fortitoken_indicador')
            ->label('FortiToken')
            ->badge()
            ->state(fn (Model $record): string => self::fortitokenEtiqueta($record->usuario))
            ->color(fn (Model $record): string => self::fortitokenColor($record->usuario))
            ->icon(fn (Model $record): Heroicon => self::fortitokenIcon($record->usuario));
    }

    /**
     * Ej.: "Producción (activo), Contingencia (inactivo)".
     */
    protected static function fortitokenEtiqueta(?User $usuario): string
    {
        if ($usuario === null) {
            return 'Sin usuario';
        }

        if ($usuario->fortitokens->isEmpty()) {
            return 'Sin FortiToken';
        }

        return $usuario->fortitokens
            ->map(fn (UsuarioFortitoken $token): string => $token->nombre_token.($token->estado ? ' (activo)' : ' (inactivo)'))
            ->implode(', ');
    }

    protected static function fortitokenEtiquetaCorta(?User $usuario): string
    {
        if ($usuario === null) {
            return '—';
        }

        $total = $usuario->fortitokens->count();

        if ($total === 0) {
            return 'No';
        }

        $activos = $usuario->fortitokens->where('estado', true)->count();

        return $activos === 0
            ? "Sí · {$total} inactivo".($total > 1 ? 's' : '')
            : "Sí · {$activos} activo".($activos > 1 ? 's' : '');
    }

    protected static function fortitokenColor(?User $usuario): string
    {
        return match (self::fortitokenSituacion($usuario)) {
            'sin' => 'gray',
            'activo' => 'success',
            default => 'warning',
        };
    }

    protected static function fortitokenIcon(?User $usuario): Heroicon
    {
        return match (self::fortitokenSituacion($usuario)) {
            'sin' => Heroicon::OutlinedXCircle,
            'activo' => Heroicon::OutlinedCheckCircle,
            default => Heroicon::OutlinedExclamationCircle,
        };
    }

    /**
     * "sin" (ninguno), "activo" (al menos uno activo) o "inactivo" (todos inactivos).
     */
    private static function fortitokenSituacion(?User $usuario): string
    {
        $tokens = $usuario?->fortitokens;

        return match (true) {
            $tokens === null || $tokens->isEmpty() => 'sin',
            $tokens->contains('estado', true) => 'activo',
            default => 'inactivo',
        };
    }
}
