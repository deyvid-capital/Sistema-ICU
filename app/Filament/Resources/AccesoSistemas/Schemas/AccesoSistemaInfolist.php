<?php

namespace App\Filament\Resources\AccesoSistemas\Schemas;

use App\Models\AccesoSistema;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AccesoSistemaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('sistema.nombre_sistema')
                    ->label('Sistema')
                    ->placeholder('—'),
                TextEntry::make('usuario_sistema')
                    ->label('Usuario del sistema')
                    ->placeholder('—'),
                TextEntry::make('nivel_acceso')
                    ->label('Nivel de acceso')
                    ->placeholder('—'),
                TextEntry::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextEntry::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                IconEntry::make('estado')
                    ->label('Activo')
                    ->boolean(),
                TextEntry::make('observaciones')
                    ->label('Observaciones')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextEntry::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn (AccesoSistema $record): bool => $record->trashed()),
            ]);
    }
}
