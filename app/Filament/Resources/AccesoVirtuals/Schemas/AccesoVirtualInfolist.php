<?php

namespace App\Filament\Resources\AccesoVirtuals\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Filament\Resources\Concerns\ShowsUsuarioFortitoken;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AccesoVirtualInfolist
{
    use HasRegistroInfolistSection;
    use ShowsUsuarioFortitoken;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Acceso a máquina virtual')
                    ->description('Máquina virtual, VPN asociada y FortiToken del colaborador.')
                    ->icon(Heroicon::OutlinedRectangleGroup)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        static::fortitokenInfolistEntry(),
                        TextEntry::make('virtual.maquina_virtual')
                            ->label('Máquina virtual')
                            ->icon(Heroicon::OutlinedServer)
                            ->placeholder('—'),
                        TextEntry::make('recursoVpn.nombre_vpn')
                            ->label('Recurso VPN')
                            ->icon(Heroicon::OutlinedLockClosed)
                            ->placeholder('Sin VPN'),
                        IconEntry::make('estado')
                            ->label('Activo')
                            ->boolean(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
