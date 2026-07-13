<?php

namespace App\Filament\Resources\Software\RelationManagers;

use App\Filament\Actions\AsignarSoftwarePorDefectoAction;
use App\Models\SoftwareUsuario;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class UsuariosRelationManager extends RelationManager
{
    protected static string $relationship = 'usuarios';

    protected static ?string $title = 'Software de usuarios';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rule(new NotTrashed(User::class))
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get, ?SoftwareUsuario $record): Unique {
                            return $rule
                                ->where('id_software', $this->ownerRecord->getKey())
                                ->ignore($record);
                        },
                    ),
                Toggle::make('estado')
                    ->label('Activo')
                    ->default(false)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ]),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar usuario')
                    ->successNotificationTitle('Asignacion registrada correctamente'),
                AsignarSoftwarePorDefectoAction::make($this),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (SoftwareUsuario $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (SoftwareUsuario $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignacion eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (SoftwareUsuario $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignacion eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (SoftwareUsuario $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignacion restaurada correctamente'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados'),
                ]),
            ])
            ->poll('5s');
    }
}
