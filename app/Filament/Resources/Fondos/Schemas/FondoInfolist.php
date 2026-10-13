<?php

namespace App\Filament\Resources\Fondos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class FondoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del fondo')
                    ->description('Identificación y descripción del fondo DAZA.')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('codigo')
                            ->label('Código')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->weight(FontWeight::Bold)
                            ->badge()
                            ->color('info'),
                        TextEntry::make('nombre_completo')
                            ->label('Nombre completo')
                            ->placeholder('—'),
                        TextEntry::make('descripcion')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(withDeletedAt: false),
            ]);
    }
}
