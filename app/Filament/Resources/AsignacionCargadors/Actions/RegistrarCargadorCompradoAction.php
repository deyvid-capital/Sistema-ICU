<?php

namespace App\Filament\Resources\AsignacionCargadors\Actions;

use App\Enums\EstadoCargador;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use App\Services\MovimientoCargador;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use Throwable;

class RegistrarCargadorCompradoAction
{
    public static function make(): Action
    {
        return Action::make('registrarCargadorComprado')
            ->label('Registrar cargador comprado')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (AsignacionCargador $record): bool => static::puedeRegistrar($record))
            ->modalHeading('Registrar cargador comprado')
            ->modalDescription('Si el cargador está prestado, vuelve al equipo origen y el nuevo queda en este equipo. Si está cedido, se queda aquí y el nuevo va al origen.')
            ->modalSubmitActionLabel('Registrar')
            ->schema([
                Select::make('serial_nuevo')
                    ->label('Cargador comprado')
                    ->options(fn (): array => Cargador::query()
                        ->where('estado', EstadoCargador::Disponible)
                        ->orderBy('serial')
                        ->get()
                        ->mapWithKeys(fn (Cargador $cargador): array => [
                            $cargador->serial => trim("{$cargador->serial} — {$cargador->marca} {$cargador->modelo}"),
                        ])
                        ->all())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data, AsignacionCargador $record): void {
                try {
                    app(MovimientoCargador::class)->registrarComprado(
                        $record,
                        (string) $data['serial_nuevo'],
                    );
                } catch (RuntimeException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo registrar')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->danger()
                        ->title('No se pudo registrar')
                        ->body('Ocurrió un error al registrar el cargador comprado.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Cargador comprado registrado')
                    ->send();
            });
    }

    public static function puedeRegistrar(AsignacionCargador $record): bool
    {
        return ! $record->trashed()
            && $record->fecha_fin === null
            && filled($record->service_tag_origen);
    }
}
