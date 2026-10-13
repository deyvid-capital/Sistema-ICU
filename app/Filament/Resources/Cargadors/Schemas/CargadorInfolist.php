<?php

namespace App\Filament\Resources\Cargadors\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class CargadorInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del cargador')
                    ->description('Cargador de equipo de cómputo.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('serial')
                            ->label('Serial')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->weight(FontWeight::Bold)
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('marca')
                            ->label('Marca')
                            ->placeholder('—'),
                        TextEntry::make('modelo')
                            ->label('Modelo')
                            ->placeholder('—'),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->placeholder('—'),
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
