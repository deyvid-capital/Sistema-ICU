<?php

namespace App\Filament\Resources\Firmantes\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FirmanteInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Firmante del documento')
                    ->description('Usuario interno que firma un formulario PDF.')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('fecha')
                            ->label('Fecha de firma')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('posicion')
                            ->label('Posición de firma')
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('documento_type')
                            ->label('Tipo de documento')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? class_basename($state) : '—')
                            ->placeholder('—'),
                        TextEntry::make('documento_id')
                            ->label('Documento')
                            ->numeric()
                            ->placeholder('—'),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
