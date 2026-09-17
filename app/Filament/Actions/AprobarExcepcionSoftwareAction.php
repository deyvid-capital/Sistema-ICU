<?php

namespace App\Filament\Actions;

use App\Models\SoftwareExc;
use App\Services\Software\DecidirExcepcionSoftware;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

class AprobarExcepcionSoftwareAction
{
    public static function make(): Action
    {
        return Action::make('aprobarExcepcion')
            ->label('Aprobar')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (SoftwareExc $record): bool => $record->puedeDecidir(auth()->user()))
            ->authorize('approve')
            ->modalHeading('Aprobar excepción')
            ->modalSubmitActionLabel('Aprobar')
            ->modalIcon(Heroicon::OutlinedCheckCircle)
            ->modalIconColor('success')
            ->requiresConfirmation(fn (SoftwareExc $record): bool => ! $record->requiereNotaDeIntervencion(auth()->user()))
            ->schema([
                Textarea::make('comentario_decision')
                    ->label('Nota de intervención')
                    ->required()
                    ->maxLength(1000)
                    ->visible(fn (SoftwareExc $record): bool => $record->requiereNotaDeIntervencion(auth()->user()))
                    ->helperText(function (SoftwareExc $record): string {
                        $nombre = $record->aprobador?->name ?? 'el aprobador designado';

                        return "Indica por qué intervienes en lugar de {$nombre}. Esta nota queda como respaldo.";
                    }),
            ])
            ->action(function (array $data, SoftwareExc $record): void {
                try {
                    app(DecidirExcepcionSoftware::class)->aprobar(
                        $record,
                        auth()->user(),
                        $data['comentario_decision'] ?? null,
                    );
                } catch (InvalidArgumentException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('No se pudo aprobar')
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Excepción aprobada')
                    ->body('La excepción de software quedó aprobada.')
                    ->send();
            });
    }
}
