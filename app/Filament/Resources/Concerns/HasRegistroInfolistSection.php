<?php

namespace App\Filament\Resources\Concerns;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasRegistroInfolistSection
{
    /**
     * Sección colapsable con fechas de auditoría del registro.
     */
    protected static function registroInfolistSection(bool $withDeletedAt = true): Section
    {
        $schema = [
            TextEntry::make('created_at')
                ->label('Creado el')
                ->dateTime('d/m/Y H:i')
                ->placeholder('—'),
            TextEntry::make('updated_at')
                ->label('Actualizado el')
                ->dateTime('d/m/Y H:i')
                ->placeholder('—'),
        ];

        if ($withDeletedAt) {
            $schema[] = TextEntry::make('deleted_at')
                ->label('Eliminado el')
                ->dateTime('d/m/Y H:i')
                ->placeholder('—')
                ->visible(fn (Model $record): bool => static::recordIsSoftDeleted($record));
        }

        return Section::make('Registro')
            ->icon(Heroicon::OutlinedClock)
            ->columnSpanFull()
            ->columns(['default' => 1, 'md' => 3])
            ->collapsed()
            ->schema($schema);
    }

    protected static function recordIsSoftDeleted(Model $record): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($record), true)
            && $record->trashed();
    }
}
