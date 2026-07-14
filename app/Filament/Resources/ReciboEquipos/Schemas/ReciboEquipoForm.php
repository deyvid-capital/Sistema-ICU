<?php

namespace App\Filament\Resources\ReciboEquipos\Schemas;

use App\Models\Equipo;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReciboEquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Recibo de equipo')
                    ->description('Registra el recibo asociado a un equipo y, si aplica, el enlace al documento.')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('equipo_id')
                                    ->label('Equipo')
                                    ->relationship('equipo', 'service_tag')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Equipo $record): string => trim("{$record->service_tag} — {$record->nombre_equipo}")
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(Equipo::class)),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required(),
                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
