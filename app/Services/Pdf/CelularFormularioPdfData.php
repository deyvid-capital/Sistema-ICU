<?php

namespace App\Services\Pdf;

use App\Enums\CelularFormularioPdfTipo;
use App\Enums\FirmantePosicion;
use App\Models\AsigCargadorCel;
use App\Models\Celular;
use App\Models\EntregaCelular;
use App\Models\Firmante;
use App\Models\RecepcionCelular;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CelularFormularioPdfData
{
    /**
     * @return array{documento: array<string, mixed>}
     */
    public function for(Model $record, CelularFormularioPdfTipo $tipo): array
    {
        $documento = match ($tipo) {
            CelularFormularioPdfTipo::Entrega => $this->fromEntrega($this->require($record, EntregaCelular::class)),
            CelularFormularioPdfTipo::Recepcion => $this->fromRecepcion($this->require($record, RecepcionCelular::class)),
        };

        return ['documento' => $documento];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromEntrega(EntregaCelular $entrega): array
    {
        $entrega->loadMissing([
            'asignacion.usuario.cargo',
            'asignacion.usuario.area',
            'asignacion.celular.asignacionesCargador.cargador',
            'firmantes.usuario.cargo',
        ]);

        $usuario = $entrega->asignacion?->usuario;
        $celular = $entrega->asignacion?->celular;
        $firmas = $this->resolveFirmas($entrega->firmantes, $usuario);

        return [
            'titulo' => 'Formulario de Entrega / Recepción de Celular',
            'numero' => (string) $entrega->numero,
            'tipo' => 'Entrega',
            'fecha' => $entrega->fecha?->format('d/m/Y') ?? '—',
            'usuario' => $this->mapUsuario($usuario),
            'celular' => $this->mapCelular($celular),
            'cargador' => $this->mapCargador($celular),
            'observaciones' => filled($entrega->observaciones) ? (string) $entrega->observaciones : '—',
            'nota' => filled($entrega->nota) ? (string) $entrega->nota : null,
            'firma_izquierda' => $firmas['izquierda'],
            'firma_centro' => $firmas['centro'],
            'firma_derecha' => $firmas['derecha'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromRecepcion(RecepcionCelular $recepcion): array
    {
        $recepcion->loadMissing([
            'asignacion.usuario.cargo',
            'asignacion.usuario.area',
            'asignacion.celular.asignacionesCargador.cargador',
            'firmantes.usuario.cargo',
        ]);

        $usuario = $recepcion->asignacion?->usuario;
        $celular = $recepcion->asignacion?->celular;
        $firmas = $this->resolveFirmas($recepcion->firmantes, $usuario);

        return [
            'titulo' => 'Formulario de Entrega / Recepción de Celular',
            'numero' => (string) $recepcion->numero,
            'tipo' => 'Recepción',
            'fecha' => $recepcion->fecha?->format('d/m/Y') ?? '—',
            'usuario' => $this->mapUsuario($usuario),
            'celular' => $this->mapCelular($celular),
            'cargador' => $this->mapCargador($celular),
            'observaciones' => filled($recepcion->nota) ? (string) $recepcion->nota : '—',
            'firma_izquierda' => $firmas['izquierda'],
            'firma_centro' => $firmas['centro'],
            'firma_derecha' => $firmas['derecha'],
        ];
    }

    /**
     * @return array{nombre: string, cargo: string, area: string}
     */
    private function mapUsuario(?User $usuario): array
    {
        return [
            'nombre' => $usuario?->name ?? '—',
            'cargo' => $usuario?->cargo?->cargo ?? '—',
            'area' => $usuario?->area?->area ?? '—',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mapCelular(?Celular $celular): array
    {
        return [
            'marca' => $celular?->marca ?? '—',
            'modelo' => $celular?->modelo ?? '—',
            'procesador' => $celular?->procesador ?? '—',
            'disco' => $celular?->disco ?? '—',
            'ram' => $celular?->ram ?? '—',
            'imei' => $celular?->imei ?? '—',
            'capacidad_bateria' => $celular?->bateria ?? '—',
            'color' => $celular?->color ?? '—',
        ];
    }

    /**
     * @return array{modelo: string, serial: string}
     */
    private function mapCargador(?Celular $celular): array
    {
        /** @var AsigCargadorCel|null $asignacion */
        $asignacion = $celular?->asignacionesCargador?->first();
        $cargador = $asignacion?->cargador;

        return [
            'modelo' => $cargador?->modelo ?? '—',
            'serial' => $cargador?->serial ?? '—',
        ];
    }

    /**
     * @param  Collection<int, Firmante>  $firmantes
     * @return array{
     *     izquierda: array{nombre: string, cargo: ?string},
     *     centro: array{nombre: string, cargo: ?string},
     *     derecha: array{nombre: string, cargo: ?string}
     * }
     */
    private function resolveFirmas(Collection $firmantes, ?User $usuarioAsignado): array
    {
        $porPosicion = $firmantes->keyBy(
            fn (Firmante $firmante): string => $firmante->posicion instanceof FirmantePosicion
                ? $firmante->posicion->value
                : (string) $firmante->posicion,
        );

        $izquierda = $porPosicion->get(FirmantePosicion::Izquierda->value)?->usuario;
        $centro = $porPosicion->get(FirmantePosicion::Centro->value)?->usuario;
        $derecha = $porPosicion->get(FirmantePosicion::Derecha->value)?->usuario;

        return [
            'izquierda' => [
                'nombre' => $izquierda?->name ?? 'Tecnología de la Información',
                'cargo' => $izquierda?->cargo?->cargo,
            ],
            'centro' => [
                'nombre' => $centro?->name ?? 'Administrador',
                'cargo' => $centro?->cargo?->cargo,
            ],
            'derecha' => [
                'nombre' => $derecha?->name ?? $usuarioAsignado?->name ?? '—',
                'cargo' => $derecha?->cargo?->cargo ?? $usuarioAsignado?->cargo?->cargo,
            ],
        ];
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private function require(Model $record, string $class): Model
    {
        if (! $record instanceof $class) {
            throw new \InvalidArgumentException("Se esperaba {$class}.");
        }

        return $record;
    }
}
