<?php

namespace App\Filament\Resources\CategoriasSoftware\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\CategoriaSoftware;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class CategoriaSoftwareInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos de la categoría')
                    ->description('Agrupa el software para asignarlo en bloque y ubicarlo en los formularios.')
                    ->icon(Heroicon::OutlinedTag)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('nombre')
                            ->label('Nombre')
                            ->badge()
                            ->color(fn (CategoriaSoftware $record): string|array => $record->color())
                            ->weight(FontWeight::Bold),
                        TextEntry::make('codigo')
                            ->label('Código')
                            ->fontFamily('mono'),
                        TextEntry::make('software')
                            ->label('Software de la categoría')
                            ->state(fn (CategoriaSoftware $record): array => $record->software()
                                ->orderBy('nombre_software')
                                ->pluck('nombre_software')
                                ->all())
                            ->badge()
                            ->color('gray')
                            ->placeholder('Sin software en esta categoría.')
                            ->columnSpanFull(),
                        TextEntry::make('descripcion')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
                            ->columnSpanFull(),
                    ]),
                static::registroInfolistSection(withDeletedAt: false),
            ]);
    }
}
