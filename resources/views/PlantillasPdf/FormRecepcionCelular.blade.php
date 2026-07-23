@php
    /** @var array<string, mixed> $documento */
    $documento = $documento ?? [];
    $titulo = $documento['titulo'] ?? 'Formulario de Entrega / Recepción de Celular';
    $numero = $documento['numero'] ?? 'FURC-2402032';
    $tipo = $documento['tipo'] ?? 'Recepción';
    $fecha = $documento['fecha'] ?? '18/04/2024';
    $usuario = $documento['usuario'] ?? [
        'nombre' => 'Juan Pérez Aguilar',
        'cargo' => 'Analista TI',
        'area' => 'Tecnología de la Información',
    ];
    $celular = $documento['celular'] ?? [
        'marca' => 'Samsung',
        'modelo' => 'Galaxy A54',
        'procesador' => 'Exynos 1380',
        'disco' => '128 GB',
        'ram' => '8 GB',
        'imei' => '356789101234567',
        'capacidad_bateria' => '5000 mAh',
        'color' => 'Negro',
    ];
    $cargador = $documento['cargador'] ?? [
        'modelo' => 'Samsung 25W USB-C',
        'serial' => 'CA90028821',
    ];
    $observaciones = $documento['observaciones'] ?? 'El celular se recibe en perfecto estado y funcionamiento.';
    $firmaIzquierda = $documento['firma_izquierda'] ?? ['nombre' => 'Tecnología de la Información', 'cargo' => null];
    $firmaCentro = $documento['firma_centro'] ?? ['nombre' => 'Administrador', 'cargo' => null];
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
                            <td class="header-info-label">Tipo</td>
                            <td class="header-info-cell"><div class="header-info-box">{{ $tipo }}</div></td>
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
                <td class="col-main-title">Información de Usuario</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Nombre Completo:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $usuario['nombre'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Cargo:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $usuario['cargo'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Área:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $usuario['area'] }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="section-table">
            <tr>
                <td class="col-main-title">Celular</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Marca:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $celular['marca'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Modelo:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $celular['modelo'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Procesador:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $celular['procesador'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Disco:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $celular['disco'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Ram:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $celular['ram'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">IMEI:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $celular['imei'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Capacidad Batería:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $celular['capacidad_bateria'] }}</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Color:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $celular['color'] }}</div></td>
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
                <td class="col-main-title-texto">OBSERVACIONES</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-value-container" colspan="2">
                                <div class="inner-input centered-text">{!! $observaciones !!}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="signatures-table">
            <tr>
                <td class="signature-cell-third">
                    <div class="signature-box"></div>
                    <div class="signature-name">{{ $firmaIzquierda['nombre'] }}</div>
                    @if (filled($firmaIzquierda['cargo'] ?? null))
                        <div class="signature-name">{{ $firmaIzquierda['cargo'] }}</div>
                    @endif
                </td>
                <td class="signature-cell-third">
                    <div class="signature-box"></div>
                    <div class="signature-name">{{ $firmaCentro['nombre'] }}</div>
                    @if (filled($firmaCentro['cargo'] ?? null))
                        <div class="signature-name">{{ $firmaCentro['cargo'] }}</div>
                    @endif
                </td>
                <td class="signature-cell-third">
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
