<?php

namespace App\Filament\Resources\Celulares\Schemas;

use App\Models\Celular;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CelularInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('ID'),
                TextEntry::make('marca')->label('Marca')->placeholder('—'),
                TextEntry::make('modelo')->label('Modelo')->placeholder('—'),
                TextEntry::make('imei')->label('IMEI')->placeholder('—'),
                TextEntry::make('fecha_compra')->label('Fecha de compra')->date('d/m/Y')->placeholder('—'),
                TextEntry::make('precio')->label('Precio')->numeric()->placeholder('—'),
                TextEntry::make('procesador')->label('Procesador')->placeholder('—'),
                TextEntry::make('disco')->label('Disco')->placeholder('—'),
                TextEntry::make('ram')->label('RAM')->placeholder('—'),
                TextEntry::make('bateria')->label('Batería')->placeholder('—'),
                TextEntry::make('color')->label('Color')->placeholder('—'),
                TextEntry::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->visible(fn (Celular $record): bool => $record->trashed()),
            ]);
    }
}
