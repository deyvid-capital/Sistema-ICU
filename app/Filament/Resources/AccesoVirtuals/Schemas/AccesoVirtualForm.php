<?php

namespace App\Filament\Resources\AccesoVirtuals\Schemas;

use App\Filament\Resources\Concerns\ShowsUsuarioFortitoken;
use App\Filament\Resources\Users\RelationManagers\AccesosVirtualRelationManager;
use App\Models\AccesoVirtual;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class AccesoVirtualForm
{
    use ShowsUsuarioFortitoken;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del acceso virtual')
                    ->description('Asigna una máquina virtual al colaborador. El recurso VPN es opcional.')
                    ->icon(Heroicon::OutlinedAtSymbol)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('usuario_id')
                                    ->label('Usuario')
                                    ->relationship('usuario', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->rule(new NotTrashed(User::class))
                                    ->hiddenOn(AccesosVirtualRelationManager::class),
                                static::fortitokenFormPlaceholder(),
                                Select::make('virtual_id')
                                    ->label('Máquina virtual')
                                    ->relationship('virtual', 'maquina_virtual')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule, Get $get, ?AccesoVirtual $record): Unique {
                                            $usuarioId = $get('usuario_id') ?? $record?->usuario_id;

                                            $rule = $rule->where('usuario_id', $usuarioId);

                                            $vpnId = $get('vpn_id');

                                            if (blank($vpnId)) {
                                                return $rule->whereNull('vpn_id')->ignore($record);
                                            }

                                            return $rule->where('vpn_id', $vpnId)->ignore($record);
                                        },
                                    ),
                                Select::make('vpn_id')
                                    ->label('Recurso VPN')
                                    ->relationship('recursoVpn', 'nombre_vpn')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->live()
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule, Get $get, ?AccesoVirtual $record): Unique {
                                            $usuarioId = $get('usuario_id') ?? $record?->usuario_id;

                                            $rule = $rule
                                                ->where('usuario_id', $usuarioId)
                                                ->where('virtual_id', $get('virtual_id'));

                                            $vpnId = $get('vpn_id');

                                            if (blank($vpnId)) {
                                                return $rule->whereNull('vpn_id')->ignore($record);
                                            }

                                            return $rule->where('vpn_id', $vpnId)->ignore($record);
                                        },
                                    ),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
