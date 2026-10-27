<?php

namespace App\Filament\Resources\SolicitudesFirma;

use App\Filament\Resources\SolicitudesFirma\Pages\ConfigurarPosicionesFirma;
use App\Filament\Resources\SolicitudesFirma\Pages\CreateSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\Pages\ListSolicitudesFirma;
use App\Filament\Resources\SolicitudesFirma\Pages\ViewSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\Schemas\SolicitudFirmaInfolist;
use App\Filament\Resources\SolicitudesFirma\Tables\SolicitudesFirmaTable;
use App\Models\SolicitudFirma;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pantallas 2 y 3 del módulo de firma: preparar envíos y verlos.
 * Ver Docs/firma-digital/05-pantallas.md.
 */
class SolicitudFirmaResource extends Resource
{
    protected static ?string $model = SolicitudFirma::class;

    /**
     * Mismo URL que tenía cuando vivía en un cluster (/admin/firmas/solicitudes).
     */
    protected static ?string $slug = 'firmas/solicitudes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $recordTitleAttribute = 'codigo';

    protected static ?string $navigationLabel = 'Firma digital';

    protected static ?string $modelLabel = 'envío a firmar';

    protected static ?string $pluralModelLabel = 'envíos a firmar';

    protected static ?int $navigationSort = 7;

    /**
     * Filament pone mayúscula a cada palabra ("Envíos A Firmar"); aquí solo a la primera.
     */
    public static function getTitleCaseModelLabel(): string
    {
        return 'Envío a firmar';
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'Envíos a firmar';
    }

    public static function infolist(Schema $schema): Schema
    {
        return SolicitudFirmaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SolicitudesFirmaTable::configure($table);
    }

    /**
     * Sin el permiso general, cada usuario ve solo los envíos que creó o que debe firmar.
     *
     * @return Builder<SolicitudFirma>
     */
    public static function getEloquentQuery(): Builder
    {
        $usuario = auth()->user();

        return parent::getEloquentQuery()
            ->with('creador')
            ->withCount(['documentos', 'firmantes'])
            ->when(
                $usuario instanceof User && ! $usuario->can('View:SolicitudFirma'),
                fn (Builder $query) => $query->deUsuario($usuario),
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSolicitudesFirma::route('/'),
            'create' => CreateSolicitudFirma::route('/crear'),
            'view' => ViewSolicitudFirma::route('/{record}'),
            'posiciones' => ConfigurarPosicionesFirma::route('/{record}/posiciones'),
        ];
    }
}
