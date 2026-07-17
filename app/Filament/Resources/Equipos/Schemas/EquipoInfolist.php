<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Models\Equipo;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EquipoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('service_tag')
                    ->label('Service Tag'),
                TextEntry::make('nombre_equipo')
                    ->label('Nombre del equipo'),
                TextEntry::make('codigo_activo')
                    ->label('Código de activo')
                    ->placeholder('—'),
                TextEntry::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextEntry::make('observaciones')
                    ->label('Observaciones')
                    ->html()
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('modelo.nombre')
                    ->label('Modelo')
                    ->placeholder('—'),
                TextEntry::make('microprocesador.nombre_micro')
                    ->label('Microprocesador')
                    ->placeholder('—'),
                TextEntry::make('ram.tipo_ram')
                    ->label('Memoria RAM')
                    ->placeholder('—'),
                TextEntry::make('gpu.nombre_gpu')
                    ->label('Tarjeta de video')
                    ->placeholder('—'),
                TextEntry::make('disco_duro')
                    ->label('Disco duro')
                    ->placeholder('—'),
                TextEntry::make('proveedor')
                    ->label('Proveedor')
                    ->placeholder('—'),
                TextEntry::make('so')
                    ->label('Sistema operativo'),
                TextEntry::make('licencia_so')
                    ->label('Licencia SO')
                    ->placeholder('—'),
                TextEntry::make('inicio_garantia')
                    ->label('Inicio de garantía')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextEntry::make('venc_garantia')
                    ->label('Vencimiento de garantía')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                IconEntry::make('bloqueo_bios')
                    ->label('Bloqueo BIOS')
                    ->boolean(),
                IconEntry::make('etiquetado')
                    ->label('Etiquetado')
                    ->boolean(),
                IconEntry::make('usuario_admin')
                    ->label('Usuario administrador')
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
                    ->visible(fn (Equipo $record): bool => $record->trashed()),
            ]);
    }
}
