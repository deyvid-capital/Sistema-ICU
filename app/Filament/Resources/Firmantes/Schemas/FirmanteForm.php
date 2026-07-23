<?php

namespace App\Filament\Resources\Firmantes\Schemas;

use App\Filament\Resources\EntregaCelulars\RelationManagers\FirmantesRelationManager as EntregaCelularFirmantesRelationManager;
use App\Filament\Resources\EntregaEquipos\RelationManagers\FirmantesRelationManager as EntregaFirmantesRelationManager;
use App\Filament\Resources\NotaRecepcionEquipos\RelationManagers\FirmantesRelationManager as NotaRecepcionFirmantesRelationManager;
use App\Filament\Resources\NotaSalidaEquipos\RelationManagers\FirmantesRelationManager as NotaSalidaFirmantesRelationManager;
use App\Filament\Resources\RecepcionCelulars\RelationManagers\FirmantesRelationManager as RecepcionCelularFirmantesRelationManager;
use App\Filament\Resources\RecepcionEquipos\RelationManagers\FirmantesRelationManager as RecepcionFirmantesRelationManager;
use App\Models\User;
use App\Rules\NotTrashed;
use App\Support\FirmanteDocumentoConfig;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

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
        EntregaCelularFirmantesRelationManager::class,
        RecepcionCelularFirmantesRelationManager::class,
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Firmante')
                    ->description('Usuario interno que firma el documento. La posición depende del tipo de formulario PDF.')
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
                                Select::make('posicion')
                                    ->label('Posición')
                                    ->options(fn (Get $get, Component $livewire): array => FirmanteDocumentoConfig::optionsFor(
                                        self::resolveDocumentoType($get, $livewire),
                                    ))
                                    ->required()
                                    ->native(false)
                                    ->helperText(fn (Get $get, Component $livewire): string => sprintf(
                                        'Máximo %d firmante(s) según el documento.',
                                        FirmanteDocumentoConfig::maxFirmantes(self::resolveDocumentoType($get, $livewire)),
                                    )),
                                TextInput::make('documento_type')
                                    ->label('Tipo de documento')
                                    ->required()
                                    ->live()
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

    private static function resolveDocumentoType(Get $get, Component $livewire): ?string
    {
        foreach (self::OWNER_RELATION_MANAGERS as $relationManager) {
            if ($livewire instanceof $relationManager) {
                /** @var Model $owner */
                $owner = $livewire->getOwnerRecord();

                return $owner::class;
            }
        }

        $type = $get('documento_type');

        return is_string($type) && filled($type) ? $type : null;
    }
}
