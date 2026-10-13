<?php

namespace App\Filament\Resources\AsignacionCargadors\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\AsignacionCargador;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AsignacionCargadorInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Asignación de cargador')
                    ->description('Cargador vinculado a un equipo y su vigencia.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('cargador.serial')
                            ->label('Cargador')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('equipo.service_tag')
                            ->label('Service Tag')
                            ->icon(Heroicon::OutlinedComputerDesktop)
                            ->placeholder('—'),
                        TextEntry::make('equipo.nombre_equipo')
                            ->label('Nombre del equipo')
                            ->placeholder('—'),
                        TextEntry::make('service_tag_origen')
                            ->label('Equipo origen')
                            ->placeholder('—')
                            ->visible(fn (AsignacionCargador $record): bool => filled($record->service_tag_origen)),
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
