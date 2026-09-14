@php
    /** @var list<array{nombre_completo: string, area: string, cargo: string, correo: string, celular: string}> $usuarios */
    $usuarios = $usuarios ?? [
        [
            'nombre_completo' => 'Jorge Julio Walter Quintanilla Nielsen',
            'area' => 'Gerencia',
            'cargo' => 'Gerente General',
            'correo' => 'jorge@capitalsafi.com',
            'celular' => '+591 69711132',
        ],
        [
            'nombre_completo' => 'Eduardo Mauricio Gabriel Arana Lema',
            'area' => 'Asesoria Legal',
            'cargo' => 'Asesor Legal',
            'correo' => 'eduardo@capitalsafi.com',
            'celular' => '+591 69711130',
        ],
        [
            'nombre_completo' => 'Valeria Andrea Alejandra Torrico Salazar',
            'area' => 'Asesoria Legal',
            'cargo' => 'Asistente Legal',
            'correo' => 'valeria@capitalsafi.com',
            'celular' => '+591 69711129',
        ],
        [
            'nombre_completo' => 'Grover Alejandro Delgado Escobar',
            'area' => 'Inversiones Alternativas',
            'cargo' => 'Presidente de Directorio / Representante Legal',
            'correo' => 'alejandro@capitalsafi.com',
            'celular' => '+591 69711131',
        ],
        [
            'nombre_completo' => 'Rodrigo Javier Gustavo Moreno Villamor',
            'area' => 'Inversiones Alternativas',
            'cargo' => 'Vicepresidente de Inversiones Alternativas',
            'correo' => 'rodrigo@capitalsafi.com',
            'celular' => '+51 959111057',
        ],
        [
            'nombre_completo' => 'Juan Carlos Navarro Rossetti',
            'area' => 'Inversiones Alternativas',
            'cargo' => 'Vicepresidente de Inversiones Alternativas',
            'correo' => 'juancarlos@capitalsafi.com',
            'celular' => '+591 69711133',
        ],
        [
            'nombre_completo' => 'Cristian Fernando Duran Gonzales',
            'area' => 'Inversiones Alternativas',
            'cargo' => 'Analista de Inversiones Alternativas',
            'correo' => 'cristian@capitalsafi.com',
            'celular' => '+591 69711134',
        ],
        [
            'nombre_completo' => 'Maria Fernanda Lopez Rojas',
            'area' => 'Administración y Finanzas',
            'cargo' => 'Jefe de Administración',
            'correo' => 'maria@capitalsafi.com',
            'celular' => '+591 69711135',
        ],
        [
            'nombre_completo' => 'Andres Felipe Mendoza Cruz',
            'area' => 'Tecnología',
            'cargo' => 'Analista de Sistemas',
            'correo' => 'andres@capitalsafi.com',
            'celular' => '+591 69711136',
        ],
        [
            'nombre_completo' => 'Patricia Elena Vargas Soto',
            'area' => 'Cumplimiento',
            'cargo' => 'Oficial de Cumplimiento',
            'correo' => 'patricia@capitalsafi.com',
            'celular' => '+591 69711137',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuarios</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 24px;
            background-color: #555;
            color: #222;
            display: flex;
            justify-content: center;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Carta 8.5 × 11 in (816 × 1056 px @ 96 dpi) */
        .container {
            width: 816px;
            min-height: 1056px;
            background-color: #fff;
            /* Preview: superior 2cm, derecho 1cm, inferior 2cm, izquierdo 2.5cm */
            padding: 2cm 1cm 2cm 2.5cm;
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.45);
        }

        .cabecera {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            gap: 16px;
        }

        .logo img {
            max-width: 350px;
            max-height: 55px;
            width: auto;
            height: auto;
            display: block;
            object-fit: contain;
        }

        .titulo {
            flex: 1;
            text-align: center;
            font-size: 33px;
            font-weight: 700;
            color: #003366;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            line-height: 1.1;
            white-space: nowrap;
        }

        .tabla-usuarios {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: none;
        }

        .tabla-usuarios th,
        .tabla-usuarios td {
            border: none;
           
            padding-left: 10px;
            vertical-align: middle;
            text-align: left;
            word-wrap: break-word;
        }

        /* Número fuera del borde, pegado a Nombre completo */
        .col-num {
            width: 22px;
            padding: 8px 4px 8px 0 !important;
            text-align: right !important;
            color: #000 !important;
            font-weight: 700 !important;
            font-size: 8px !important;
            background: transparent !important;
            border: none !important;
            white-space: nowrap;
        }

        thead .col-num {
            color: transparent !important;
            background: transparent !important;
            border: none !important;
        }

        .tabla-usuarios thead th:not(.col-num) {
            background-color: #003366;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
            border-top: 1px solid #000;
            border-right: 1.5px solid #fff;
            border-bottom: none;
        }

        .tabla-usuarios thead th.col-nombre {
            border-left: 1px solid #000;
        }

        .tabla-usuarios thead th.col-celular {
            border-right: 1px solid #000;
        }

        .tabla-usuarios tbody td:not(.col-num) {
            border-left: none;
            border-right: none;
            border-top: none;
            border-bottom: none;
        }

        .tabla-usuarios tbody td.col-nombre {
            border-left: 1px solid #000;
        }

        .tabla-usuarios tbody td.col-celular {
            border-right: 1px solid #000;
        }

        .tabla-usuarios tbody tr:last-child td:not(.col-num) {
            border-bottom: 1px solid #000;
        }

        .col-nombre { width: 26%; }
        .col-area { width: 16%; }
        .col-cargo { width: 26%; }
        .col-correo { width: 18%; }
        .col-celular { width: 14%; color: #003366; }

        .correo {
            color: #003366;
            text-decoration: none;
        }

        tbody tr td:not(.col-num) {
            color: #7e7c7c;
            font-size: 8px;
        }

        /* En PDF los márgenes los aplica Browsershot (2 / 1 / 2 / 2.5 cm). */
        @media print {
            body {
                background: #fff;
                padding: 0;
                display: block;
            }

            .container {
                width: 100%;
                min-height: auto;
                box-shadow: none;
                padding: 0;
            }
        }
        
    </style>
</head>
<body>
    <div class="container">
        <div class="cabecera">
            <div class="logo">
                <img src="{{ asset('images/Capital-Safi-SA-logo-.png') }}" alt="Logo Capital Safi">
            </div>
            <div class="titulo">Lista de Usuarios</div>
        </div>

        <table class="tabla-usuarios">
            <thead>
                <tr>
                    <th class="col-num" aria-hidden="true">&nbsp;</th>
                    <th class="col-nombre">Nombre completo</th>
                    <th class="col-area">Área</th>
                    <th class="col-cargo">Cargo</th>
                    <th class="col-correo">Correo</th>
                    <th class="col-celular">Celular</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $index => $usuario)
                    <tr>
                        <td class="col-num">{{ $index + 1 }}</td>
                        <td class="col-nombre">{{ $usuario['nombre_completo'] }}</td>
                        <td class="col-area">{{ $usuario['area'] }}</td>
                        <td class="col-cargo">{{ $usuario['cargo'] }}</td>
                        <td class="col-correo">
                            @if (filled($usuario['correo']) && $usuario['correo'] !== '—')
                                <a class="correo" href="mailto:{{ $usuario['correo'] }}">{{ $usuario['correo'] }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="col-celular">{{ $usuario['celular'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="col-num">&nbsp;</td>
                        <td class="col-nombre" colspan="5">No hay usuarios de alta para listar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
