<?php

namespace App\Filament\Resources\Licencias\RelationManagers;

use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\User;
use App\Rules\LicenciaConCupoDisponible;
use App\Rules\NotTrashed;
use App\Services\Licencias\GestionarCupoLicencia;
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

class LicenciasUsuariosRelationManager extends RelationManager
{
    protected static string $relationship = 'licenciasUsuarios';

    protected static ?string $title = 'Licencias de usuarios';

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
                        modifyRuleUsing: function (Unique $rule, Get $get, ?LicenciaUsuario $record): Unique {
                            return $rule
                                ->where('licencia_id', $this->getOwnerRecord()->getKey())
                                ->ignore($record);
                        },
                    ),
                DatePicker::make('fecha')
                    ->label('Fecha de solicitud')
                    ->default(now()),
                Toggle::make('estado')
                    ->label('Activa')
                    ->default(true)
                    ->required()
                    ->rule(fn (Get $get, ?LicenciaUsuario $record): LicenciaConCupoDisponible => new LicenciaConCupoDisponible(
                        assignment: $record,
                        consumes: (bool) $get('estado'),
                        licencia: $this->ownerLicencia(),
                    )),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha')
                    ->label('Fecha de solicitud')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('estado')
                    ->label('Activa')
                    ->boolean()
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
                        '1' => 'Activa',
                        '0' => 'Inactiva',
                    ]),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar licencia')
                    ->disabled(fn (): bool => ! $this->ownerLicencia()->tieneCupoDisponible())
                    ->tooltip(fn (): ?string => $this->ownerLicencia()->tieneCupoDisponible()
                        ? null
                        : 'Cupo agotado. Desasigne un usuario o aumente el total.')
                    ->before(function (array $data): void {
                        if (! ($data['estado'] ?? false)) {
                            return;
                        }

                        app(GestionarCupoLicencia::class)->haltarSiNoPuedeConsumir($this->ownerLicencia());
                    })
                    ->successNotificationTitle('Asignacion registrada correctamente'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (LicenciaUsuario $record): bool => ! $record->trashed())
                    ->before(function (LicenciaUsuario $record, array $data): void {
                        if (! ($data['estado'] ?? false)) {
                            return;
                        }

                        app(GestionarCupoLicencia::class)->haltarSiNoPuedeConsumir($this->ownerLicencia(), $record);
                    }),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (LicenciaUsuario $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Asignacion eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (LicenciaUsuario $record): bool => $record->trashed())
                    ->successNotificationTitle('Asignacion eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (LicenciaUsuario $record): bool => $record->trashed())
                    ->before(fn (LicenciaUsuario $record) => GestionarCupoLicencia::haltarRestoreDeAsignacion($record))
                    ->successNotificationTitle('Asignacion restaurada correctamente'),
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
            ])
            ->poll('5s');
    }

    private function ownerLicencia(): Licencia
    {
        /** @var Licencia $licencia */
        $licencia = $this->getOwnerRecord();

        return $licencia;
    }
}
