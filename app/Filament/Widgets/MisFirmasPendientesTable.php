<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaFirmante;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Envíos en curso con recuadros que el usuario actual todavía debe firmar.
 *
 * Es personal: no usa permiso de Shield (está excluido en config/filament-shield.php)
 * y, como la policy de SolicitudFirma, depende de ser firmante, no de un rol.
 */
class MisFirmasPendientesTable extends TableWidget
{
    protected static ?int $sort = 7;

    protected static ?string $heading = 'Mis firmas pendientes';

    /**
     * Solo para quien firma o puede firmar: firmantes de algún envío o con firma vigente.
     */
    public static function canView(): bool
    {
        $usuario = Filament::auth()->user();

        if (! $usuario instanceof User) {
            return false;
        }

        return SolicitudFirmaFirmante::query()->where('usuario_id', $usuario->getKey())->exists()
            || $usuario->certificadosFirma()->vigentes()->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Documentos en curso que esperan tu firma.')
            ->query(fn (): Builder => $this->consulta())
            ->defaultSort('fecha_limite')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('No tienes firmas pendientes')
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge)
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->weight('medium'),
                TextColumn::make('asunto')
                    ->label('Asunto')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('creador.name')
                    ->label('Enviado por'),
                TextColumn::make('fecha_limite')
                    ->label('Fecha límite')
                    ->date('d/m/Y')
                    ->placeholder('Sin fecha')
                    ->color(fn (SolicitudFirma $record): ?string => $record->fecha_limite?->isPast() ? 'danger' : null)
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('firmar')
                    ->label('Firmar')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->button()
                    ->url(fn (SolicitudFirma $record): string => SolicitudFirmaResource::getUrl('view', ['record' => $record])),
            ]);
    }

    /**
     * @return Builder<SolicitudFirma>
     */
    private function consulta(): Builder
    {
        $usuarioId = Filament::auth()->id();

        return SolicitudFirma::query()
            ->with('creador')
            ->where('estado', EstadoSolicitudFirma::EnCurso)
            ->whereHas('firmantes', fn (Builder $query) => $query
                ->where('usuario_id', $usuarioId)
                ->whereHas('firmas', fn (Builder $query) => $query
                    ->where('estado', EstadoFirma::Pendiente)
                    ->whereHas('documento', fn (Builder $query) => $query->where('estado', EstadoDocumentoFirma::EnCurso))));
    }
}
