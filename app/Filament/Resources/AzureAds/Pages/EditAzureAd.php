<?php

namespace App\Filament\Resources\AzureAds\Pages;

use App\Filament\Resources\AzureAds\AzureAdResource;
use App\Models\AzureAd;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditAzureAd extends EditRecord
{
    protected static string $resource = AzureAdResource::class;

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cuenta actualizada')
            ->body('Los datos de Azure AD se guardaron correctamente.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->label('Ver'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AzureAd $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Cuenta Azure AD eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AzureAd $record): bool => $record->trashed())
                    ->successNotificationTitle('Cuenta Azure AD eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AzureAd $record): bool => $record->trashed())
                    ->successNotificationTitle('Cuenta Azure AD restaurada correctamente'),
            ])
                ->label('Acciones')
                ->icon(Heroicon::OutlinedEllipsisVertical)
                ->color('info')
                ->button()
                ->tooltip('Acciones'),
        ];
    }
}
