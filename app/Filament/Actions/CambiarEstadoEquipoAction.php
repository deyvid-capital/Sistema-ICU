<?php

namespace App\Filament\Actions;

use App\Enums\EstadoEquipo;
use App\Models\Equipo;
use App\Services\CambiarEstadoEquipo;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * Cambia el estado de un equipo desde la tabla.
 *
 * El propio formulario del modal hace de confirmación: cuando el equipo tiene
 * una asignación abierta, se muestra el usuario y su área junto al aviso de
 * que la asignación terminará hoy. Los botones son «Aceptar» y «Rechazar».
 */
class CambiarEstadoEquipoAction
{
    public static function make(): Action
    {
        return Action::make('cambiarEstado')
            ->label('Cambiar estado')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->visible(fn (Equipo $record): bool => ! $record->trashed())
            ->modalIcon(Heroicon::OutlinedExclamationTriangle)
            ->modalIconColor('warning')
            ->modalHeading('Cambiar estado del equipo')
            ->modalSubmitActionLabel('Aceptar')
            ->modalCancelActionLabel('Rechazar')
            ->fillForm(fn (Equipo $record): array => ['estado' => $record->estado?->value])
            ->schema([
                Select::make('estado')
                    ->label('Nuevo estado')
                    ->native(false)
                    ->required()
                    ->live()
                    ->options(fn (Equipo $record): array => static::opciones($record)),
                Placeholder::make('aviso_definitivo')
                    ->hiddenLabel()
                    ->visible(fn (Equipo $record): bool => $record->estado?->esDefinitivo() ?? false)
                    ->content(fn (Equipo $record): HtmlString => new HtmlString(
                        'Este equipo está en <strong>'.e($record->estado->getLabel()).'</strong>, una baja definitiva. '
                        .'Reactivarlo quedará registrado en el historial de actividad.'
                    )),
                Placeholder::make('aviso_asignacion')
                    ->hiddenLabel()
                    ->visible(fn (Equipo $record, Get $get): bool => static::cierraAsignacion($record, $get('estado')))
                    ->content(fn (Equipo $record): HtmlString => static::avisoAsignacion($record)),
            ])
            ->action(function (array $data, Equipo $record): void {
                $servicio = app(CambiarEstadoEquipo::class);
                $nuevo = EstadoEquipo::from($data['estado']);

                $cerrada = $servicio->ejecutar($record, $nuevo);

                $body = "El equipo {$record->getKey()} quedó en «{$nuevo->getLabel()}».";

                if ($cerrada !== null) {
                    $body .= ' Se terminó la asignación de '
                        .CambiarEstadoEquipo::descripcionAsignacion($cerrada)
                        .' con fecha '.$cerrada->fecha_fin->format('d/m/Y').'.';
                }

                Notification::make()
                    ->success()
                    ->title('Estado actualizado')
                    ->body($body)
                    ->send();
            });
    }

    /**
     * Estados elegibles. Se incluye el estado actual aunque sea automático,
     * para que el select no arranque vacío en un equipo asignado.
     *
     * @return array<string, string>
     */
    private static function opciones(Equipo $record): array
    {
        $actual = $record->estado;

        return collect(EstadoEquipo::cases())
            ->filter(fn (EstadoEquipo $estado): bool => $estado->esSeleccionableManualmente() || $estado === $actual)
            ->mapWithKeys(fn (EstadoEquipo $estado): array => [$estado->value => $estado->getLabel()])
            ->all();
    }

    private static function cierraAsignacion(Equipo $record, mixed $estado): bool
    {
        $nuevo = is_string($estado) ? EstadoEquipo::tryFrom($estado) : null;

        if ($nuevo === null) {
            return false;
        }

        return app(CambiarEstadoEquipo::class)->requiereConfirmacion($record, $nuevo);
    }

    private static function avisoAsignacion(Equipo $record): HtmlString
    {
        $activa = app(CambiarEstadoEquipo::class)->asignacionActiva($record);

        if ($activa === null) {
            return new HtmlString('');
        }

        $descripcion = e(CambiarEstadoEquipo::descripcionAsignacion($activa));
        $fecha = now()->format('d/m/Y');

        return new HtmlString(
            "Se terminará la asignación actual de <strong>{$descripcion}</strong>, "
            ."con fecha de fin <strong>{$fecha}</strong>."
        );
    }
}
