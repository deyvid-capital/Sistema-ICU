<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\AccesoDaza;
use App\Models\AccesoInternet;
use App\Models\AccesoSharepoint;
use App\Models\AccesoSistema;
use App\Models\AccesoVirtual;
use App\Models\AccUdCompartida;
use App\Models\Area;
use App\Models\AsigCargadorCel;
use App\Models\AsigCelular;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\AzureAd;
use App\Models\Cargador;
use App\Models\CargadorCel;
use App\Models\Cargo;
use App\Models\Celular;
use App\Models\EntregaCelular;
use App\Models\EntregaEquipo;
use App\Models\Equipo;
use App\Models\Firmante;
use App\Models\Fondo;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\MantenimientoEquipo;
use App\Models\MemoriaRam;
use App\Models\Microprocesador;
use App\Models\ModeloLaptop;
use App\Models\Movimiento;
use App\Models\NotaRecepcionEquipo;
use App\Models\NotaSalidaEquipo;
use App\Models\PdfCelular;
use App\Models\PdfEquipo;
use App\Models\PdfMovimiento;
use App\Models\RecepcionCelular;
use App\Models\RecepcionEquipo;
use App\Models\ReciboEquipo;
use App\Models\RecursoVpn;
use App\Models\Sistema;
use App\Models\SitioSharepoint;
use App\Models\Software;
use App\Models\SoftwareArea;
use App\Models\SoftwareExc;
use App\Models\SoftwareUsuario;
use App\Models\TarjetaVideo;
use App\Models\User;
use App\Models\UsuarioExterno;
use App\Models\Virtual;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;

/**
 * Dominio funcional al que pertenece un registro de actividad. Se guarda en la
 * columna `log_name` de `activity_log` y alimenta el filtro "Tipo" del panel.
 *
 * Los dominios reflejan los clusters de navegación del panel, para que el
 * filtro coincida con el lugar donde el usuario encuentra cada recurso.
 */
enum DominioActividad: string implements HasLabel
{
    case Usuarios = 'usuarios';
    case Equipos = 'equipos';
    case Asignaciones = 'asignaciones';
    case Celulares = 'celulares';
    case Accesos = 'accesos';
    case Software = 'software';
    case Recursos = 'recursos';
    case Inventario = 'inventario';
    case Documentos = 'documentos';

    public function getLabel(): string
    {
        return match ($this) {
            self::Usuarios => 'Usuarios',
            self::Equipos => 'Equipos',
            self::Asignaciones => 'Asignaciones y entregas',
            self::Celulares => 'Celulares',
            self::Accesos => 'Accesos',
            self::Software => 'Software y licencias',
            self::Recursos => 'Recursos',
            self::Inventario => 'Inventario técnico',
            self::Documentos => 'Documentos PDF',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Usuarios => 'primary',
            self::Equipos => 'info',
            self::Asignaciones => 'warning',
            self::Celulares => 'success',
            self::Accesos => 'danger',
            self::Software => 'primary',
            self::Recursos => 'info',
            self::Inventario => 'gray',
            self::Documentos => 'gray',
        };
    }

    /**
     * Resuelve el dominio de un modelo (o de su nombre de clase). Devuelve null
     * cuando el modelo no está mapeado, para que el llamador decida el respaldo.
     */
    public static function paraModelo(Model|string $modelo): ?self
    {
        $clase = $modelo instanceof Model ? $modelo::class : $modelo;

        return self::mapaDeModelos()[$clase] ?? null;
    }

    /**
     * @return array<class-string<Model>, self>
     */
    public static function mapaDeModelos(): array
    {
        return [
            User::class => self::Usuarios,
            UsuarioExterno::class => self::Usuarios,
            Area::class => self::Usuarios,
            Cargo::class => self::Usuarios,
            Movimiento::class => self::Usuarios,

            Equipo::class => self::Equipos,
            Cargador::class => self::Equipos,
            NotaRecepcionEquipo::class => self::Equipos,
            NotaSalidaEquipo::class => self::Equipos,
            ReciboEquipo::class => self::Equipos,

            AsignacionEquipo::class => self::Asignaciones,
            AsignacionCargador::class => self::Asignaciones,
            EntregaEquipo::class => self::Asignaciones,
            RecepcionEquipo::class => self::Asignaciones,
            MantenimientoEquipo::class => self::Asignaciones,

            Celular::class => self::Celulares,
            CargadorCel::class => self::Celulares,
            AsigCelular::class => self::Celulares,
            AsigCargadorCel::class => self::Celulares,
            EntregaCelular::class => self::Celulares,
            RecepcionCelular::class => self::Celulares,
            Firmante::class => self::Celulares,

            AccesoDaza::class => self::Accesos,
            AccesoInternet::class => self::Accesos,
            AccesoSharepoint::class => self::Accesos,
            AccesoSistema::class => self::Accesos,
            AccesoVirtual::class => self::Accesos,
            AccUdCompartida::class => self::Accesos,
            AzureAd::class => self::Accesos,

            Software::class => self::Software,
            SoftwareArea::class => self::Software,
            SoftwareExc::class => self::Software,
            SoftwareUsuario::class => self::Software,
            Licencia::class => self::Software,
            LicenciaUsuario::class => self::Software,

            Fondo::class => self::Recursos,
            Sistema::class => self::Recursos,
            SitioSharepoint::class => self::Recursos,
            RecursoVpn::class => self::Recursos,
            Virtual::class => self::Recursos,

            MemoriaRam::class => self::Inventario,
            Microprocesador::class => self::Inventario,
            ModeloLaptop::class => self::Inventario,
            TarjetaVideo::class => self::Inventario,

            PdfEquipo::class => self::Documentos,
            PdfCelular::class => self::Documentos,
            PdfMovimiento::class => self::Documentos,
        ];
    }
}
