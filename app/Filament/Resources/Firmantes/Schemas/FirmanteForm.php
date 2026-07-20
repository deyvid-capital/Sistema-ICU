<?php

namespace App\Filament\Resources\Firmantes\Schemas;

use App\Filament\Resources\EntregaEquipos\RelationManagers\FirmantesRelationManager as EntregaFirmantesRelationManager;
use App\Filament\Resources\NotaRecepcionEquipos\RelationManagers\FirmantesRelationManager as NotaRecepcionFirmantesRelationManager;
use App\Filament\Resources\NotaSalidaEquipos\RelationManagers\FirmantesRelationManager as NotaSalidaFirmantesRelationManager;
use App\Filament\Resources\RecepcionEquipos\RelationManagers\FirmantesRelationManager as RecepcionFirmantesRelationManager;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FirmanteForm
{
    /**
     * @var list<class-string>
     */
    private const OWNER_RELATION_MANAGERS = [
        EntregaFirmantesRelationManager::class,
        RecepcionFirmantesRelationManager::class,
        NotaSalidaFirmantesRelationManager::class,
        NotaRecepcionFirmantesRelationManager::class,
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Firmante')
                    ->description('Usuario interno que firma el documento.')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('usuario_id')
                                    ->label('Usuario')
                                    ->relationship('usuario', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(User::class)),
                                DatePicker::make('fecha')
                                    ->label('Fecha de firma')
                                    ->default(now())
                                    ->required(),
                                TextInput::make('documento_type')
                                    ->label('Tipo de documento')
                                    ->required()
                                    ->hiddenOn(self::OWNER_RELATION_MANAGERS),
                                TextInput::make('documento_id')
                                    ->label('ID del documento')
                                    ->required()
                                    ->numeric()
                                    ->hiddenOn(self::OWNER_RELATION_MANAGERS),
                            ]),
                    ]),
            ]);
    }
}
