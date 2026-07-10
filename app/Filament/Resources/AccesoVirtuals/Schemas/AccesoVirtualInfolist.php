<?php

namespace App\Filament\Resources\AccesoVirtuals\Schemas;

use App\Models\AccesoVirtual;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AccesoVirtualInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('accesoInternet.usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('accesoInternet.usuario_red')
                    ->label('Usuario de red')
                    ->placeholder('—'),
                TextEntry::make('virtual.maquina_virtual')
                    ->label('Máquina virtual')
                    ->placeholder('—'),
                TextEntry::make('recursoVpn.nombre_vpn')
                    ->label('Recurso VPN')
                    ->placeholder('—'),
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
