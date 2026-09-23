<?php

namespace App\Filament\Resources\AccesoInternets\Schemas;

use App\Filament\Resources\Concerns\ShowsUsuarioFortitoken;
use App\Models\AccesoInternet;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AccesoInternetInfolist
{
    use ShowsUsuarioFortitoken;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                static::fortitokenInfolistEntry(),
                TextEntry::make('usuario_red')
                    ->label('Usuario de red')
                    ->placeholder('—'),
                TextEntry::make('nivel_acceso')
                    ->label('Nivel de acceso')
                    ->placeholder('—'),
                TextEntry::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                IconEntry::make('estado')
                    ->label('Activo')
                    ->boolean(),
                TextEntry::make('observaciones')
                    ->label('Observaciones')
                    ->placeholder('—')
                    ->columnSpanFull(),
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
                    ->visible(fn (AccesoInternet $record): bool => $record->trashed()),
            ]);
    }
}
