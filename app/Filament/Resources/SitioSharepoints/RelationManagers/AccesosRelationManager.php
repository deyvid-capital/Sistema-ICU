<?php

namespace App\Filament\Resources\SitioSharepoints\RelationManagers;

use App\Models\AccesoSharepoint;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class AccesosRelationManager extends RelationManager
{
    protected static string $relationship = 'accesos';

    protected static ?string $title = 'Accesos SharePoint';

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
                        modifyRuleUsing: function (Unique $rule, Get $get, ?AccesoSharepoint $record): Unique {
                            return $rule
                                ->where('sitio_id', $this->ownerRecord->getKey())
                                ->ignore($record);
                        },
                    ),
                Toggle::make('estado')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
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
                    ->label('Registrado')
                    ->since()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo acceso')
                    ->successNotificationTitle('Acceso SharePoint registrado correctamente'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (AccesoSharepoint $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AccesoSharepoint $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Acceso eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AccesoSharepoint $record): bool => $record->trashed())
                    ->successNotificationTitle('Acceso eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AccesoSharepoint $record): bool => $record->trashed())
                    ->successNotificationTitle('Acceso restaurado correctamente'),
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
            ]);
    }
}
