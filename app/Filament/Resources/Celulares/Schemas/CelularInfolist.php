<?php

namespace App\Filament\Resources\Celulares\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class CelularInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del celular')
                    ->description('Identificación y especificaciones del dispositivo.')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('marca')
                            ->label('Marca')
                            ->weight(FontWeight::Bold)
                            ->placeholder('—'),
                        TextEntry::make('modelo')
                            ->label('Modelo')
                            ->placeholder('—'),
                        TextEntry::make('imei')
                            ->label('IMEI')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('fecha_compra')
                            ->label('Fecha de compra')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('precio')
                            ->label('Precio')
                            ->numeric()
                            ->placeholder('—'),
                        TextEntry::make('color')
                            ->label('Color')
                            ->placeholder('—'),
                    ]),
                Section::make('Especificaciones')
                    ->icon(Heroicon::OutlinedCpuChip)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('procesador')
                            ->label('Procesador')
                            ->placeholder('—'),
                        TextEntry::make('disco')
                            ->label('Almacenamiento')
                            ->placeholder('—'),
                        TextEntry::make('ram')
                            ->label('Memoria RAM')
                            ->placeholder('—'),
                        TextEntry::make('bateria')
                            ->label('Batería')
                            ->placeholder('—'),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
