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
        .signatures-table {
            margin-top: 10px;
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
                        Nota de Recepcion Externa de Equipos
                    </div>
                    <table class="header-info-table">
                        <tr>
                            <td class="header-info-label">N°</td>
                            <td class="header-info-cell">
                                <div class="header-info-box">A-2402032</div>
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

        <!-- Equipo -->
        <table class="section-table">
            <tr>
                <td class="col-main-title">Equipo</td>
                <td class="col-content">
                    <table class="inner-table">
                        <tr>
                            <td class="inner-label">Marca:</td>
                            <td class="inner-value-container"><div class="inner-input">Dell</div></td>
                        </tr>
                        <tr>
                            <td class="inner-label">Modelo:</td>
                            <td class="inner-value-container"><div class="inner-input">Latitude 5510</div></td>
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

      
        <!-- Observaciones -->
        <table class="section-table">
            <tr>
                <td class="col-main-title-texto">NOTA</td>
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
            <tr>
                <td colspan="2" class="signature-cell" style="text-align: center;">
                    <div class="signature-box" style="margin-left:auto;margin-right:auto;"></div>
                    <div class="signature-name" style="text-align: center; margin-top: 20px;">
                        <span style="font-weight: bold;">Empresa:</span>
                        <span style="font-weight: 400; color: #333;">Aseguradoa fortaleza S.A.</span>
                    </div>
                    <div class="signature-name" style="text-align: center; margin-top: 12px;">
                        <span style="font-weight: bold;">Nombre:</span>
                        <span style="font-weight: 400; color: #333;">Jorge Luis Fuentes Arroyo</span>
                    </div>
                    <div class="signature-name" style="text-align: center; margin-top: 12px;">
                        <span style="font-weight: bold;">Cargo:</span>
                        <span style="font-weight: 400; color: #333;">Tecnico en Seguridad de la Informacion</span>
                    </div>
                    <div class="signature-name" style="text-align: center; margin-top: 12px;">
                        <span style="font-weight: bold;">C.I.:</span>
                        <span style="font-weight: 400; color: #333;">1234567890</span>
                    </div>
                </td>
           
            </tr>
        </table>
   
        

    </div>

</body>
</html>