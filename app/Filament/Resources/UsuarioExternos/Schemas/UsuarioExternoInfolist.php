<?php

namespace App\Filament\Resources\UsuarioExternos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class UsuarioExternoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del usuario externo')
                    ->description('Persona externa relacionada con notas de recepción o salida de equipos.')
                    ->icon(Heroicon::OutlinedUser)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('nombre')
                            ->label('Nombre')
                            ->icon(Heroicon::OutlinedUser)
                            ->weight(FontWeight::Bold)
                            ->placeholder('—'),
                        TextEntry::make('carnet')
                            ->label('Carnet')
                            ->icon(Heroicon::OutlinedIdentification)
                            ->placeholder('—'),
                        TextEntry::make('empresa')
                            ->label('Empresa')
                            ->icon(Heroicon::OutlinedBuildingOffice)
                            ->placeholder('—'),
                        TextEntry::make('cargo')
                            ->label('Cargo')
                            ->icon(Heroicon::OutlinedBriefcase)
                            ->placeholder('—'),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
