<?php

namespace App\Filament\Resources\SoftwareAreas\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SoftwareAreaInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Software autorizado por área')
                    ->description('Relación entre un software y el área que lo autoriza.')
                    ->icon(Heroicon::OutlinedBuildingOffice2)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('software.nombre_software')
                            ->label('Software')
                            ->icon(Heroicon::OutlinedCube)
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('area.area')
                            ->label('Área')
                            ->icon(Heroicon::OutlinedBuildingOffice)
                            ->badge()
                            ->color('gray')
                            ->placeholder('—'),
                    ]),
                static::registroInfolistSection(withDeletedAt: false),
            ]);
    }
}
