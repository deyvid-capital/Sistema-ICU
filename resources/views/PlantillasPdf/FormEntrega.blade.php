@php
    /** @var array<string, mixed> $documento */
    $documento = $documento ?? [];
    $titulo = $documento['titulo'] ?? 'Formulario de Entrega / Recepción';
    $numero = $documento['numero'] ?? 'A-2402032';
    $tipo = $documento['tipo'] ?? 'Entrega';
    $fecha = $documento['fecha'] ?? '18/04/2024';
    $usuario = $documento['usuario'] ?? [
        'nombre' => 'Juan Pérez Aguilar',
        'cargo' => 'Analista TI',
        'area' => 'Tecnología de la Información',
    ];
    $equipo = $documento['equipo'] ?? [
        'modelo' => 'Dell Latitude 5510',
        'microprocesador' => 'Intel Core i5-10210U',
        'disco_duro' => 'SSD 512 GB',
        'ram' => '16 GB',
        'gpu' => 'Intel UHD Graphics',
        'service_tag' => 'GH123J64',
        'garantia' => '21/01/2025',
    ];
    $cargador = $documento['cargador'] ?? [
        'modelo' => 'Dell 65W USB-C',
        'serial' => 'CA90028821',
    ];
    $mouse = $documento['mouse'] ?? ['incluido' => true, 'etiqueta' => 'Incluido'];
    $cooler = $documento['cooler'] ?? ['incluido' => true, 'etiqueta' => 'Cooler Master Notepal'];
    $audifonos = $documento['audifonos'] ?? ['incluido' => true, 'etiqueta' => 'JBL Tune 500'];
    $observaciones = $documento['observaciones'] ?? 'Todo el equipo se entrega en perfecto estado y funcionamiento.';
    $nota = $documento['nota'] ?? null;
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
                        <tr>
                            <td class="inner-label">Garantía:</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $equipo['garantia'] }}</div></td>
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

        @if (($mouse['incluido'] ?? false) || filled($mouse['etiqueta'] ?? null))
        <table class="section-table">
            <tr>
                <td class="col-main-title">Mouse</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Estado</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $mouse['etiqueta'] ?? '—' }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        @endif

        @if (($cooler['incluido'] ?? false) || filled($cooler['etiqueta'] ?? null))
        <table class="section-table">
            <tr>
                <td class="col-main-title">Cooler</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Estado</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $cooler['etiqueta'] ?? '—' }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        @endif

        @if (($audifonos['incluido'] ?? false) || filled($audifonos['etiqueta'] ?? null))
        <table class="section-table">
            <tr>
                <td class="col-main-title">Audífono</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Estado</td>
                            <td class="inner-value-container"><div class="inner-input">{{ $audifonos['etiqueta'] ?? '—' }}</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        @endif

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

        <table class="section-table">
            <tr>
                <td class="col-main-title-texto">NOTA</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-value-container" colspan="2">
                                <div class="inner-input nota-text">
                                    @if (filled($nota))
                                        {!! $nota !!}
                                    @else
                                        <b>1. Responsabilidad del Usuario:</b> Cada usuario es responsable de los equipos y accesorios que se les ha asignado. Esto incluye su uso adecuado y su mantenimiento en buen estado.<br>
                                        <b>2. Cuidado del Equipo:</b> Se espera que todos los usuarios manejen los equipos y accesorios con cuidado y diligencia. Eviten exponer los equipos a condiciones que puedan causar daños, como humedad, calor extremo o golpes.<br>
                                        <b>3. Extravío o Deterioro:</b> En caso de extravío o deterioro del equipo debido a negligencia o mal uso, el usuario será responsable de asumir el costo de reparación o reemplazo del mismo.
                                    @endif
                                </div>
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
