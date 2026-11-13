<?php

namespace App\Filament\Resources\Users\Actions;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Services\Movimientos\ReasignarTrasReingreso;
use App\Support\Notifications\NotifyAdmins;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * «Reasignar todo»: devuelve a un colaborador que reingresa los accesos y
 * las asignaciones que perdió con su Baja. Solo aparece si está de Alta, tuvo
 * una Baja antes y queda algo por devolver. Exige poder editar al usuario.
 */
class ReasignarTrasReingresoAction
{
    public static function make(): Action
    {
        return Action::make('reasignarTrasReingreso')
            ->label('Reasignar todo')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->authorize(fn (User $record): bool => UserResource::canEdit($record))
            ->visible(fn (User $record): bool => ! $record->trashed()
                && app(ReasignarTrasReingreso::class)->pendiente($record)->hayAlgo())
            ->requiresConfirmation()
            ->modalHeading(fn (User $record): string => "Reasignar todo a {$record->name}")
            ->modalDescription(fn (User $record): string => static::aviso($record))
            ->modalSubmitActionLabel('Sí, reasignar todo')
            ->action(function (User $record): void {
                $resumen = app(ReasignarTrasReingreso::class)->ejecutar($record);

                if (! $resumen->hayAlgo()) {
                    Notification::make()
                        ->warning()
                        ->title('No había nada que reasignar')
                        ->body($resumen->avisoDeOmitidos())
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Reasignación completa')
                    ->body(trim("Se devolvió a {$record->name}: {$resumen->detalle()}. ".$resumen->avisoDeOmitidos()))
                    ->duration(10000)
                    ->send();

                NotifyAdmins::dispatch(
                    title: 'Reingreso: se reasignó todo',
                    body: "Se devolvió a {$record->name}: {$resumen->detalle()} — por ".(auth()->user()?->name ?? 'Sistema').'.',
                    url: UserResource::getUrl('view', ['record' => $record->getKey()]),
                    icon: 'heroicon-o-arrow-path',
                    color: 'warning',
                    severity: CriticalEventNotification::SEVERIDAD_ALTA,
                );
            });
    }

    /**
     * Lo que va a pasar, dicho antes de confirmar.
     */
    public static function aviso(User $usuario): string
    {
        $servicio = app(ReasignarTrasReingreso::class);
        $pendiente = $servicio->pendiente($usuario);
        $fecha = $servicio->ultimaBaja($usuario)?->fecha?->format('d/m/Y');

        return trim(
            "Se le devolverá todo, sin excepción: {$pendiente->detalle()}. "
            .'Se reactivan todos sus accesos inactivos (software, licencias, sistemas, SharePoint, internet, escritorios, FortiToken, Azure AD y unidades compartidas) '
            ."y se reabren las asignaciones que se cerraron con su baja del {$fecha}. "
            .'Lo que hoy tenga otra persona no se toca. '
            .$pendiente->avisoDeOmitidos()
        );
    }
}
