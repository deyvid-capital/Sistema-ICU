<?php

namespace App\Filament\Resources\AccUdCompartidas\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AccUdCompartidaInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Acceso a unidad compartida')
                    ->description('Permiso del colaborador sobre una unidad de red compartida.')
                    ->icon(Heroicon::OutlinedFolderOpen)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('unidad_compartida')
                            ->label('Unidad compartida')
                            ->icon(Heroicon::OutlinedServerStack)
                            ->placeholder('—'),
                        IconEntry::make('estado')
                            ->label('Activo')
                            ->boolean(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
