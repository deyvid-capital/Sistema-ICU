<?php

namespace App\Filament\Resources\Software\RelationManagers;

use App\Models\SoftwareCargo;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class CargosRelationManager extends RelationManager
{
    protected static string $relationship = 'cargos';

    protected static ?string $title = 'Software por cargo';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cargo_id')
                    ->label('Cargo')
                    ->relationship('cargo', 'cargo')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get, ?SoftwareCargo $record): Unique {
                            return $rule
                                ->where('software_id', $this->ownerRecord->getKey())
                                ->ignore($record);
                        },
                    ),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('cargo.cargo')
                    ->label('Cargo')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('cargo_id')
                    ->label('Cargo')
                    ->relationship('cargo', 'cargo')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar cargo')
                    ->successNotificationTitle('Relacion agregada correctamente'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->successNotificationTitle('Relacion eliminada correctamente'),
            ]);
    }
}
