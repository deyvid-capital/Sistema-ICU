<?php

namespace App\Filament\Resources\AccesoVirtuals\Schemas;

use App\Filament\Resources\Concerns\ShowsUsuarioFortitoken;
use App\Models\AccesoVirtual;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AccesoVirtualInfolist
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
                TextEntry::make('virtual.maquina_virtual')
                    ->label('Máquina virtual')
                    ->placeholder('—'),
                TextEntry::make('recursoVpn.nombre_vpn')
                    ->label('Recurso VPN')
                    ->placeholder('Sin VPN'),
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
                    ->visible(fn (AccesoVirtual $record): bool => $record->trashed()),
            ]);
    }
}
