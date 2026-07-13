<?php

namespace App\Filament\Resources\AccesoVirtuals\Schemas;

use App\Models\AccesoInternet;
use App\Models\AccesoVirtual;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class AccesoVirtualForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del acceso virtual')
                    ->description('Agrega a quién se le va a otorgar acceso a la VPN, y qué nivel de acceso tiene.')
                    ->icon(Heroicon::OutlinedAtSymbol)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('internet_id')
                                    ->label('Acceso a Internet')
                                    ->relationship(
                                        'accesoInternet',
                                        'usuario_red',
                                        modifyQueryUsing: fn ($query) => $query->with('usuario'),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (AccesoInternet $record): string => trim("{$record->usuario?->name} — {$record->usuario_red}"))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                Select::make('virtual_id')
                                    ->label('Máquina virtual')
                                    ->relationship('virtual', 'maquina_virtual')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule, Get $get, ?AccesoVirtual $record): Unique {
                                            return $rule
                                                ->where('internet_id', $get('internet_id'))
                                                ->where('vpn_id', $get('vpn_id'))
                                                ->ignore($record);
                                        },
                                    ),
                                Select::make('vpn_id')
                                    ->label('Recurso VPN')
                                    ->relationship('recursoVpn', 'nombre_vpn')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                            ]),
                    ]),
            ]);
    }
}
