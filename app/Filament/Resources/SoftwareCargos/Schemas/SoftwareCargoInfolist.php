<?php

namespace App\Filament\Resources\SoftwareCargos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SoftwareCargoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Software autorizado por cargo')
                    ->description('Relación entre un software y el cargo que lo autoriza.')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('software.nombre_software')
                            ->label('Software')
                            ->icon(Heroicon::OutlinedCube)
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('cargo.cargo')
                            ->label('Cargo')
                            ->icon(Heroicon::OutlinedBriefcase)
                            ->badge()
                            ->color('gray')
                            ->placeholder('—'),
                    ]),
                static::registroInfolistSection(withDeletedAt: false),
            ]);
    }
}
