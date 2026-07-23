{{-- Estilos compartidos para formularios PDF de equipos --}}
<style>
    * { box-sizing: border-box; }
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
    .header-table { width: 100%; border: none; border-collapse: collapse; }
    .header-logo { width: 25%; vertical-align: top; padding-top: 5px; }
    .header-logo img { max-width: 160px; height: auto; }
    .header-title {
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        color: #003366;
        width: 100%;
        margin-bottom: 12px;
    }
    .header-info-table { width: 101.5%; border-collapse: collapse; }
    .header-info-table td { vertical-align: middle; }
    .header-info-label {
        width: 127px;
        color: #003366;
        font-weight: bold;
        text-align: right;
        padding-right: 15px;
    }
    .header-info-cell { padding: 2px 5px 2px 1px; vertical-align: middle; }
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
    .col-content { width: 75%; padding: 1px 4px; vertical-align: middle; }
    .inner-table { margin-left: 6px; width: 100%; border-collapse: collapse; }
    .inner-table td { padding: 2.5px 6px; vertical-align: middle; }
    .inner-label {
        width: 145px;
        color: #003366;
        font-weight: bold;
        padding-left: 5px;
    }
    .inner-value-container { padding-right: 5px; }
    .inner-input {
        border: 1.8px solid #dad8d8;
        padding: 2.5px 8px;
        min-height: 14px;
        background-color: #fff;
        width: 100%;
        color: #000;
        font-size: 10px;
    }
    .centered-text { text-align: center; padding: 8px; }
    .nota-text { text-align: center; padding: 8px 15px; line-height: 1.4; font-size: 10px; }
    .signatures-table { width: 100%; margin-top: 10px; margin-bottom: 20px; border: none; }
    .signature-cell { width: 50%; text-align: center; vertical-align: top; }
    .signature-cell-third { width: 33.33%; text-align: center; vertical-align: top; }
    .signature-box {
        width: 220px;
        height: 70px;
        border: 1.5px dashed #888;
        border-radius: 10px;
        margin: 0 auto 8px auto;
    }
    .signature-name { font-weight: bold; font-size: 10px; margin: 0; line-height: 1.2; }
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
        @page {
            size: letter;
            margin: 2cm 1cm 2cm 2.5cm;
        }
    }
</style>
