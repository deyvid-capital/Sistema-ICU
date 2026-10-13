<?php

namespace App\Filament\Resources\LicenciasUsuarios\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class LicenciaUsuarioInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Asignación de licencia')
                    ->description('Licencia otorgada a un colaborador.')
                    ->icon(Heroicon::OutlinedKey)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('licencia.nombre_licencia')
                            ->label('Licencia')
                            ->icon(Heroicon::OutlinedKey)
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('fecha')
                            ->label('Fecha de asignación')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        IconEntry::make('estado')
                            ->label('Activa')
                            ->boolean(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
