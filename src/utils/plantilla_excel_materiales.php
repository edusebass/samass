<?php
require './../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Plantilla Inventario');

// Encabezados según tu formato
$headers = [
    'A1' => 'Código del elemento',
    'B1' => 'Nombre del elemento',
    'C1' => 'Descripción del elemento',
    'D1' => 'Estado Actual',
    'E1' => 'Cantidad en existencia',
    'F1' => 'Costo unitario',
    'G1' => 'Tipo de elemento',
    'H1' => 'Lugar donde se lo almacena/usa',
    'I1' => 'Fecha de adquisición',
    'J1' => 'Tiempo de uso',
    'K1' => 'Tiempo de vida útil',
    'L1' => 'Valor residual',
    'M1' => 'Costo de mantenimiento mensual',
    'N1' => 'Fotografías.',
    'O1' => 'Observaciones'
];

foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Descargar el archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="plantilla_inventario.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;