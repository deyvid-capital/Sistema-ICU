<?php

namespace App\Filament\Resources\NotaSalidaEquipos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class NotaSalidaEquipoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Nota de salida de equipo')
                    ->description('Nota asociada a la salida de un equipo hacia un usuario externo.')
                    ->icon(Heroicon::OutlinedArrowRightOnRectangle)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('equipo.service_tag')
                            ->label('Equipo')
                            ->icon(Heroicon::OutlinedComputerDesktop)
                            ->placeholder('—'),
                        TextEntry::make('usuarioExterno.nombre')
                            ->label('Usuario externo')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('numero')
                            ->label('Número')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->placeholder('—'),
                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('nota')
                            ->label('Nota')
                            ->placeholder('Sin nota')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
