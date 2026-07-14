<?php

namespace App\Filament\Resources\AzureAds\Schemas;

use App\Models\AzureAd;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AzureAdInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('iniciales')
                    ->label('Iniciales')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                TextEntry::make('sincronizacion')
                    ->label('Sincronización')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Nube' => 'success',
                        'Local / Nube' => 'warning',
                        'Local / No sincronizado' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                IconEntry::make('mfa')
                    ->label('MFA')
                    ->boolean(),
                IconEntry::make('estado')
                    ->label('Activo')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn (AzureAd $record): bool => $record->trashed()),
            ]);
    }
}
