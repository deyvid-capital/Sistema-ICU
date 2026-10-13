<?php

namespace App\Filament\Resources\SoftwareUsuarios\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SoftwareUsuarioInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Software asignado')
                    ->description('Software instalado o autorizado para el colaborador.')
                    ->icon(Heroicon::OutlinedCube)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('software.nombre_software')
                            ->label('Software')
                            ->icon(Heroicon::OutlinedCube)
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        IconEntry::make('estado')
                            ->label('Activo')
                            ->boolean(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
