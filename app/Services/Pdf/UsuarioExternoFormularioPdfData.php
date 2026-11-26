<?php

namespace App\Services\Pdf;

use App\Models\UsuarioExterno;

/**
 * Datos del Formulario de Usuario Externo para la plantilla
 * PlantillasPdf.FormUsuarioExterno.
 */
class UsuarioExternoFormularioPdfData
{
    /**
     * @return array{documento: array<string, mixed>}
     */
    public function for(UsuarioExterno $usuarioExterno): array
    {
        $usuarioExterno->loadMissing(['responsable', 'sitioSharepoint']);

        return [
            'documento' => [
                'titulo' => 'Formulario de Usuario Externo',
                'numero' => (string) $usuarioExterno->numero,
                'tipo' => 'Alta',
                'solicitante' => $usuarioExterno->responsable?->name ?? '—',
                'externo' => [
                    'empresa' => $usuarioExterno->empresa ?: '—',
                    'nombre' => $usuarioExterno->nombre_contacto ?: '—',
                    'cargo' => $usuarioExterno->cargo ?: '—',
                    'correo' => $usuarioExterno->correo ?: '—',
                    'fecha_inicio' => $usuarioExterno->fecha_inicio?->format('d/m/Y') ?? '—',
                    'fecha_fin' => $usuarioExterno->fecha_fin?->format('d/m/Y') ?? '—',
                ],
                'sitio' => $this->sitio($usuarioExterno),
                'permiso' => $usuarioExterno->permiso?->getLabel() ?? '—',
            ],
        ];
    }

    /**
     * SharePoint y OneDrive juntos si están los dos.
     */
    private function sitio(UsuarioExterno $usuarioExterno): string
    {
        $partes = array_filter([
            $usuarioExterno->sitioSharepoint?->nombre_sitio,
            $usuarioExterno->onedrive,
        ], fn (?string $parte): bool => filled($parte));

        return $partes === [] ? '—' : implode(' / ', $partes);
    }
}
