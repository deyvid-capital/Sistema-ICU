@php
    /** @var array<string, mixed> $documento */
    $documento = $documento ?? [];
    $titulo = $documento['titulo'] ?? 'Nota de Salida de Equipos';
    $numero = $documento['numero'] ?? 'A-2402032';
    $fecha = $documento['fecha'] ?? '18/04/2024';
    $equipo = $documento['equipo'] ?? [
        'modelo' => 'Dell Latitude 5510',
        'microprocesador' => 'Intel Core i5-10210U',
        'disco_duro' => 'SSD 512 GB',
        'ram' => '16 GB',
        'gpu' => 'Intel UHD Graphics',
        'service_tag' => 'GH123J64',
    ];
    $cargador = $documento['cargador'] ?? [
        'modelo' => 'Dell 65W USB-C',
        'serial' => 'CA90028821',
    ];
    $nota = $documento['nota'] ?? 'Todo el equipo se entrega en perfecto estado y funcionamiento.';
    $firmaIzquierda = $documento['firma_izquierda'] ?? ['nombre' => 'Tecnología de la Información', 'cargo' => null];
    $firmaDerecha = $documento['firma_derecha'] ?? ['nombre' => 'Juan Pérez Aguilar', 'cargo' => 'Analista TI'];
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
                            <td class="header-info-label">Fecha</td>
                            <td class="header-info-cell"><div class="header-info-box">{{ $fecha }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-table">
            <tr>
                <td class="col-main-title">Equipo</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Modelo:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $equipo['modelo'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Microprocesador:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $equipo['microprocesador'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Disco Duro:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $equipo['disco_duro'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Ram:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $equipo['ram'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">GPU:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $equipo['gpu'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Service Tag:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $equipo['service_tag'] }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-table">
            <tr>
                <td class="col-main-title">Cargador</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Modelo</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $cargador['modelo'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">S/N</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $cargador['serial'] }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-table">
            <tr>
                <td class="col-main-title-texto">NOTA</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-value-container" colspan="2">
                                <div class="inner-input centered-text">{!! $nota !!}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="signatures-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-box"></div>
                    <div class="signature-name">{{ $firmaIzquierda['nombre'] }}</div>
                    @if (filled($firmaIzquierda['cargo'] ?? null))
                        <div class="signature-name">{{ $firmaIzquierda['cargo'] }}</div>
                    @endif
                </td>
                <td class="signature-cell">
                    <div class="signature-box"></div>
                    <div class="signature-name">{{ $firmaDerecha['nombre'] }}</div>
                    @if (filled($firmaDerecha['cargo'] ?? null))
                        <div class="signature-name">{{ $firmaDerecha['cargo'] }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
