<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Entrega / Recepción - CAPITAL+ SAFI</title>
    <style>
        /* Configuraciones Generales */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: "Gisha", Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
            background-color: #555;
            color: #000;
            display: flex;
            justify-content: center;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .container {
            width: 816px;
            min-height: 1056px;
            background-color: #fff;
            padding: 2cm 1cm 2cm 2.5cm;
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
            position: relative;
        }
        .header-table {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        .header-logo {
            width: 25%;
            vertical-align: top;
            padding-top: 5px;
        }
        .header-logo img {
            max-width: 160px;
            height: auto;
        }
        .header-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #003366;
            width: 100%;
            margin-bottom: 12px;
        }
        .header-info-table {
            width: 101.5%;
            border-collapse: collapse;
        }
        .header-info-table td {
            vertical-align: middle;
        }
        .header-info-label {
            width: 127px;
            color: #003366;
            font-weight: bold;
            text-align: right;
            padding-right: 15px;
        }
        .header-info-cell {
            padding: 2px 5px 2px 1px;
            vertical-align: middle;
        }
        .header-info-box {
            border: 1.8px solid #dad8d8;
            padding: 3px 6px;
            min-height: 14px;
            background-color: #fff;
            width: 100%;
            color: #000;
            font-size: 10px;
        }
        .section-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #003366;
            margin-bottom: 2px;
        }
        .col-main-title {
            background-color: #e6e6e6;
            width: 20%;
            color: #000000;
            font-weight: bold;
            text-align: left;
            padding: 10px 10px 10px 15px;
            vertical-align: middle;
            border-right: none;
            font-size: 10px;
        }
        .col-main-title-texto {
            width: 44%;
            color: #f70000;
            font-weight: bold;
            text-align: center;
            padding: 10px 10px 10px 15px;
            vertical-align: middle;
            border-right: none;
            font-size: 10px;
        }
        .col-content {
            width: 75%;
            padding: 1px 4px;
            vertical-align: middle;
        }
        .inner-table {
            margin-left: 6px;
            width: 100%;
            border-collapse: collapse;
        }
        .inner-table td {
            padding: 2.5px 6px;
            vertical-align: middle;
        }
        .inner-label {
            width: 145px;
            color: #003366;
            font-weight: bold;
            padding-left: 5px;
        }
        .inner-value-container {
            padding-right: 5px;
        }
        .inner-input {
            border: 1.8px solid #dad8d8;
            padding: 2.5px 8px;
            min-height: 14px;
            background-color: #fff;
            width: 100%;
            color: #000;
            font-size: 10px;
        }
        .centered-text {
            text-align: center;
            padding: 8px;
        }
        .nota-text {
            text-align: center;
            padding: 8px 15px;
            line-height: 1.4;
            font-size: 10px;
        }
        .signatures-table {
            width: 100%;
            margin-bottom: 20px;
            border: none;
        }
        .signature-cell {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signature-box {
            width: 220px;
            height: 70px;
            border: 1.5px dashed #888;
            border-radius: 10px;
            margin: 0 auto 8px auto;
        }
        .signature-name {
            font-weight: bold;
            font-size: 10px;
            margin: 0;
            line-height: 1.2;
        }
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body {
                background-color: transparent;
                padding: 0;
            }
            .container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                min-height: auto;
                padding: 0;
            }
            @page {
                size: letter;
                margin: 2cm 1cm 2cm 2.5cm;
            }
        }
    </style>
