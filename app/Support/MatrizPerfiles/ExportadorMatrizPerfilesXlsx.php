<?php

namespace App\Support\MatrizPerfiles;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga la matriz de perfiles como Excel (.xlsx), en una sola petición.
 *
 * Usa OpenSpout, que ya viene con Filament. La cabecera queda fija al
 * desplazarse, en negrita y con fondo; las celdas ajustan el texto para que
 * las listas separadas por " | " se lean sin ensanchar la columna.
 */
class ExportadorMatrizPerfilesXlsx
{
    private const ANCHO_CARGO = 38;

    private const ANCHO_COLUMNA = 45;

    public function __construct(private MatrizPerfiles $matriz) {}

    public function descargar(?string $nombre = null): StreamedResponse
    {
        $nombre ??= 'matriz-de-perfiles-'.now()
            ->timezone(config('app.display_timezone'))
            ->format('Y-m-d_His').'.xlsx';

        $cabeceras = $this->matriz->cabeceras();
        $filas = $this->matriz->filas();

        return response()->streamDownload(function () use ($cabeceras, $filas): void {
            $writer = new Writer($this->opciones(count($cabeceras)));
            $writer->openToFile('php://output');

            $hoja = $writer->getCurrentSheet();
            $hoja->setName('Matriz de perfiles');
            $hoja->setSheetView((new SheetView)->setFreezeRow(2)->setFreezeColumn('B'));

            $writer->addRow(Row::fromValues($cabeceras, $this->estiloCabecera()));

            foreach ($filas as $fila) {
                $writer->addRow(Row::fromValues($fila, $this->estiloFila()));
            }

            $writer->close();
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function opciones(int $columnas): Options
    {
        $opciones = new Options;
        $opciones->setColumnWidth(self::ANCHO_CARGO, 1);

        if ($columnas > 1) {
            $opciones->setColumnWidthForRange(self::ANCHO_COLUMNA, 2, $columnas);
        }

        return $opciones;
    }

    private function estiloCabecera(): Style
    {
        return (new Style)
            ->setFontBold()
            ->setFontColor('FFFFFF')
            ->setBackgroundColor('1F4E78')
            ->setShouldWrapText()
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER);
    }

    private function estiloFila(): Style
    {
        return (new Style)
            ->setShouldWrapText()
            ->setCellVerticalAlignment(CellVerticalAlignment::TOP);
    }
}
