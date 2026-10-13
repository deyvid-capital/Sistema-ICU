<?php

namespace App\Filament\Resources\AzureAds\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AzureAdInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Azure AD')
                    ->description('Estado de sincronización e identidad del colaborador en Azure AD.')
                    ->icon(Heroicon::OutlinedCloud)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->placeholder('—'),
                        TextEntry::make('iniciales')
                            ->label('Iniciales')
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('sincronizacion')
                            ->label('Sincronización')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'Nube' => 'success',
                                'Local / Nube' => 'warning',
                                'Local / No sincronizado' => 'danger',
                                default => 'gray',
                            })
                            ->placeholder('—'),
                        IconEntry::make('mfa')
                            ->label('MFA')
                            ->boolean(),
                        IconEntry::make('estado')
                            ->label('Activo')
                            ->boolean(),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