</head>
<body>

    <div class="container">

        <!-- Encabezado -->
        <table class="header-table">
            <tr>
                <td class="header-logo">
                    <img src="{{ asset('images/Capital-Safi-SA-logo-.png') }}" alt="Logo Capital Safi">
                </td>
                <td style="width: 75%; vertical-align: top; padding: 4px 8px;">
                    <div class="header-title">
                        Formulario de Entrega / Recepción
                    </div>
                    <table class="header-info-table">
                        <tr>
                            <td class="header-info-label">N°</td>
                            <td class="header-info-cell">
                                <div class="header-info-box">A-2402032</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="header-info-label">Tipo</td>
                            <td class="header-info-cell">
                                <div class="header-info-box">Entrega</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="header-info-label">Fecha</td>
                            <td class="header-info-cell">
                                <div class="header-info-box">18/04/2024</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Información de Usuario -->
        <table class="section-table">
            <tr>
                <td class="col-main-title">Información de Usuario</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Nombre Completo:</td>
                            <td class="inner-value-container"><div class="inner-input">Juan Pérez Aguilar</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Cargo:</td>
                            <td class="inner-value-container"><div class="inner-input">Analista TI</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Área:</td>
                            <td class="inner-value-container"><div class="inner-input">Tecnología de la Información</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Equipo -->
        <table class="section-table">
            <tr>
                <td class="col-main-title">Equipo</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Modelo:</td>
                            <td class="inner-value-container"><div class="inner-input">Dell Latitude 5510</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Microprocesador:</td>
                            <td class="inner-value-container"><div class="inner-input">Intel Core i5-10210U</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Disco Duro:</td>
                            <td class="inner-value-container"><div class="inner-input">SSD 512 GB</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Ram:</td>
                            <td class="inner-value-container"><div class="inner-input">16 GB</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">GPU:</td>
                            <td class="inner-value-container"><div class="inner-input">Intel UHD Graphics</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Service Tag:</td>
                            <td class="inner-value-container"><div class="inner-input">GH123J64</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Garantía:</td>
                            <td class="inner-value-container"><div class="inner-input">21/01/2025</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Cargador -->
        <table class="section-table">
            <tr>
                <td class="col-main-title">Cargador</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Modelo</td>
                            <td class="inner-value-container"><div class="inner-input">Dell 65W USB-C</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">S/N</td>
                            <td class="inner-value-container"><div class="inner-input">CA90028821</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Mouse -->
        <table class="section-table">
            <tr>
                <td class="col-main-title">Mouse</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Modelo</td>
                            <td class="inner-value-container"><div class="inner-input">Logitech M100</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">S/N</td>
                            <td class="inner-value-container"><div class="inner-input">-</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Cooler -->
        <table class="section-table">
            <tr>
                <td class="col-main-title">Cooler</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Modelo</td>
                            <td class="inner-value-container"><div class="inner-input">Cooler Master Notepal</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Audífono -->
        <table class="section-table">
            <tr>
                <td class="col-main-title">Audífono</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Modelo</td>
                            <td class="inner-value-container"><div class="inner-input">JBL Tune 500</div></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Observaciones -->
        <table class="section-table">
            <tr>
                <td class="col-main-title-texto">OBSERVACIONES</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label"></td>
                                <div class="inner-input centered-text">
                                    Todo el equipo se entrega en perfecto estado y funcionamiento. 
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Nota -->
        <table class="section-table">
            <tr>
                <td class="col-main-title-texto">NOTA</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label"></td>
                                <div class="inner-input nota-text">
                                    <b>1. Responsabilidad del Usuario:</b> Cada usuario es responsable de los equipos y accesorios que se les ha asignado. Esto incluye su uso adecuado y su mantenimiento en buen estado.<br>
                                    <b>2. Cuidado del Equipo:</b> Se espera que todos los usuarios manejen los equipos y accesorios con cuidado y diligencia. Eviten exponer los equipos a condiciones que puedan causar daños, como humedad, calor extremo o golpes.<br>
                                    <b>3. Extravío o Deterioro:</b> En caso de extravío o deterioro del equipo debido a negligencia o mal uso, el usuario será responsable de asumir el costo de reparación o reemplazo del mismo.
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Firmas -->
        <table class="signatures-table">
            <tr>
                <td class="signature-cell">
                    <div class="signature-box"></div>
                    <div class="signature-name">Tecnología de la Información</div>
                </td>
                <td class="signature-cell">
                    <div class="signature-box"></div>
                    <div class="signature-name">Juan Pérez Aguilar</div>
                    <div class="signature-name">Analista TI</div>
                </td>
            </tr>
        </table>

    </div>

</body>
</html>