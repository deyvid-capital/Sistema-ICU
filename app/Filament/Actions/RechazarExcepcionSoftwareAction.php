<?php

namespace App\Filament\Actions;

use App\Models\SoftwareExc;
use App\Services\Software\DecidirExcepcionSoftware;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

class RechazarExcepcionSoftwareAction
{
    public static function make(): Action
    {
        return Action::make('rechazarExcepcion')
            ->label('Rechazar')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (SoftwareExc $record): bool => $record->puedeDecidir(auth()->user()))
            ->authorize('reject')
            ->modalHeading('Rechazar excepción')
            ->modalSubmitActionLabel('Rechazar')
            ->modalIcon(Heroicon::OutlinedXCircle)
            ->modalIconColor('danger')
            ->schema([
                Textarea::make('comentario_decision')
                    ->label('Motivo del rechazo')
                    ->required()
                    ->maxLength(1000)
                    ->helperText(function (SoftwareExc $record): ?string {
                        if (! $record->requiereNotaDeIntervencion(auth()->user())) {
                            return null;
                        }

                        $nombre = $record->aprobador?->name ?? 'el aprobador designado';

                        return "Indica por qué intervienes en lugar de {$nombre} y el motivo del rechazo.";
                    }),
            ])
            ->action(function (array $data, SoftwareExc $record): void {
                try {
                    app(DecidirExcepcionSoftware::class)->rechazar(
                        $record,
                        auth()->user(),
                        (string) ($data['comentario_decision'] ?? ''),
                    );
                } catch (InvalidArgumentException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo rechazar')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Excepción rechazada')
                    ->body('La excepción de software quedó rechazada.')
                    ->send();
            });
    }
}
