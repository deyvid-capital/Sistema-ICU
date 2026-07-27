@php
    /** @var array<string, mixed> $documento */
    $documento = $documento ?? [];
    $tipo = $documento['tipo'] ?? 'Alta';
    $fechaAlta = $documento['fecha_alta'] ?? '1/4/2026';
    $fechaBaja = $documento['fecha_baja'] ?? '-';
    $usuario = $documento['usuario'] ?? [
        'nombres' => 'Cristian Fernando',
        'apellido' => 'Duran Gonzales',
        'carnet' => '6101010 LP',
        'area' => 'Inversiones Alternativas',
        'cargo' => 'Analista de Inversiones Alternativas',
        'email' => 'cristian@capitalsafi.com',
    ];
    $equipo = $documento['equipo'] ?? [
        'modelo' => 'Latitude 3500',
        'nombre' => 'BOCSIA06',
        'service_tag' => 'DB3BMW2',
        'fecha_entrega' => '1/4/2026',
        'fecha_recepcion' => '-',
        'sistema_operativo' => 'Windows 11 Pro',
    ];
    $software = $documento['software'] ?? [
        'sistemas' => 'Bflow - Usuario | Siegri - Usuario | Vlaferna - Usuario | Datex - Usuario | Infocred - Consultas',
        'microsoft' => 'Sin acceso | Office 365 E3 | Microsoft Fabric (Free) | Microsoft Power Automate Free | Power BI Pro | Power Automate Premium | Azure Active Directory Premium P1',
        'sitio_sharepoint' => 'Intranet | Inversiones Alternativas | Administración y Finanzas',
        'antivirus' => 'FortiClient - Inversiones_Alternativas',
        'internet' => 'Inversiones Alternativas (IA)',
        'colaboracion' => 'Teams',
        'escritorio_remoto' => '-',
        'mensajeria_instantanea' => '-',
        'unidad_compartida' => '-',
        'pdf' => 'Adobe Acrobat | PDFSamBasic',
        'vpn' => '-',
        'navegadores' => 'Edge | Chrome | Firefox',
        'software_vpn' => 'FortiClient',
        'soporte_remoto' => 'Anydesk',
        'impresora_scaner' => 'Canon 2525 | Epson WF-M5899',
        'compresion' => 'Winrar',
        'software_escaner' => 'NAPS2 | Color Network ScanGear | Epson Scan 2',
        'autenticacion_multifactor' => 'Microsoft Autenticator',
    ];
    $embedded = (bool) ($embedded ?? false);
@endphp
@unless ($embedded)
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Usuario</title>
@endunless
    <style>
        /* ====== Configuración de página tamaño carta con márgenes ====== */
        @page {
            size: letter;
            /* 21.59cm x 27.94cm */
            margin: 2cm 1cm 2cm 2.3cm;
            /* sup 2 / der 1 / inf 2 / izq 2.3 */
        }
        @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body { background-color: transparent; padding: 0; }
        .container {
            box-shadow: none;
            margin: 0;
            width: 100%;
            min-height: auto;
            padding: 0;
        }
    }

        :root {
            --plomo-titulo: #02348f;
            --plomo-header: #e2e1e1;
            /* barras "Datos del Usuario" / "EquiEpo Asignado" */
            --plomo-header2: #8A8A8A;
            --plomo-label: #e2e1e1;
            /* celdas de etiqueta (plomo claro) */
            --plomo-alta: #D0D0D0;
            /* resaltado del valor "Alta" / fechas */
            --borde: #b8b5b5;
            --texto: #1a1a1a;
        }

        * {
            box-sizing: border-box;
        }

@unless ($embedded)
        html,
        body {
            margin: 0;
            padding: 0;
            background: #f0f0f0;
            font-family: "Gisha", sans-serif;
            color: var(--texto);
        }
