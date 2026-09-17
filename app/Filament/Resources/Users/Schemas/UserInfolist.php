<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\AccesoDaza;
use App\Models\AccesoInternet;
use App\Models\AccesoSistema;
use App\Models\AsigCelular;
use App\Models\AsignacionEquipo;
use App\Models\Movimiento;
use App\Models\SoftwareArea;
use App\Models\User;
use Closure;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make('Datos personales')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nombre')
                                    ->icon(Heroicon::OutlinedUser)
                                    ->weight('medium')
                                    ->size('md'),
                                TextEntry::make('carnet')
                                    ->label('Carnet')
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->placeholder('Sin carnet'),
                                TextEntry::make('area.area')
                                    ->label('Área')
                                    ->icon(Heroicon::OutlinedBuildingOffice2)
                                    ->badge()
                                    ->color('info')
                                    ->placeholder('Sin área'),
                                TextEntry::make('cargo.cargo')
                                    ->label('Cargo')
                                    ->icon(Heroicon::OutlinedBriefcase)
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('Sin cargo'),
                                TextEntry::make('movimientos.tipo')
                                    ->label('Estado actual')
                                    ->badge()
                                    ->state(fn (User $record): ?string => $record->movimientos->last()?->tipo)
                                    ->color(fn (User $record): string => $record->movimientos->last()?->tipo === 'Alta' ? 'success' : 'danger')
                                    ->placeholder('Sin movimientos'),
                                TextEntry::make('email')
                                    ->label('Correo electrónico')
                                    ->icon(Heroicon::OutlinedEnvelope)
                                    ->copyable()
                                    ->copyMessage('Correo copiado')
                                    ->placeholder('—'),
                                TextEntry::make('telefono')
                                    ->label('Teléfono')
                                    ->icon(Heroicon::OutlinedPhone)
                                    ->copyable()
                                    ->copyMessage('Teléfono copiado')
                                    ->placeholder('Sin teléfono'),
                            ]),
                    ]),

                Tabs::make('Recursos y accesos del usuario')
                    ->columnSpanFull()
                    ->persistTabInQueryString('detalle-usuario')
                    ->tabs([

                        Tab::make('Resumen')
                            ->icon(Heroicon::OutlinedSquares2x2)
                            ->schema([
                                Section::make('Qué tiene y qué no tiene asignado')
                                    ->description('Vista rápida de todos los recursos relacionados con el usuario. Verde = tiene registros activos, amarillo = sólo registros inactivos, gris = nunca se le asignó.')
                                    ->schema([
                                        Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])
                                            ->schema(static::resumenEntries()),
                                    ]),
                            ]),

                        Tab::make('Accesos')
                            ->icon(Heroicon::OutlinedKey)
                            ->badge(fn (User $record): ?int => static::totalOrNull(
                                $record->accesosInternet->count()
                                + $record->accesosDaza->count()
                                + $record->accesosSharepoint->count()
                                + $record->accesosSistemas->count()
                                + $record->unidadesCompartidas->count()
                                + $record->azureAd->count()
                            ))
                            ->badgeColor('info')
                            ->schema([

                                Section::make('Acceso a red / Wifi, VPN y máquinas virtuales')
                                    ->icon(Heroicon::OutlinedGlobeAlt)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('accesosInternet')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 3])
                                                    ->schema([
                                                        TextEntry::make('usuario_red')
                                                            ->label('Usuario de red')
                                                            ->icon(Heroicon::OutlinedUserCircle)
                                                            ->copyable()
                                                            ->placeholder('—'),
                                                        TextEntry::make('nivel_acceso')
                                                            ->label('Nivel de acceso')
                                                            ->badge()
                                                            ->color('info')
                                                            ->placeholder('—'),
                                                        TextEntry::make('estado')
                                                            ->label('Estado')
                                                            ->badge()
                                                            ->state(fn (AccesoInternet $record): string => $record->estado ? 'Activo' : 'Inactivo')
                                                            ->color(fn (AccesoInternet $record): string => $record->estado ? 'success' : 'danger'),
                                                        IconEntry::make('fortitoken')
                                                            ->label('FortiToken')
                                                            ->boolean(),
                                                        TextEntry::make('fecha')
                                                            ->label('Fecha')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('—'),
                                                        TextEntry::make('observaciones')
                                                            ->label('Observaciones')
                                                            ->placeholder('Sin observaciones'),
                                                        TextEntry::make('vpn')
                                                            ->label('Recursos VPN')
                                                            ->badge()
                                                            ->color('warning')
                                                            ->state(fn (AccesoInternet $record): array => $record->accesosVirtual
                                                                ->map(fn ($acceso): ?string => $acceso->recursoVpn?->nombre_vpn)
                                                                ->filter()
                                                                ->unique()
                                                                ->values()
                                                                ->all())
                                                            ->placeholder('Sin VPN asignada')
                                                            ->columnSpan(['md' => 2]),
                                                        TextEntry::make('maquinas_virtuales')
                                                            ->label('Máquinas virtuales')
                                                            ->badge()
                                                            ->color('primary')
                                                            ->state(fn (AccesoInternet $record): array => $record->accesosVirtual
                                                                ->map(fn ($acceso): ?string => $acceso->virtual?->maquina_virtual)
                                                                ->filter()
                                                                ->unique()
                                                                ->values()
                                                                ->all())
                                                            ->placeholder('Sin máquina virtual'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('accesosInternet', 'El usuario no tiene acceso a red / Internet registrado.'),
                                    ]),

                                Section::make('Accesos DAZA')
                                    ->icon(Heroicon::OutlinedCircleStack)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('accesosDaza')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 3])
                                                    ->schema([
                                                        TextEntry::make('usuario_daza')
                                                            ->label('Usuario DAZA')
                                                            ->icon(Heroicon::OutlinedUserCircle)
                                                            ->copyable()
                                                            ->placeholder('—'),
                                                        TextEntry::make('fondo.nombre_completo')
                                                            ->label('Fondo')
                                                            ->badge()
                                                            ->color('info')
                                                            ->placeholder('Sin fondo'),
                                                        TextEntry::make('nivel_acceso')
                                                            ->label('Nivel de acceso')
                                                            ->badge()
                                                            ->color('gray')
                                                            ->placeholder('—'),
                                                        TextEntry::make('ambiente')
                                                            ->label('Ambiente')
                                                            ->badge()
                                                            ->placeholder('—'),
                                                        TextEntry::make('fecha')
                                                            ->label('Fecha')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('—'),
                                                        TextEntry::make('estado')
                                                            ->label('Estado')
                                                            ->badge()
                                                            ->state(fn (AccesoDaza $record): string => $record->estado ? 'Activo' : 'Inactivo')
                                                            ->color(fn (AccesoDaza $record): string => $record->estado ? 'success' : 'danger'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('accesosDaza', 'El usuario no tiene accesos DAZA registrados.'),
                                    ]),

                                Section::make('Sistemas')
                                    ->icon(Heroicon::OutlinedServerStack)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('accesosSistemas')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 3])
                                                    ->schema([
                                                        TextEntry::make('sistema.nombre_sistema')
                                                            ->label('Sistema')
                                                            ->badge()
                                                            ->color('info')
                                                            ->placeholder('—'),
                                                        TextEntry::make('usuario_sistema')
                                                            ->label('Usuario en el sistema')
                                                            ->copyable()
                                                            ->placeholder('—'),
                                                        TextEntry::make('nivel_acceso')
                                                            ->label('Nivel de acceso')
                                                            ->badge()
                                                            ->color('gray')
                                                            ->placeholder('—'),
                                                        TextEntry::make('fecha_inicio')
                                                            ->label('Desde')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('—'),
                                                        TextEntry::make('fecha_fin')
                                                            ->label('Hasta')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('Vigente'),
                                                        TextEntry::make('estado')
                                                            ->label('Estado')
                                                            ->badge()
                                                            ->state(fn (AccesoSistema $record): string => $record->estado ? 'Activo' : 'Inactivo')
                                                            ->color(fn (AccesoSistema $record): string => $record->estado ? 'success' : 'danger'),
                                                        TextEntry::make('observaciones')
                                                            ->label('Observaciones')
                                                            ->placeholder('Sin observaciones')
                                                            ->columnSpanFull(),
                                                    ]),
                                            ]),
                                        static::emptyNotice('accesosSistemas', 'El usuario no tiene accesos a sistemas registrados.'),
                                    ]),

                                Section::make('Sitios de SharePoint')
                                    ->icon(Heroicon::OutlinedCloud)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('accesosSharepoint')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 3])
                                                    ->schema([
                                                        TextEntry::make('sitio.nombre_sitio')
                                                            ->label('Sitio')
                                                            ->badge()
                                                            ->color('info')
                                                            ->placeholder('—'),
                                                        TextEntry::make('sitio.url')
                                                            ->label('URL')
                                                            ->icon(Heroicon::OutlinedLink)
                                                            ->copyable()
                                                            ->url(fn ($record): ?string => $record->sitio?->url)
                                                            ->openUrlInNewTab()
                                                            ->placeholder('—'),
                                                        TextEntry::make('estado')
                                                            ->label('Estado')
                                                            ->badge()
                                                            ->state(fn ($record): string => $record->estado ? 'Activo' : 'Inactivo')
                                                            ->color(fn ($record): string => $record->estado ? 'success' : 'danger'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('accesosSharepoint', 'El usuario no tiene accesos a sitios de SharePoint.'),
                                    ]),

                                Section::make('Unidades compartidas')
                                    ->icon(Heroicon::OutlinedFolderOpen)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('unidadesCompartidas')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 2])
                                                    ->schema([
                                                        TextEntry::make('unidad_compartida')
                                                            ->label('Unidad')
                                                            ->icon(Heroicon::OutlinedFolder)
                                                            ->copyable()
                                                            ->placeholder('—'),
                                                        TextEntry::make('estado')
                                                            ->label('Estado')
                                                            ->badge()
                                                            ->state(fn ($record): string => $record->estado ? 'Activo' : 'Inactivo')
                                                            ->color(fn ($record): string => $record->estado ? 'success' : 'danger'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('unidadesCompartidas', 'El usuario no tiene unidades compartidas asignadas.'),
                                    ]),

                                Section::make('Azure AD')
                                    ->icon(Heroicon::OutlinedShieldCheck)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('azureAd')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 4])
                                                    ->schema([
                                                        TextEntry::make('iniciales')
                                                            ->label('Iniciales')
                                                            ->badge()
                                                            ->color('gray')
                                                            ->placeholder('—'),
                                                        IconEntry::make('sincronizacion')
                                                            ->label('Sincronizado')
                                                            ->boolean(),
                                                        IconEntry::make('mfa')
                                                            ->label('MFA')
                                                            ->boolean(),
                                                        TextEntry::make('estado')
                                                            ->label('Estado')
                                                            ->badge()
                                                            ->state(fn ($record): string => $record->estado ? 'Activo' : 'Inactivo')
                                                            ->color(fn ($record): string => $record->estado ? 'success' : 'danger'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('azureAd', 'El usuario no tiene cuenta de Azure AD registrada.'),
                                    ]),
                            ]),

                        Tab::make('Software')
                            ->icon(Heroicon::OutlinedCommandLine)
                            ->badge(fn (User $record): ?int => static::totalOrNull(
                                $record->softwareUsuarios->count() + $record->softwareExcepciones->count()
                            ))
                            ->badgeColor('info')
                            ->schema([

                                Section::make('Software asignado')
                                    ->icon(Heroicon::OutlinedCommandLine)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('softwareUsuarios')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 3])
                                                    ->schema([
                                                        TextEntry::make('software.nombre_software')
                                                            ->label('Software')
                                                            ->badge()
                                                            ->color('info')
                                                            ->placeholder('—'),
                                                        TextEntry::make('software.categoria')
                                                            ->label('Categoría')
                                                            ->badge()
                                                            ->color('gray')
                                                            ->placeholder('—'),
                                                        TextEntry::make('estado')
                                                            ->label('Estado')
                                                            ->badge()
                                                            ->state(fn ($record): string => $record->estado ? 'Activo' : 'Inactivo')
                                                            ->color(fn ($record): string => $record->estado ? 'success' : 'danger'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('softwareUsuarios', 'El usuario no tiene software asignado directamente.'),
                                    ]),

                                Section::make('Excepciones de software')
                                    ->icon(Heroicon::OutlinedExclamationTriangle)
                                    ->description('Permisos especiales. El estado indica si ya fueron aprobados.')
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('softwareExcepciones')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 3])
                                                    ->schema([
                                                        TextEntry::make('software.nombre_software')
                                                            ->label('Software')
                                                            ->badge()
                                                            ->color('info')
                                                            ->placeholder('—'),
                                                        TextEntry::make('estado_aprobacion')
                                                            ->label('Aprobación')
                                                            ->badge()
                                                            ->placeholder('—'),
                                                        TextEntry::make('tipo_excepcion')
                                                            ->label('Tipo')
                                                            ->badge()
                                                            ->color(fn ($record): string => match ($record->tipo_excepcion) {
                                                                'Emergencia' => 'danger',
                                                                'Permanente' => 'warning',
                                                                'Temporal' => 'info',
                                                                default => 'gray',
                                                            })
                                                            ->placeholder('—'),
                                                        TextEntry::make('aprobador.name')
                                                            ->label('Aprobador')
                                                            ->icon(Heroicon::OutlinedUserCircle)
                                                            ->placeholder('—'),
                                                        TextEntry::make('fecha_inicio')
                                                            ->label('Desde')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('—'),
                                                        TextEntry::make('fecha_fin')
                                                            ->label('Hasta')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('Sin vencimiento'),
                                                        TextEntry::make('motivo')
                                                            ->label('Motivo')
                                                            ->placeholder('Sin motivo registrado')
                                                            ->columnSpanFull(),
                                                    ]),
                                            ]),
                                        static::emptyNotice('softwareExcepciones', 'El usuario no tiene excepciones de software.'),
                                    ]),

                                Section::make('Software autorizado por su área')
                                    ->icon(Heroicon::OutlinedBuildingOffice2)
                                    ->description('Software habilitado para el área del usuario, aunque no esté asignado individualmente.')
                                    ->collapsed()
                                    ->schema([
                                        TextEntry::make('software_area')
                                            ->hiddenLabel()
                                            ->badge()
                                            ->color('gray')
                                            ->state(fn (User $record): array => $record->id_area === null
                                                ? []
                                                : SoftwareArea::query()
                                                    ->where('area_id', $record->id_area)
                                                    ->with('software')
                                                    ->get()
                                                    ->map(fn (SoftwareArea $item): ?string => $item->software?->nombre_software)
                                                    ->filter()
                                                    ->unique()
                                                    ->values()
                                                    ->all())
                                            ->placeholder('Sin software autorizado para el área.'),
                                    ]),
                            ]),

                        Tab::make('Licencias')
                            ->icon(Heroicon::OutlinedDocumentCheck)
                            ->badge(fn (User $record): ?int => static::totalOrNull($record->licenciasUsuarios->count()))
                            ->badgeColor('info')
                            ->schema([
                                Section::make('Licencias asignadas')
                                    ->icon(Heroicon::OutlinedDocumentCheck)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('licenciasUsuarios')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 4])
                                                    ->schema([
                                                        TextEntry::make('licencia.nombre_licencia')
                                                            ->label('Licencia')
                                                            ->badge()
                                                            ->color('info')
                                                            ->placeholder('—'),
                                                        TextEntry::make('licencia.categoria')
                                                            ->label('Categoría')
                                                            ->badge()
                                                            ->color('gray')
                                                            ->placeholder('—'),
                                                        TextEntry::make('fecha')
                                                            ->label('Fecha de asignación')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('—'),
                                                        TextEntry::make('estado')
                                                            ->label('Estado')
                                                            ->badge()
                                                            ->state(fn ($record): string => $record->estado ? 'Activa' : 'Inactiva')
                                                            ->color(fn ($record): string => $record->estado ? 'success' : 'danger'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('licenciasUsuarios', 'El usuario no tiene licencias asignadas.'),
                                    ]),
                            ]),

                        Tab::make('Equipos y celulares')
                            ->icon(Heroicon::OutlinedComputerDesktop)
                            ->badge(fn (User $record): ?int => static::totalOrNull(
                                $record->asignacionesEquipos->count() + $record->asignacionesCelulares->count()
                            ))
                            ->badgeColor('info')
                            ->schema([

                                Section::make('Equipos asignados')
                                    ->icon(Heroicon::OutlinedComputerDesktop)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('asignacionesEquipos')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 4])
                                                    ->schema([
                                                        TextEntry::make('service_tag')
                                                            ->label('Service Tag')
                                                            ->badge()
                                                            ->color('info')
                                                            ->copyable()
                                                            ->placeholder('—'),
                                                        TextEntry::make('equipo.nombre_equipo')
                                                            ->label('Nombre del equipo')
                                                            ->icon(Heroicon::OutlinedComputerDesktop)
                                                            ->placeholder('—'),
                                                        TextEntry::make('fecha_inicio')
                                                            ->label('Desde')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('—'),
                                                        TextEntry::make('estado_asignacion')
                                                            ->label('Situación')
                                                            ->badge()
                                                            ->state(fn (AsignacionEquipo $record): string => $record->fecha_fin === null
                                                                ? 'En uso'
                                                                : 'Devuelto el '.$record->fecha_fin->format('d/m/Y'))
                                                            ->color(fn (AsignacionEquipo $record): string => $record->fecha_fin === null ? 'success' : 'gray'),
                                                        TextEntry::make('motivo')
                                                            ->label('Motivo')
                                                            ->placeholder('—')
                                                            ->columnSpan(['md' => 2]),
                                                        TextEntry::make('observaciones')
                                                            ->label('Observaciones')
                                                            ->placeholder('Sin observaciones')
                                                            ->columnSpan(['md' => 2]),
                                                    ]),
                                            ]),
                                        static::emptyNotice('asignacionesEquipos', 'El usuario no tiene equipos asignados.'),
                                    ]),

                                Section::make('Celulares asignados')
                                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                                    ->collapsible()
                                    ->schema([
                                        RepeatableEntry::make('asignacionesCelulares')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 4])
                                                    ->schema([
                                                        TextEntry::make('celular.marca')
                                                            ->label('Marca')
                                                            ->badge()
                                                            ->color('info')
                                                            ->placeholder('—'),
                                                        TextEntry::make('celular.modelo')
                                                            ->label('Modelo')
                                                            ->placeholder('—'),
                                                        TextEntry::make('celular.imei')
                                                            ->label('IMEI')
                                                            ->copyable()
                                                            ->placeholder('—'),
                                                        TextEntry::make('estado_asignacion')
                                                            ->label('Situación')
                                                            ->badge()
                                                            ->state(fn (AsigCelular $record): string => $record->fecha_fin === null
                                                                ? 'En uso'
                                                                : 'Devuelto el '.$record->fecha_fin->format('d/m/Y'))
                                                            ->color(fn (AsigCelular $record): string => $record->fecha_fin === null ? 'success' : 'gray'),
                                                        TextEntry::make('fecha_ini')
                                                            ->label('Desde')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('—'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('asignacionesCelulares', 'El usuario no tiene celulares asignados.'),
                                    ]),
                            ]),

                        Tab::make('Movimientos')
                            ->icon(Heroicon::OutlinedArrowsRightLeft)
                            ->badge(fn (User $record): ?int => static::totalOrNull($record->movimientos->count()))
                            ->badgeColor('info')
                            ->schema([
                                Section::make('Historial de movimientos')
                                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                                    ->description('Altas y bajas registradas para el usuario.')
                                    ->schema([
                                        RepeatableEntry::make('movimientos')
                                            ->hiddenLabel()
                                            ->state(fn (User $record) => $record->movimientos()->orderByDesc('fecha')->get())
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 3])
                                                    ->schema([
                                                        TextEntry::make('tipo')
                                                            ->label('Tipo')
                                                            ->badge()
                                                            ->color(fn (Movimiento $record): string => $record->tipo === 'Alta' ? 'success' : 'danger'),
                                                        TextEntry::make('fecha')
                                                            ->label('Fecha')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y'),
                                                        TextEntry::make('cargo.cargo')
                                                            ->label('Cargo')
                                                            ->icon(Heroicon::OutlinedBriefcase)
                                                            ->placeholder('—'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('movimientos', 'Este usuario no tiene movimientos registrados.'),
                                    ]),
                            ]),

                        Tab::make('Documentos')
                            ->icon(Heroicon::OutlinedPencilSquare)
                            ->badge(fn (User $record): ?int => static::totalOrNull($record->firmantes->count()))
                            ->badgeColor('info')
                            ->schema([
                                Section::make('Documentos donde firma')
                                    ->icon(Heroicon::OutlinedPencilSquare)
                                    ->schema([
                                        RepeatableEntry::make('firmantes')
                                            ->hiddenLabel()
                                            ->schema([
                                                Grid::make(['default' => 1, 'md' => 3])
                                                    ->schema([
                                                        TextEntry::make('documento_type')
                                                            ->label('Tipo de documento')
                                                            ->badge()
                                                            ->color('gray')
                                                            ->formatStateUsing(fn (?string $state): string => $state === null
                                                                ? '—'
                                                                : class_basename($state)),
                                                        TextEntry::make('posicion')
                                                            ->label('Posición de firma')
                                                            ->badge()
                                                            ->color('info')
                                                            ->placeholder('—'),
                                                        TextEntry::make('fecha')
                                                            ->label('Fecha')
                                                            ->icon(Heroicon::OutlinedCalendarDays)
                                                            ->date('d/m/Y')
                                                            ->placeholder('—'),
                                                    ]),
                                            ]),
                                        static::emptyNotice('firmantes', 'El usuario no figura como firmante en ningún documento.'),
                                    ]),
                            ]),

                        Tab::make('Registro')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Section::make('Información del registro')
                                    ->icon(Heroicon::OutlinedInformationCircle)
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('email_verified_at')
                                            ->label('Correo verificado el')
                                            ->icon(Heroicon::OutlinedCheckBadge)
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('Sin verificar'),
                                        TextEntry::make('created_at')
                                            ->label('Registrado el')
                                            ->icon(Heroicon::OutlinedCalendarDays)
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('—'),
                                        TextEntry::make('updated_at')
                                            ->label('Actualizado el')
                                            ->icon(Heroicon::OutlinedArrowPath)
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('—'),
                                        TextEntry::make('deleted_at')
                                            ->label('Eliminado el')
                                            ->icon(Heroicon::OutlinedTrash)
                                            ->dateTime('d/m/Y H:i')
                                            ->visible(fn (User $record): bool => $record->trashed())
                                            ->placeholder('—'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    /**
     * Construye las tarjetas del resumen: una por cada tipo de recurso relacionado al usuario.
     *
     * @return array<int, TextEntry>
     */
    protected static function resumenEntries(): array
    {
        return [
            static::resumenEntry(
                'internet',
                'Acceso a red / WIFI',
                Heroicon::OutlinedGlobeAlt,
                fn (User $record): int => $record->accesosInternet->count(),
                fn (User $record): int => $record->accesosInternet->where('estado', true)->count(),
            ),
            static::resumenEntry(
                'vpn',
                'Recursos VPN',
                Heroicon::OutlinedLockClosed,
                fn (User $record): int => static::accesosVirtualesDe($record)->whereNotNull('vpn_id')->count(),
                fn (User $record): int => static::accesosVirtualesDe($record)->whereNotNull('vpn_id')->count(),
            ),
            static::resumenEntry(
                'virtual',
                'Máquinas virtuales',
                Heroicon::OutlinedRectangleGroup,
                fn (User $record): int => static::accesosVirtualesDe($record)->whereNotNull('virtual_id')->count(),
                fn (User $record): int => static::accesosVirtualesDe($record)->whereNotNull('virtual_id')->count(),
            ),
            static::resumenEntry(
                'daza',
                'Accesos DAZA',
                Heroicon::OutlinedCircleStack,
                fn (User $record): int => $record->accesosDaza->count(),
                fn (User $record): int => $record->accesosDaza->where('estado', true)->count(),
            ),
            static::resumenEntry(
                'sistemas',
                'Sistemas',
                Heroicon::OutlinedServerStack,
                fn (User $record): int => $record->accesosSistemas->count(),
                fn (User $record): int => $record->accesosSistemas->where('estado', true)->count(),
            ),
            static::resumenEntry(
                'sharepoint',
                'Sitios de SharePoint',
                Heroicon::OutlinedCloud,
                fn (User $record): int => $record->accesosSharepoint->count(),
                fn (User $record): int => $record->accesosSharepoint->where('estado', true)->count(),
            ),
            static::resumenEntry(
                'unidades',
                'Unidades compartidas',
                Heroicon::OutlinedFolderOpen,
                fn (User $record): int => $record->unidadesCompartidas->count(),
                fn (User $record): int => $record->unidadesCompartidas->where('estado', true)->count(),
            ),
            static::resumenEntry(
                'azure',
                'Azure AD',
                Heroicon::OutlinedShieldCheck,
                fn (User $record): int => $record->azureAd->count(),
                fn (User $record): int => $record->azureAd->where('estado', true)->count(),
            ),
            static::resumenEntry(
                'software',
                'Software asignado',
                Heroicon::OutlinedCommandLine,
                fn (User $record): int => $record->softwareUsuarios->count(),
                fn (User $record): int => $record->softwareUsuarios->where('estado', true)->count(),
            ),
            static::resumenEntry(
                'excepciones',
                'Excepciones de software',
                Heroicon::OutlinedExclamationTriangle,
                fn (User $record): int => $record->softwareExcepciones->count(),
                fn (User $record): int => $record->softwareExcepciones->count(),
            ),
            static::resumenEntry(
                'licencias',
                'Licencias',
                Heroicon::OutlinedDocumentCheck,
                fn (User $record): int => $record->licenciasUsuarios->count(),
                fn (User $record): int => $record->licenciasUsuarios->where('estado', true)->count(),
            ),
            static::resumenEntry(
                'equipos',
                'Equipos',
                Heroicon::OutlinedComputerDesktop,
                fn (User $record): int => $record->asignacionesEquipos->count(),
                fn (User $record): int => $record->asignacionesEquipos->whereNull('fecha_fin')->count(),
            ),
            static::resumenEntry(
                'celulares',
                'Celulares',
                Heroicon::OutlinedDevicePhoneMobile,
                fn (User $record): int => $record->asignacionesCelulares->count(),
                fn (User $record): int => $record->asignacionesCelulares->whereNull('fecha_fin')->count(),
            ),
            static::resumenEntry(
                'firmantes',
                'Documentos firmados',
                Heroicon::OutlinedPencilSquare,
                fn (User $record): int => $record->firmantes->count(),
                fn (User $record): int => $record->firmantes->count(),
            ),
        ];
    }

    /**
     * Tarjeta de resumen con el total de registros y cuántos están vigentes.
     */
    protected static function resumenEntry(string $key, string $label, Heroicon $icon, Closure $total, Closure $activos): TextEntry
    {
        return TextEntry::make('resumen_'.$key)
            ->label($label)
            ->icon($icon)
            ->badge()
            ->state(function (User $record) use ($total, $activos): string {
                $totalRegistros = $total($record);

                if ($totalRegistros === 0) {
                    return 'No tiene';
                }

                $vigentes = $activos($record);

                if ($vigentes === 0) {
                    return $totalRegistros.' inactivo'.($totalRegistros === 1 ? '' : 's');
                }

                return $vigentes === $totalRegistros
                    ? $vigentes.' activo'.($vigentes === 1 ? '' : 's')
                    : $vigentes.' de '.$totalRegistros.' activos';
            })
            ->color(function (User $record) use ($total, $activos): string {
                if ($total($record) === 0) {
                    return 'gray';
                }

                return $activos($record) > 0 ? 'success' : 'warning';
            });
    }

    /**
     * Accesos a VPN y máquinas virtuales derivados de los accesos a red del usuario.
     */
    protected static function accesosVirtualesDe(User $record): Collection
    {
        return $record->accesosInternet
            ->flatMap(fn (AccesoInternet $acceso) => $acceso->accesosVirtual)
            ->values();
    }

    /**
     * Aviso que se muestra sólo cuando la relación no tiene registros.
     */
    protected static function emptyNotice(string $relation, string $message): TextEntry
    {
        return TextEntry::make('sin_'.$relation)
            ->hiddenLabel()
            ->state($message)
            ->color('gray')
            ->visible(fn (User $record): bool => $record->{$relation}->isEmpty());
    }

    /**
     * Devuelve null cuando no hay registros para que la insignia de la pestaña no se muestre.
     */
    protected static function totalOrNull(int $total): ?int
    {
        return $total > 0 ? $total : null;
    }
}
