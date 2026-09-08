<?php

namespace App\Filament\Resources\AsignacionCargadors\Actions;

use App\Enums\ModoAsignacionCargador;
use App\Exceptions\EquipoOrigenConUsuarioException;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\User;
use App\Services\MovimientoCargador;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use Throwable;

class UsarCargadorDeOtroEquipoAction
{
    public static function make(): Action
    {
        return Action::make('usarCargadorDeOtroEquipo')
            ->label('Usar cargador de otro equipo')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('warning')
            ->modalHeading('Usar cargador de otro equipo')
            ->modalDescription('Solo si el equipo del que sale el cargador no tiene un usuario asignado. Si lo tiene, se avisará en la campana con el nombre y el acceso a esa asignación.')
            ->modalSubmitActionLabel('Confirmar')
            ->schema([
                Select::make('service_tag')
                    ->label('Equipo que necesita el cargador')
                    ->options(fn (): array => Equipo::query()
                        ->orderBy('service_tag')
                        ->get()
                        ->mapWithKeys(fn (Equipo $equipo): array => [
                            $equipo->service_tag => trim("{$equipo->service_tag} — {$equipo->nombre_equipo}"),
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
                Select::make('serial_cargador')
                    ->label('Cargador')
                    ->options(fn (): array => Cargador::query()
                        ->whereHas('asignacionActiva')
                        ->orderBy('serial')
                        ->get()
                        ->mapWithKeys(fn (Cargador $cargador): array => [
                            $cargador->serial => trim("{$cargador->serial} — {$cargador->marca} {$cargador->modelo}"),
                        ])
                        ->all())
                    ->searchable()
                    ->required()
                    ->helperText('Solo cargadores que hoy están en otro equipo.'),
                Radio::make('modo')
                    ->label('Qué pasa con este cargador')
                    ->options(ModoAsignacionCargador::class)
                    ->required()
                    ->helperText('Prestado: vuelve al origen cuando llegue el nuevo. Cedido: se queda; el nuevo va al equipo origen.'),
            ])
            ->action(function (array $data): void {
                $actor = auth()->user();

                if (! $actor instanceof User) {
                    return;
                }

                try {
                    app(MovimientoCargador::class)->prestarOCeder(
                        serialCargador: (string) $data['serial_cargador'],
                        equipoDestino: (string) $data['service_tag'],
                        modo: $data['modo'] instanceof ModoAsignacionCargador
                            ? $data['modo']
                            : ModoAsignacionCargador::from((string) $data['modo']),
                        actor: $actor,
                    );
                } catch (EquipoOrigenConUsuarioException) {
                    Notification::make()
                        ->warning()
                        ->title('No se puede usar ese cargador')
                        ->body('El equipo origen tiene un usuario asignado. Revise la campana para ver a quién y abrir la asignación.')
                        ->send();

                    return;
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo mover el cargador')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('No se pudo mover el cargador')
                        ->body('Ocurrió un error al registrar el movimiento.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Cargador movido')
                    ->body('Cuando llegue el cargador comprado, regístrelo desde la asignación creada.')
                    ->send();
            });
    }
}
