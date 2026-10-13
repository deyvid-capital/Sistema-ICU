<?php

namespace App\Filament\Resources\CargadorCels\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class CargadorCelInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del cargador de celular')
                    ->description('Cargador asociado a dispositivos móviles.')
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
                        TextEntry::make('modelo')
                            ->label('Modelo')
                            ->placeholder('—'),
                        TextEntry::make('observacion')
                            ->label('Observación')
                            ->html()
                            ->placeholder('Sin observación')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
