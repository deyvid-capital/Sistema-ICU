<?php

namespace App\Filament\Actions;

use App\Models\Cargo;
use App\Models\Software;
use App\Models\User;
use App\Services\Software\AsignarSoftwareEnBloque;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;

/**
 * Asignación en bloque según el software autorizado por cargo.
 *
 * Se comporta según el dueño del gestor de relaciones:
 * - User: le asigna todo el software activo autorizado para su cargo.
 * - Software: lo asigna a todas las personas de un cargo que no están de baja.
 */
class AsignarSoftwarePorCargoAction
{
    public static function make(RelationManager $relationManager): Action
    {
        $owner = $relationManager->getOwnerRecord();

        return $owner instanceof User
            ? self::paraUsuario($owner)
            : self::paraSoftware($owner);
    }

    private static function paraUsuario(User $usuario): Action
    {
        return self::base()
            ->modalHeading('Asignar el software de su cargo')
            ->modalDescription(fn (): string => 'Se asigna al usuario todo el software activo autorizado para el cargo «'
                .($usuario->cargo?->cargo ?? '—')
                .'». Lo que ya tiene asignado no cambia.')
            ->requiresConfirmation()
            ->disabled(fn (): bool => $usuario->id_cargo === null)
            ->tooltip(fn (): ?string => $usuario->id_cargo === null
                ? 'El usuario no tiene cargo asignado.'
                : null)
            ->action(function () use ($usuario): void {
                $asignados = app(AsignarSoftwareEnBloque::class)->porCargoDelUsuario($usuario);

                Notification::make()
                    ->success()
                    ->title('Asignación por cargo completada')
                    ->body("Se asignaron {$asignados} software(s) del cargo «{$usuario->cargo?->cargo}» al usuario.")
                    ->send();
            });
    }

    private static function paraSoftware(Software $software): Action
    {
        return self::base()
            ->modalHeading('Asignar este software por cargo')
            ->modalDescription('Se asigna a todas las personas del cargo elegido que no están de baja. Las que ya lo tienen no cambian.')
            ->disabled(fn (): bool => ! $software->estado)
            ->tooltip(fn (): ?string => $software->estado
                ? null
                : 'El software está inactivo.')
            ->schema([
                Select::make('cargo_id')
                    ->label('Cargo')
                    ->options(fn (): array => Cargo::query()
                        ->orderBy('cargo')
                        ->pluck('cargo', 'id_cargo')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->action(function (array $data) use ($software): void {
                $cargo = Cargo::query()->findOrFail($data['cargo_id']);

                $asignados = app(AsignarSoftwareEnBloque::class)->aPersonasDelCargo($software, $cargo);

                Notification::make()
                    ->success()
                    ->title('Asignación por cargo completada')
                    ->body("Se asignó este software a {$asignados} persona(s) del cargo «{$cargo->cargo}».")
                    ->send();
            });
    }

    private static function base(): Action
    {
        return Action::make('asignarPorCargo')
            ->label('Asignar por cargo')
            ->icon(Heroicon::OutlinedBriefcase)
            ->color('info')
            ->modalSubmitActionLabel('Asignar');
    }
}
