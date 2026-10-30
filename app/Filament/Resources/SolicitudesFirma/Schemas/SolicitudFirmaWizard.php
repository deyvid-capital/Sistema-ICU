<?php

namespace App\Filament\Resources\SolicitudesFirma\Schemas;

use App\Models\FirmaSetting;
use App\Models\User;
use App\Rules\FirmanteDisponible;
use App\Services\Archivos\ArchivosService;
use App\Services\Firma\CrearSolicitudFirma;
use App\Services\Firma\DocumentosFirmables;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;

/**
 * Pasos de "Preparar envío": documentos → firmantes → detalles.
 */
class SolicitudFirmaWizard
{
    /**
     * @return array<int, Step>
     */
    public static function steps(): array
    {
        return [
            self::documentos(),
            self::firmantes(),
            self::detalles(),
        ];
    }

    private static function documentos(): Step
    {
        return Step::make('documentos')
            ->label('Documentos')
            ->description('Uno o varios PDF')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->schema([
                Grid::make(['default' => 1, 'md' => 2])->schema([
                    Select::make('categoria_documentos')
                        ->label('Categoría')
                        ->placeholder('Todas las categorías')
                        ->options(DocumentosFirmables::categorias())
                        ->native(false)
                        ->live()
                        ->dehydrated(false),
                    Toggle::make('mostrar_firmados')
                        ->label('Mostrar firmados')
                        ->helperText('Se ven para consultar, pero no se pueden volver a enviar.')
                        ->default(false)
                        ->inline(false)
                        ->live()
                        ->dehydrated(false),
                ]),
                Select::make('documentos_sistema')
                    ->label('Documentos del sistema')
                    ->helperText('Del más reciente al más antiguo. Escribe el número o el nombre del archivo para buscar. No aparecen los que están en firma o ya firmados.')
                    ->multiple()
                    ->searchable()
                    ->options(fn (Get $get): array => app(DocumentosFirmables::class)->buscar(null, $get('categoria_documentos'), (bool) $get('mostrar_firmados'), 10))
                    ->getSearchResultsUsing(fn (string $search, Get $get): array => app(DocumentosFirmables::class)->buscar($search, $get('categoria_documentos'), (bool) $get('mostrar_firmados')))
                    ->getOptionLabelsUsing(fn (array $values): array => app(DocumentosFirmables::class)->etiquetas($values))
                    ->disableOptionWhen(fn (string $value): bool => ! app(DocumentosFirmables::class)->esElegible($value))
                    ->maxItems(CrearSolicitudFirma::MAX_DOCUMENTOS)
                    ->requiredWithout('documentos_subidos')
                    ->validationMessages(['required_without' => 'Elige al menos un documento del sistema o sube un PDF.']),
                FileUpload::make('documentos_subidos')
                    ->label('Subir PDF')
                    ->helperText('Se guarda una copia privada solo para este envío.')
                    ->multiple()
                    ->storeFiles(false)
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(fn (): int => app(ArchivosService::class)->maxPdfKb())
                    ->maxFiles(CrearSolicitudFirma::MAX_DOCUMENTOS),
            ]);
    }

    private static function firmantes(): Step
    {
        return Step::make('firmantes')
            ->label('Firmantes')
            ->description('Solo usuarios con firma activa')
            ->icon(Heroicon::OutlinedUsers)
            ->schema([
                Toggle::make('orden_obligatorio')
                    ->label('Exigir orden de firma')
                    ->helperText('Apagado: firma quien esté disponible, en cualquier orden. Encendido: firman uno tras otro en el orden de la lista.')
                    ->default(false)
                    ->live(),
                Select::make('firmantes')
                    ->label('Firmantes')
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => self::opcionesFirmantes())
                    ->nestedRecursiveRules([new FirmanteDisponible])
                    ->required()
                    ->visible(fn (Get $get): bool => ! $get('orden_obligatorio')),
                Repeater::make('firmantes_en_orden')
                    ->label('Firmantes, en orden')
                    ->helperText('Arrastra para cambiar quién firma primero.')
                    ->simple(
                        Select::make('usuario_id')
                            ->options(fn (): array => self::opcionesFirmantes())
                            ->searchable()
                            ->distinct()
                            ->rule(new FirmanteDisponible)
                            ->required(),
                    )
                    ->reorderable()
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel('Agregar firmante')
                    ->visible(fn (Get $get): bool => (bool) $get('orden_obligatorio')),
            ]);
    }

    private static function detalles(): Step
    {
        $ajustes = FirmaSetting::actual();

        return Step::make('detalles')
            ->label('Detalles')
            ->description('Lo que verán los firmantes')
            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
            ->schema([
                TextInput::make('asunto')
                    ->label('Asunto')
                    ->required()
                    ->maxLength(255),
                Textarea::make('mensaje')
                    ->label('Mensaje para los firmantes')
                    ->rows(3)
                    ->maxLength(2000),
                Toggle::make('con_fecha_limite')
                    ->label('Definir fecha límite')
                    ->helperText('Apagado: el envío no tiene fecha límite.')
                    ->default(false)
                    ->live(),
                Callout::make('¿Para qué sirve la fecha límite?')
                    ->info()
                    ->description('Es una referencia para los firmantes: aparece en el aviso y en los recordatorios. Al vencer no se bloquea la firma; los recordatorios siguientes se marcan como vencidos y se copian a quien creó el envío.')
                    ->visible(fn (Get $get): bool => (bool) $get('con_fecha_limite')),
                DatePicker::make('fecha_limite')
                    ->label('Fecha límite')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->minDate(today())
                    ->default(today()->addDays($ajustes->plazo_dias_por_defecto))
                    ->required()
                    ->visible(fn (Get $get): bool => (bool) $get('con_fecha_limite')),
                TextInput::make('recordatorio_cada_horas')
                    ->label('Recordatorio cada (horas)')
                    ->helperText('Déjalo vacío para no enviar recordatorios.')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(720)
                    ->default($ajustes->recordatorio_horas_por_defecto),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function opcionesFirmantes(): array
    {
        return CrearSolicitudFirma::firmantesDisponibles()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $usuario): array => [(string) $usuario->getKey() => $usuario->name])
            ->all();
    }
}
