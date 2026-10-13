<?php

namespace App\Filament\Resources\Software\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class SoftwareInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del software')
                    ->description('Catálogo de software autorizado en la organización.')
                    ->icon(Heroicon::OutlinedCube)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('nombre_software')
                            ->label('Nombre del software')
                            ->icon(Heroicon::OutlinedCube)
                            ->weight(FontWeight::Bold)
                            ->placeholder('—'),
                        TextEntry::make('categoria')
                            ->label('Categoría')
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        IconEntry::make('estado')
                            ->label('Activo')
                            ->boolean(),
                        TextEntry::make('fecha_autorizacion')
                            ->label('Fecha de autorización')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
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
