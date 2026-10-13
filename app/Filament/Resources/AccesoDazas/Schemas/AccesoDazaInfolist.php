<?php

namespace App\Filament\Resources\AccesoDazas\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\AccesoDaza;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AccesoDazaInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Acceso DAZA')
                    ->description('Credenciales y alcance del colaborador en DAZA.')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('usuario_daza')
                            ->label('Usuario DAZA')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->placeholder('—'),
                        TextEntry::make('nivel_acceso')
                            ->label('Nivel de acceso')
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('fondo.nombre_completo')
                            ->label('Fondo')
                            ->formatStateUsing(fn (?string $state, AccesoDaza $record): string => $state ?? $record->fondo?->codigo ?? '—')
                            ->placeholder('—'),
                        TextEntry::make('ambiente')
                            ->label('Ambiente')
                            ->placeholder('—'),
                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        IconEntry::make('estado')
                            ->label('Activo')
                            ->boolean(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
