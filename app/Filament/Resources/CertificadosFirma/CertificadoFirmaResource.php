<?php

namespace App\Filament\Resources\CertificadosFirma;

use App\Filament\Clusters\Configuracion\ConfiguracionCluster;
use App\Filament\Resources\CertificadosFirma\Pages\ListCertificadosFirma;
use App\Filament\Resources\CertificadosFirma\Tables\CertificadosFirmaTable;
use App\Models\CertificadoFirma;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pantalla 1 del módulo de firma digital: el admin asigna, renueva y revoca los
 * .p12 de todos los usuarios (incluido el suyo). La activación la hace cada titular
 * desde Mi firma digital. Ver Docs/firma-digital/05-pantallas.md.
 */
class CertificadoFirmaResource extends Resource
{
    protected static ?string $model = CertificadoFirma::class;

    protected static ?string $cluster = ConfiguracionCluster::class;

    protected static ?string $slug = 'firmas-digitales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?string $navigationLabel = 'Firmas digitales';

    protected static ?string $modelLabel = 'firma digital';

    protected static ?string $pluralModelLabel = 'firmas digitales';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return CertificadosFirmaTable::configure($table);
    }

    /**
     * @return Builder<CertificadoFirma>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['usuario', 'cargadoPor']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificadosFirma::route('/'),
        ];
    }
}
