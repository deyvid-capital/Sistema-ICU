<?php

namespace App\Filament\Resources\SitioSharepoints\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class SitioSharepointInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Sitio SharePoint')
                    ->description('Sitio o biblioteca disponible para asignar accesos.')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('nombre_sitio')
                            ->label('Nombre del sitio')
                            ->icon(Heroicon::OutlinedBuildingLibrary)
                            ->weight(FontWeight::Bold)
                            ->placeholder('—'),
                        TextEntry::make('url')
                            ->label('URL')
                            ->icon(Heroicon::OutlinedLink)
                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab()
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('descripcion')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
                            ->columnSpanFull(),
                        IconEntry::make('estado')
                            ->label('Activo')
                            ->boolean(),
                    ]),
                static::registroInfolistSection(withDeletedAt: false),
            ]);
    }
}
