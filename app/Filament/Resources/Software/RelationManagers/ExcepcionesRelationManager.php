<?php

namespace App\Filament\Resources\Software\RelationManagers;

use App\Models\SoftwareExc;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExcepcionesRelationManager extends RelationManager
{
    protected static string $relationship = 'excepciones';

    protected static ?string $title = 'Excepciones de software';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('tipo_excepcion')
                    ->label('Tipo de excepcion')
                    ->options([
                        'Temporal' => 'Temporal',
                        'Permanente' => 'Permanente',
                        'Emergencia' => 'Emergencia',
                    ]),
                Textarea::make('motivo')
                    ->label('Motivo')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->required(),
                DatePicker::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->afterOrEqual('fecha_inicio'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->defaultSort('fecha_inicio', 'desc')
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo_excepcion')
                    ->label('Tipo')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('fecha_inicio')
                    ->label('Fecha inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fecha_fin')
                    ->label('Fecha fin')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo_excepcion')
                    ->label('Tipo de excepcion')
                    ->options([
                        'Temporal' => 'Temporal',
                        'Permanente' => 'Permanente',
                        'Emergencia' => 'Emergencia',
                    ]),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva excepcion')
                    ->successNotificationTitle('Excepcion registrada correctamente'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (SoftwareExc $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (SoftwareExc $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Excepcion eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (SoftwareExc $record): bool => $record->trashed())
                    ->successNotificationTitle('Excepcion eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (SoftwareExc $record): bool => $record->trashed())
                    ->successNotificationTitle('Excepcion restaurada correctamente'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionadas'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionadas'),
                ]),
            ]);
    }
}
