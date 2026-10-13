<?php

namespace App\Filament\Resources\AccesoInternets\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Filament\Resources\Concerns\ShowsUsuarioFortitoken;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AccesoInternetInfolist
{
    use HasRegistroInfolistSection;
    use ShowsUsuarioFortitoken;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Acceso a red / Internet')
                    ->description('Usuario de red, nivel de acceso y FortiToken del colaborador.')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        static::fortitokenInfolistEntry(),
                        TextEntry::make('usuario_red')
                            ->label('Usuario de red')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('nivel_acceso')
                            ->label('Nivel de acceso')
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        IconEntry::make('estado')
                            ->label('Activo')
                            ->boolean(),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Sin observaciones')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
