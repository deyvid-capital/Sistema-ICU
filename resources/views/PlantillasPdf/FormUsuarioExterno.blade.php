@php
    /** @var array<string, mixed> $documento */
    $documento = $documento ?? [];
    $titulo = $documento['titulo'] ?? 'Formulario de Usuario Externo';
    $numero = $documento['numero'] ?? '001-2026';
    $tipo = $documento['tipo'] ?? 'Alta';
    $solicitante = $documento['solicitante'] ?? 'Cecilia Elizabeth Arandia Terán';
    $externo = $documento['externo'] ?? [
        'empresa' => 'Consultor independiente',
        'nombre' => 'Javier Villegas',
        'cargo' => 'Consultor externo',
        'correo' => 'usuario@consultora.com',
        'fecha_inicio' => '25/11/2026',
        'fecha_fin' => '31/12/2026',
    ];
    $sitio = $documento['sitio'] ?? 'Sostenibilidad e Impacto';
    $permiso = $documento['permiso'] ?? 'Edición';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }} - CAPITAL+ SAFI</title>
    @include('PlantillasPdf.partials.styles')
</head>
<body>
    <div class="container">
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <img src="{{ asset('images/Capital-Safi-SA-logo-.png') }}" alt="Logo Capital Safi">
                </td>
                <td style="width: 75%; vertical-align: top; padding: 4px 8px;">
                    <div class="header-title">{{ $titulo }}</div>
                    <table class="header-info-table">
                        <tr>
                            <td class="header-info-label">N°</td>
                            <td class="header-info-cell"><div class="header-info-box">{{ $numero }}</div></td>
                        </tr>
                        <tr>
                            <td class="header-info-label">Tipo</td>
                            <td class="header-info-cell"><div class="header-info-box">{{ $tipo }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-table">
            <tr>
                <td class="col-main-title">Usuario Solicitante</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Nombre:</td>
                            <td class="inner-value-container"><div class="inner-input" style="font-weight: bold;">{{ $solicitante }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-table">
            <tr>
                <td class="col-main-title">Datos Usuario Externo</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Empresa:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $externo['empresa'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Nombre:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $externo['nombre'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Cargo:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $externo['cargo'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Correo Electrónico:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $externo['correo'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Fecha de Inicio:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $externo['fecha_inicio'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Fecha de Finalización:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $externo['fecha_fin'] }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-table">
            <tr>
                <td class="col-main-title">Sitio</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Sharepoint / OneDrive</td>
                            <td class="inner-value-container"><div class="inner-input" style="font-weight: bold; color: #0f766e;">{{ $sitio }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-table">
            <tr>
                <td class="col-main-title">Permisos</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Documentos Compartidos:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $permiso }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="signatures-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-box"></div>
                    <div class="signature-name">Gerente de Área</div>
                </td>
                <td class="signature-cell">
                    <div class="signature-box"></div>
                    <div class="signature-name">Subgerente de Tecnología de la Información</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
