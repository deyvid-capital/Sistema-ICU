<?php

namespace App\Filament\Resources\Concerns;

use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * Indicador de solo lectura: si el colaborador tiene FortiToken asignado.
 * El alta/edición del serial vive en Seguridad del usuario.
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

                $usuario = User::query()->with('fortitoken')->find($usuarioId);

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

    protected static function fortitokenEtiqueta(?User $usuario): string
    {
        if ($usuario === null) {
            return 'Sin usuario';
        }

        $token = $usuario->fortitoken;

        if ($token === null) {
            return 'Sin FortiToken';
        }

        return $token->estado
            ? 'Con FortiToken (activo)'
            : 'Con FortiToken (inactivo)';
    }

    protected static function fortitokenEtiquetaCorta(?User $usuario): string
    {
        if ($usuario === null) {
            return '—';
        }

        $token = $usuario->fortitoken;

        if ($token === null) {
            return 'No';
        }

        return $token->estado ? 'Sí · activo' : 'Sí · inactivo';
    }

    protected static function fortitokenColor(?User $usuario): string
    {
        $token = $usuario?->fortitoken;

        if ($token === null) {
            return 'gray';
        }

        return $token->estado ? 'success' : 'warning';
    }

    protected static function fortitokenIcon(?User $usuario): Heroicon
    {
        $token = $usuario?->fortitoken;

        if ($token === null) {
            return Heroicon::OutlinedXCircle;
        }

        return $token->estado
            ? Heroicon::OutlinedCheckCircle
            : Heroicon::OutlinedExclamationCircle;
    }
}
