<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Movimiento;
use App\Models\User;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nombre')
                                    ->icon(Heroicon::OutlinedUser)
                                    ->weight('bold')
                                    ->size('lg')
                                    ->columnSpan(['md' => 2]),
                                TextEntry::make('movimientos.tipo')
                                    ->label('Estado actual')
                                    ->badge()
                                    ->state(fn (User $record): ?string => $record->movimientos->last()?->tipo)
                                    ->color(fn (User $record): string => $record->movimientos->last()?->tipo === 'Alta' ? 'success' : 'danger')
                                    ->placeholder('Sin movimientos'),
                            ]),
                    ]),

                Section::make('Datos personales')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('carnet')
                            ->label('Carnet')
                            ->icon(Heroicon::OutlinedIdentification)
                            ->placeholder('Sin carnet'),
                        TextEntry::make('area.area')
                            ->label('Área')
                            ->icon(Heroicon::OutlinedBuildingOffice2)
                            ->badge()
                            ->color('info')
                            ->placeholder('Sin área'),
                        TextEntry::make('cargo.cargo')
                            ->label('Cargo')
                            ->icon(Heroicon::OutlinedBriefcase)
                            ->badge()
                            ->color('gray')
                            ->placeholder('Sin cargo'),
                    ]),

                Section::make('Contacto')
                    ->icon(Heroicon::OutlinedAtSymbol)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('email')
                            ->label('Correo electrónico')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->copyable()
                            ->copyMessage('Correo copiado')
                            ->placeholder('—'),
                        TextEntry::make('telefono')
                            ->label('Teléfono')
                            ->icon(Heroicon::OutlinedPhone)
                            ->copyable()
                            ->copyMessage('Teléfono copiado')
                            ->placeholder('Sin teléfono'),
                    ]),

                Section::make('Historial de movimientos')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->description('Altas y bajas registradas para el usuario.')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('movimientos')
                            ->hiddenLabel()
                            ->state(fn (User $record) => $record->movimientos()->orderByDesc('fecha')->get())
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 3])
                                    ->schema([
                                        TextEntry::make('tipo')
                                            ->label('Tipo')
                                            ->badge()
                                            ->color(fn (Movimiento $record): string => $record->tipo === 'Alta' ? 'success' : 'danger'),
                                        TextEntry::make('fecha')
                                            ->label('Fecha')
                                            ->icon(Heroicon::OutlinedCalendarDays)
                                            ->date('d/m/Y'),
                                        TextEntry::make('cargo.cargo')
                                            ->label('Cargo')
                                            ->icon(Heroicon::OutlinedBriefcase)
                                            ->placeholder('—'),
                                    ]),
                            ]),
                        TextEntry::make('sin_movimientos')
                            ->hiddenLabel()
                            ->state('Este usuario no tiene movimientos registrados.')
                            ->color('gray')
                            ->visible(fn (User $record): bool => $record->movimientos->isEmpty()),
                    ]),

                Section::make('Información del registro')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('email_verified_at')
                            ->label('Correo verificado el')
                            ->icon(Heroicon::OutlinedCheckBadge)
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Sin verificar'),
                        TextEntry::make('created_at')
                            ->label('Registrado el')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('updated_at')
                            ->label('Actualizado el')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('deleted_at')
                            ->label('Eliminado el')
                            ->icon(Heroicon::OutlinedTrash)
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn (User $record): bool => $record->trashed())
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
