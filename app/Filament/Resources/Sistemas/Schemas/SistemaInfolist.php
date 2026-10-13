<?php

namespace App\Filament\Resources\Sistemas\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class SistemaInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del sistema')
                    ->description('Sistema institucional al que se pueden otorgar accesos.')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('nombre_sistema')
                            ->label('Nombre del sistema')
                            ->icon(Heroicon::OutlinedCube)
                            ->weight(FontWeight::Bold)
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
