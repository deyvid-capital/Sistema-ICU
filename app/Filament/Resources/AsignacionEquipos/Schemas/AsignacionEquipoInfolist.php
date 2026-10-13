<?php

namespace App\Filament\Resources\AsignacionEquipos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AsignacionEquipoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Asignación de equipo')
                    ->description('Equipo entregado a un colaborador y su vigencia.')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('equipo.service_tag')
                            ->label('Service Tag')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('equipo.nombre_equipo')
                            ->label('Nombre del equipo')
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
                            ->placeholder('Vigente'),
                        TextEntry::make('motivo')
                            ->label('Motivo')
                            ->placeholder('Sin motivo')
                            ->columnSpanFull(),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->html()
                            ->placeholder('Sin observaciones')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
