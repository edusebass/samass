<?php
require './../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once './../db/dbconn.php';

function obtener_inventario($conn) {
    $rol = $_SESSION['rol'];
    if ($rol == 2) {
        $query = "SELECT iditems, nombre, descripcion, estado_id, estado.descripcion, uso, seccion_id, ROUND(uso / 60) AS uso_minutos, observaciones, cantidad 
                  FROM items 
                  JOIN estado ON items.estado_id = estado.idestado WHERE seccion_id = 6;";
    } else {
        $query = "SELECT iditems, nombre, descripcion, estado_id, estado.descripcion, uso, seccion_id, ROUND(uso / 60) AS uso_minutos, observaciones, cantidad 
                  FROM items 
                  JOIN estado ON items.estado_id = estado.idestado;";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$inventario = obtener_inventario($conn);

// Crear nuevo archivo de Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Inventario');

// Establecer los encabezados
$sheet->setCellValue('A1', 'Nombre');
$sheet->setCellValue('B1', 'Descripción');
$sheet->setCellValue('C1', 'Estado');
$sheet->setCellValue('D1', 'Cantidad');
$sheet->setCellValue('E1', 'Tiempo de uso');
$sheet->setCellValue('F1', 'Observaciones');

// Llenar los datos en el archivo Excel
$row = 2;
foreach ($inventario as $item) {
    $sheet->setCellValue('A' . $row, $item['nombre']);
    $sheet->setCellValue('B' . $row, $item['descripcion']);
    $sheet->setCellValue('C' . $row, $item['descripcion']);
    $sheet->setCellValue('D' . $row, $item['cantidad']);
    $sheet->setCellValue('E' . $row, round($item['uso'] / 60, 1) . ' horas');
    $sheet->setCellValue('F' . $row, $item['observaciones']);
    $row++;
}

// Definir el nombre del archivo Excel
$filename = 'inventario_' . date('Y-m-d') . '.xlsx';

// Enviar las cabeceras para que el archivo se descargue correctamente
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0'); // No usar caché

// Limpiar el búfer de salida y forzar la descarga
ob_clean();
flush();

// Escribir el archivo Excel directamente a la salida del navegador
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
