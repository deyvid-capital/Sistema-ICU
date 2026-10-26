<?php

namespace App\Filament\Resources\CertificadosFirma\Tables;

use App\Enums\EstadoCertificadoFirma;
use App\Filament\Resources\CertificadosFirma\Actions\CertificadoFirmaActions;
use App\Models\CertificadoFirma;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificadosFirmaTable
{
    /**
     * Días antes del vencimiento en que una firma se marca "por vencer".
     */
    public const DIAS_AVISO_VENCIMIENTO = 30;

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Sin firmas digitales asignadas')
            ->emptyStateDescription('Usa "Asignar firma" para subir el .p12 de un usuario.')
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedUser),
                TextColumn::make('titular_cn')
                    ->label('Titular del certificado')
                    ->searchable()
                    ->placeholder('Se completa al activar'),
                TextColumn::make('titular_documento')
                    ->label('CI')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('cargo_en_sello')
                    ->label('Cargo en sello')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('emisor')
                    ->label('Emisor')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('vigente_hasta')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn (CertificadoFirma $record): ?string => self::colorVencimiento($record))
                    ->description(fn (CertificadoFirma $record): ?string => self::descripcionVencimiento($record)),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Cargado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (CertificadoFirma $record): ?string => $record->cargadoPor?->name)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoCertificadoFirma::class)
                    ->multiple()
                    ->default(array_map(
                        fn (EstadoCertificadoFirma $estado): string => $estado->value,
                        EstadoCertificadoFirma::vigentes(),
                    )),
                Filter::make('por_vencer')
                    ->label('Vencen en los próximos '.self::DIAS_AVISO_VENCIMIENTO.' días')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('vigente_hasta', '>=', now())
                        ->where('vigente_hasta', '<=', now()->addDays(self::DIAS_AVISO_VENCIMIENTO))),
            ])
            ->recordActions([
                ActionGroup::make([
                    CertificadoFirmaActions::renovar(),
                    CertificadoFirmaActions::editarSello(),
                    CertificadoFirmaActions::reenviarAviso(),
                    CertificadoFirmaActions::revocar(),
                ]),
            ]);
    }

    private static function colorVencimiento(CertificadoFirma $record): ?string
    {
        $dias = $record->diasParaVencer();

        return match (true) {
            $dias === null => null,
            $dias < 0 => 'danger',
            $dias <= self::DIAS_AVISO_VENCIMIENTO => 'warning',
            default => null,
        };
    }

    private static function descripcionVencimiento(CertificadoFirma $record): ?string
    {
        $dias = $record->diasParaVencer();

        return match (true) {
            $dias === null => null,
            $dias < 0 => 'Vencido',
            $dias <= self::DIAS_AVISO_VENCIMIENTO => "Vence en {$dias} días",
            default => null,
        };
    }
}
