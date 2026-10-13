<?php

namespace App\Filament\Resources\MantenimientoEquipos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MantenimientoEquipoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Soporte / mantenimiento')
                    ->description('Datos del equipo y la intervención realizada.')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('equipo.service_tag')
                            ->label('Service Tag')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->badge()
                            ->color('primary')
                            ->placeholder('—'),
                        TextEntry::make('equipo.nombre_equipo')
                            ->label('Nombre del equipo')
                            ->placeholder('—'),
                        TextEntry::make('fecha_soporte')
                            ->label('Fecha de soporte')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('Sin fecha'),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Sin observaciones')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),
                Section::make('Capturas')
                    ->description('Imágenes del estado antes y después del soporte.')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('cap_antes')
                            ->label('Captura antes')
                            ->collection('cap_antes')
                            ->conversion('thumb')
                            ->placeholder('Sin imagen')
                            ->columnSpan(1),
                        SpatieMediaLibraryImageEntry::make('cap_despues')
                            ->label('Captura después')
                            ->collection('cap_despues')
                            ->conversion('thumb')
                            ->placeholder('Sin imagen')
                            ->columnSpan(1),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
