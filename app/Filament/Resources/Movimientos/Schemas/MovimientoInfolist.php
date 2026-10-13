<?php

namespace App\Filament\Resources\Movimientos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\Movimiento;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MovimientoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Movimiento de personal')
                    ->description('Alta o baja del colaborador en la organización.')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('tipo')
                            ->label('Tipo')
                            ->badge()
                            ->color(fn (Movimiento $record): string => $record->tipo === 'Alta' ? 'success' : 'danger'),
                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('cargo.cargo')
                            ->label('Cargo')
                            ->icon(Heroicon::OutlinedBriefcase)
                            ->placeholder('—'),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
