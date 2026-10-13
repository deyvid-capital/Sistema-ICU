<?php

namespace App\Filament\Resources\AccesoSistemas\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AccesoSistemaInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Acceso a sistema')
                    ->description('Credenciales y vigencia del acceso a un sistema institucional.')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('sistema.nombre_sistema')
                            ->label('Sistema')
                            ->icon(Heroicon::OutlinedCube)
                            ->placeholder('—'),
                        TextEntry::make('usuario_sistema')
                            ->label('Usuario del sistema')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->placeholder('—'),
                        TextEntry::make('nivel_acceso')
                            ->label('Nivel de acceso')
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('fecha_inicio')
                            ->label('Fecha de inicio')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('fecha_fin')
                            ->label('Fecha de fin')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('Sin vencimiento'),
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
