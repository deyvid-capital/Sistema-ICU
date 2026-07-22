<?php

namespace App\Services\Pdf;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\EntregaEquipo;
use App\Models\Equipo;
use App\Models\Firmante;
use App\Models\NotaRecepcionEquipo;
use App\Models\NotaSalidaEquipo;
use App\Models\RecepcionEquipo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EquipoFormularioPdfData
{
    /**
     * @return array{documento: array<string, mixed>}
     */
    public function for(Model $record, EquipoFormularioPdfTipo $tipo, ?string $firmanteId = null): array
    {
        $documento = match ($tipo) {
            EquipoFormularioPdfTipo::Entrega => $this->fromEntrega($this->require($record, EntregaEquipo::class)),
            EquipoFormularioPdfTipo::Recepcion => $this->fromRecepcion($this->require($record, RecepcionEquipo::class)),
            EquipoFormularioPdfTipo::NotaSalida => $this->fromNotaSalida($this->require($record, NotaSalidaEquipo::class)),
            EquipoFormularioPdfTipo::NotaRecepcion => $this->fromNotaRecepcion($this->require($record, NotaRecepcionEquipo::class), $firmanteId),
        };

        return ['documento' => $documento];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromEntrega(EntregaEquipo $entrega): array
    {
        $entrega->loadMissing([
            'asignacion.usuario.cargo',
            'asignacion.usuario.area',
            'asignacion.equipo.modelo',
            'asignacion.equipo.microprocesador',
            'asignacion.equipo.ram',
            'asignacion.equipo.gpu',
            'asignacion.equipo.asignacionCargador.cargador',
            'firmantes.usuario.cargo',
        ]);

        $usuario = $entrega->asignacion?->usuario;
        $equipo = $entrega->asignacion?->equipo;
        $firmas = $this->resolveFirmas($entrega->firmantes, $usuario);

        return [
            'titulo' => 'Formulario de Entrega / Recepción',
            'numero' => (string) $entrega->numero,
            'tipo' => 'Entrega',
            'fecha' => $entrega->fecha?->format('d/m/Y') ?? '—',
            'usuario' => $this->mapUsuario($usuario),
            'equipo' => $this->mapEquipo($equipo, withGarantia: true),
            'cargador' => $this->mapCargador($equipo),
            'mouse' => $this->mapAccesorioBoolean((bool) $entrega->mouse),
            'cooler' => $this->mapAccesorioBoolean((bool) $entrega->cooler),
            'audifonos' => $this->mapAccesorioBoolean((bool) $entrega->audifonos),
            'observaciones' => filled($entrega->observaciones) ? (string) $entrega->observaciones : '—',
            'nota' => filled($entrega->nota) ? (string) $entrega->nota : null,
            'firma_izquierda' => $firmas['izquierda'],
            'firma_derecha' => $firmas['derecha'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromRecepcion(RecepcionEquipo $recepcion): array
    {
        $recepcion->loadMissing([
            'asignacion.usuario.cargo',
            'asignacion.usuario.area',
            'asignacion.equipo.modelo',
            'asignacion.equipo.microprocesador',
            'asignacion.equipo.ram',
            'asignacion.equipo.gpu',
            'asignacion.equipo.asignacionCargador.cargador',
            'firmantes.usuario.cargo',
        ]);

        $usuario = $recepcion->asignacion?->usuario;
        $equipo = $recepcion->asignacion?->equipo;
        $firmas = $this->resolveFirmas($recepcion->firmantes, $usuario);

        return [
            'titulo' => 'Formulario de Entrega / Recepción',
            'numero' => (string) $recepcion->numero,
            'tipo' => 'Recepción',
            'fecha' => $recepcion->fecha?->format('d/m/Y') ?? '—',
            'usuario' => $this->mapUsuario($usuario),
            'equipo' => $this->mapEquipo($equipo, withGarantia: true),
            'cargador' => $this->mapCargador($equipo),
            'observaciones' => filled($recepcion->observaciones) ? (string) $recepcion->observaciones : '—',
            'firma_izquierda' => $firmas['izquierda'],
            'firma_derecha' => $firmas['derecha'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromNotaSalida(NotaSalidaEquipo $nota): array
    {
        $nota->loadMissing([
            'equipo.modelo',
            'equipo.microprocesador',
            'equipo.ram',
            'equipo.gpu',
            'equipo.asignacionCargador.cargador',
            'usuarioExterno',
            'firmantes.usuario.cargo',
        ]);

        $firmas = $this->resolveFirmas(
            $nota->firmantes,
            null,
            fallbackDerecha: [
                'nombre' => $nota->usuarioExterno?->nombre ?? '—',
                'cargo' => $nota->usuarioExterno?->cargo,
            ],
        );

        return [
            'titulo' => 'Nota de Salida de Equipos',
            'numero' => (string) $nota->numero,
            'fecha' => $nota->fecha?->format('d/m/Y') ?? '—',
            'equipo' => $this->mapEquipo($nota->equipo, withGarantia: false),
            'cargador' => $this->mapCargador($nota->equipo),
            'nota' => filled($nota->nota) ? (string) $nota->nota : '—',
            'firma_izquierda' => $firmas['izquierda'],
            'firma_derecha' => $firmas['derecha'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fromNotaRecepcion(NotaRecepcionEquipo $nota, ?string $firmanteId = null): array
    {
        $nota->loadMissing([
            'equipo.modelo',
            'equipo.microprocesador',
            'equipo.ram',
            'equipo.gpu',
            'equipo.asignacionCargador.cargador',
            'usuarioExterno',
            'firmantes.usuario.cargo',
        ]);

        $firmas = $this->resolveFirmas($nota->firmantes, null);
        $firmanteSeleccionado = $this->mapFirmanteSeleccionado($firmanteId);

        if ($firmanteSeleccionado !== null) {
            $firmas['derecha'] = $firmanteSeleccionado;
        }

        $externo = $nota->usuarioExterno;

        return [
            'titulo' => 'Nota de Recepción Externa de Equipos',
            'numero' => (string) $nota->numero,
            'fecha' => $nota->fecha?->format('d/m/Y') ?? '—',
            'equipo' => array_merge(
                $this->mapEquipo($nota->equipo, withGarantia: false),
                ['marca' => '—'],
            ),
            'cargador' => $this->mapCargador($nota->equipo),
            'nota' => filled($nota->nota) ? (string) $nota->nota : '—',
            'firma_izquierda' => $firmas['izquierda'],
            'firma_derecha' => $firmas['derecha'],
            'externo' => [
                'empresa' => $externo?->empresa ?? '—',
                'nombre' => $externo?->nombre ?? '—',
                'cargo' => $externo?->cargo ?? '—',
                'carnet' => $externo?->carnet ?? '—',
            ],
        ];
    }

    /**
     * Resuelve el usuario firmante elegido al momento de generar el PDF.
     * No proviene de la base de datos de la nota; solo aplica al documento.
     *
     * @return array{nombre: string, cargo: ?string}|null
     */
    private function mapFirmanteSeleccionado(?string $firmanteId): ?array
    {
        if (! filled($firmanteId)) {
            return null;
        }

        $usuario = User::with('cargo')->find($firmanteId);

        if ($usuario === null) {
            return null;
        }

        return [
            'nombre' => $usuario->name,
            'cargo' => $usuario->cargo?->cargo,
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
    private function mapEquipo(?Equipo $equipo, bool $withGarantia): array
    {
        $data = [
            'modelo' => $equipo?->modelo?->nombre ?? '—',
            'microprocesador' => $equipo?->microprocesador?->nombre_micro ?? '—',
            'disco_duro' => $equipo?->disco_duro ?? '—',
            'ram' => $equipo?->ram?->tipo_ram ?? '—',
            'gpu' => $equipo?->gpu?->nombre_gpu ?? '—',
            'service_tag' => $equipo?->service_tag ?? '—',
        ];

        if ($withGarantia) {
            $data['garantia'] = $equipo?->venc_garantia?->format('d/m/Y') ?? '—';
        }

        return $data;
    }

    /**
     * @return array{modelo: string, serial: string}
     */
    private function mapCargador(?Equipo $equipo): array
    {
        $cargador = $equipo?->asignacionCargador?->cargador;

        return [
            'modelo' => $cargador?->modelo ?? '—',
            'serial' => $cargador?->serial ?? '—',
        ];
    }

    /**
     * @return array{incluido: bool, etiqueta: string|null}
     */
    private function mapAccesorioBoolean(bool $incluido): array
    {
        return [
            'incluido' => $incluido,
            'etiqueta' => $incluido ? 'Incluido' : null,
        ];
    }

    /**
     * @param  Collection<int, Firmante>  $firmantes
     * @param  array{nombre: string, cargo: ?string}|null  $fallbackDerecha
     * @return array{izquierda: array{nombre: string, cargo: ?string}, derecha: array{nombre: string, cargo: ?string}}
     */
    private function resolveFirmas(Collection $firmantes, ?User $usuarioAsignado, ?array $fallbackDerecha = null): array
    {
        $ordered = $firmantes->sortBy('id')->values();
        $primero = $ordered->get(0)?->usuario;
        $segundo = $ordered->get(1)?->usuario;

        return [
            'izquierda' => [
                'nombre' => $primero?->name ?? 'Tecnología de la Información',
                'cargo' => $primero?->cargo?->cargo,
            ],
            'derecha' => [
                'nombre' => $segundo?->name
                    ?? $fallbackDerecha['nombre']
                    ?? $usuarioAsignado?->name
                    ?? '—',
                'cargo' => $segundo?->cargo?->cargo
                    ?? $fallbackDerecha['cargo']
                    ?? $usuarioAsignado?->cargo?->cargo,
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
