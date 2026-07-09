<?php

namespace App\Filament\Resources\LicenciasUsuarios\Schemas;

use App\Models\LicenciaUsuario;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LicenciaUsuarioInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('usuario.name')
                    ->label('Usuario')
                    ->placeholder('—'),
                TextEntry::make('licencia.nombre_licencia')
                    ->label('Licencia')
                    ->placeholder('—'),
                TextEntry::make('fecha')
                    ->label('Fecha de solicitud')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                IconEntry::make('estado')
                    ->label('Activa')
                    ->boolean(),
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
                    ->visible(fn (LicenciaUsuario $record): bool => $record->trashed()),
            ]);
    }
}