@endunless

        .formulario-alta-baja {
            font-family: "Gisha", sans-serif;
            color: var(--texto);
        }

        .formulario-alta-baja * {
            box-sizing: border-box;
        }

        /* Hoja simulada en pantalla (equivale al área imprimible dentro de los márgenes) */
        .hoja {
            width: 21.59cm;
            min-height: 27.94cm;
            margin: 20px auto;
            background: #fff;
            padding: 2cm 1cm 2cm 2.3cm;
            /* mismos márgenes en pantalla */
            box-shadow: 0 0 12px rgba(0, 0, 0, .15);
        }

        .hoja-embedded {
            margin: 0 auto;
            box-shadow: 0 0 8px rgba(0, 0, 0, .12);
        }

        /* ============ Encabezado ============ */
        .cabecera {
            display: flex;
            height: 40px;
            align-items: center;
            gap: 0px;
        }

        .logo {
            display: flex;
            align-items: center;
            min-width: 170px;
        }

        .logo img {
            max-width: 160px;
            max-height: 55px;
            margin-top: 25px;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
        }

        .titulo {
            flex: 1;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            color: var(--plomo-titulo);
            letter-spacing: 0.5px;
        }

        /* ============ Tabla superior (Tipo / Fechas) ============ */
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 9px;
        }

        td {
            border: 3px solid #ffffff;
            padding: 3px 6px;
            vertical-align: middle;
            height: 16px;
        }
       
        .tabla-tipo {
            width: 45%;
            margin-left: 220px;
            margin-right: auto;
            color: var(--plomo-titulo);
            /* alineada al centro */
            margin-bottom: 1.5px;
            border-collapse: separate;
            padding-right: 5px;
        }
  

        .tabla-tipo td.lbl {
            padding-right: 0.8cm;
            border: none;
            font-weight: 700;
            width: 45%;
            text-align: right;
        }

        .tabla-tipo td.val {
            padding-left: 0.4cm;
            border: 1.5px solid #ebe9e9;
            background: #ffffff;
            text-align: center;
            font-weight: 600;
            width: 55%;
        }

        /* ============ Barra de sección ============ */
        .barra {
            background: var(--plomo-header);
            color: #000000;
            font-weight : bold;
            font-size: 10px;
            text-align: center;
            padding: 4px;
            border-top: 1px Solid var(--borde);
            border-left: 1px Solid var(--borde);
            border-right: 1px Solid var(--borde);
            letter-spacing: .3px;
        }

        /* ============ Tabla de datos (4 columnas) ============ */
        .datos {
            border-collapse: separate;
            border-spacing: 0 2px;
            border-left: 1px Solid var(--borde);
            border-right: 1px Solid var(--borde);
            border-bottom: 1px Solid var(--borde);
            padding-right: 3px;
        }

        .datos td.lbl {
            padding-left: 0.5cm;
            border: none;
            font-weight: 700;
            width: 20.2%;
        }

        .datos td.val {
            padding-left: 0.4cm;
            border: 1.5px solid #ebe9e9;
            width: 27%;
        }

        /* ============ Sección de software (filas altas) ============ */
        .software {
            table-layout: fixed;
        }

        .software td.lbl {
            background: var(--plomo-label);
            border-left: 6px solid #ffffff; 
            padding-left: 0.5cm;
            font-weight: 700;
            width: 3.8cm;
            min-width: 3.8cm;
            max-width: 3.8cm;
            vertical-align: middle;
        }

        .software td.val {
            background: #ffffff;
            padding-left: 0.4cm;
            box-shadow: inset 0 0 0 1.5px #ebe9e9;
            width: 29%;
            vertical-align: middle;
            line-height: 1.35;
        }

        .software td {
            height: 50px;
        }

        /* ============ Firmas ============ */
        .firmas {
            display: flex;
            justify-content: space-around;
            gap: 40px;
            margin-top: 20px;
        }

        .firma {
            flex: 1;
            text-align: center;
            max-width: 45%;
        }

        .firma .caja {
            height: 90px;
            border: 1px dashed var(--borde);
            border-radius: 10px;
            margin-bottom: 6px;
        }

        .firma .rotulo {
            font-size: 9px;
            font-weight: 700;
            color: #333;
        }
    </style>
@unless ($embedded)
</head>

