<?php

namespace App\Filament\Resources\Licencias\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\Licencia;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class LicenciaInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos de la licencia')
                    ->description('Stock y estado de la licencia de software.')
                    ->icon(Heroicon::OutlinedKey)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('nombre_licencia')
                            ->label('Nombre de la licencia')
                            ->icon(Heroicon::OutlinedKey)
                            ->weight(FontWeight::Bold)
                            ->placeholder('—'),
                        TextEntry::make('categoria')
                            ->label('Categoría')
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('total_licencias')
                            ->label('Total disponibles')
                            ->numeric(),
                        TextEntry::make('asignadas')
                            ->label('Asignadas')
                            ->state(fn (Licencia $record): int => $record->asignadas)
                            ->color(fn (Licencia $record): string => $record->asignadas >= $record->total_licencias
                                ? 'danger'
                                : 'success'),
                        IconEntry::make('estado')
                            ->label('Activa')
                            ->boolean(),
                        TextEntry::make('descripcion')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(withDeletedAt: false),
            ]);
    }
}