<body>
@endunless
    <div class="formulario-alta-baja">
    <div class="hoja{{ $embedded ? ' hoja-embedded' : '' }}">

        <!-- ENCABEZADO -->
        <div class="cabecera">
            <div class="logo">
                <img src="{{ asset('images/Capital-Safi-SA-logo-.png') }}" alt="Logo Capital Safi">
            </div>
            <div class="titulo">FORMULARIO DE USUARIO</div>
        </div>

        <!-- TABLA TIPO / FECHAS -->
        <table class="tabla-tipo">
            <tr>
                <td class="lbl">Tipo</td>
                <td class="val">{{ $tipo }}</td>
            </tr>
            <tr>
                <td class="lbl">Fecha Alta</td>
                <td class="val">{{ $fechaAlta }}</td>
            </tr>
            <tr>
                <td class="lbl">Fecha Baja</td>
                <td class="val">{{ $fechaBaja }}</td>
            </tr>
        </table>

        <!-- SECCIÓN: DATOS DEL USUARIO -->
        <div class="barra">Datos del Usuario</div>
        <table class="datos">
            <tr>
                <td class="lbl">Nombres:</td>
                <td class="val">{{ $usuario['nombres'] }}</td>
                <td class="lbl">&Aacute;rea:</td>
                <td class="val">{{ $usuario['area'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Apellido:</td>
                <td class="val">{{ $usuario['apellido'] }}</td>
                <td class="lbl">Cargo:</td>
                <td class="val">{{ $usuario['cargo'] }}</td>
            </tr>
            <tr>
                <td class="lbl">CI:</td>
                <td class="val">{{ $usuario['carnet'] }}</td>
                <td class="lbl">Correo Electr&oacute;nico:</td>
                <td class="val">{{ $usuario['email'] }}</td>
            </tr>
        </table>

        <!-- SECCIÓN: EQUIPO ASIGNADO -->
        <div class="barra" style="margin-top:1.5px;">Equipo Asignado</div>
        <table class="datos">
            <tr>
                <td class="lbl">Modelo:</td>
                <td class="val">{{ $equipo['modelo'] }}</td>
                <td class="lbl">Fecha de Entrega:</td>
                <td class="val">{{ $equipo['fecha_entrega'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Nombre del Equipo:</td>
                <td class="val">{{ $equipo['nombre'] }}</td>
                <td class="lbl">Fecha de Recepci&oacute;n:</td>
                <td class="val">{{ $equipo['fecha_recepcion'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Service Tag:</td>
                <td class="val">{{ $equipo['service_tag'] }}</td>
                <td class="lbl">Sistema Operativo:</td>
                <td class="val">{{ $equipo['sistema_operativo'] }}</td>
            </tr>
        </table>

        <!-- SECCIÓN SOFTWARE / CONFIGURACIÓN -->
        <table class="software" style="margin-top:1.5px;">
            <tr>
                <td class="lbl">Sistemas</td>
                <td class="val">{{ $software['sistemas'] }}</td>
                <td class="lbl">Microsoft</td>
                <td class="val">{{ $software['microsoft'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Sitio Sharepoint</td>
                <td class="val">{{ $software['sitio_sharepoint'] }}</td>
                <td class="lbl">Antivirus</td>
                <td class="val">{{ $software['antivirus'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Internet</td>
                <td class="val">{{ $software['internet'] }}</td>
                <td class="lbl">Colaboraci&oacute;n</td>
                <td class="val">{{ $software['colaboracion'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Escritorio Remoto</td>
                <td class="val">{{ $software['escritorio_remoto'] }}</td>
                <td class="lbl">Mensajer&iacute;a Instant&aacute;nea</td>
                <td class="val">{{ $software['mensajeria_instantanea'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Unidad Compartida</td>
                <td class="val">{{ $software['unidad_compartida'] }}</td>
                <td class="lbl">PDF</td>
                <td class="val">{{ $software['pdf'] }}</td>
            </tr>
            <tr>
                <td class="lbl">VPN</td>
                <td class="val">{{ $software['vpn'] }}</td>
                <td class="lbl">Navegadores</td>
                <td class="val">{{ $software['navegadores'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Software VPN</td>
                <td class="val">{{ $software['software_vpn'] }}</td>
                <td class="lbl">Soporte Remoto</td>
                <td class="val">{{ $software['soporte_remoto'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Impresora y Scaner</td>
                <td class="val">{{ $software['impresora_scaner'] }}</td>
                <td class="lbl">Compresi&oacute;n</td>
                <td class="val">{{ $software['compresion'] }}</td>
            </tr>
            <tr>
                <td class="lbl">Software Escaner</td>
                <td class="val">{{ $software['software_escaner'] }}</td>
                <td class="lbl">Autenticaci&oacute;n Multifactor</td>
                <td class="val">{{ $software['autenticacion_multifactor'] }}</td>
            </tr>
        </table>

        <!-- FIRMAS -->
        <div class="firmas">
            <div class="firma">
                <div class="caja"></div>
                <div class="rotulo">Gerente de &Aacute;rea</div>
            </div>
            <div class="firma">
                <div class="caja"></div>
                <div class="rotulo">Subgerente de Tecnolog&iacute;a de la Informaci&oacute;n</div>
            </div>
        </div>

    </div>
    </div>
@unless ($embedded)
</body>

</html>
@endunless
