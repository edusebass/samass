<?php
require './../../vendor/autoload.php';
require_once './../db/dbconn.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function limpiar_decimal($valor) {
    // Elimina $ y puntos de mil, cambia coma por punto
    $valor = str_replace(['$', ','], ['', '.'], $valor);
    return is_numeric($valor) ? $valor : 0;
}

function limpiar_entero($valor) {
    return is_numeric($valor) ? intval($valor) : 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    // Saltar encabezado
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (empty($row[0])) continue; // Si no hay código, saltar

        // Limpiar valores decimales
        $costo = limpiar_decimal($row[5]);
        $valor_residual = limpiar_decimal($row[11]);
        $costo_mantenimiento = limpiar_decimal($row[12]);

        $data = [
            'codigo' => $row[0],
            'nombre' => $row[1],
            'descripcion' => $row[2],
            'estado_id' => limpiar_entero($row[3]),
            'cantidad' => limpiar_entero($row[4]),
            'costo' => $costo,
            'elemento_id' => limpiar_entero($row[6]),
            'seccion_id' => limpiar_entero($row[7]),
            'fecha' => $row[8],
            'uso' => limpiar_entero($row[9]),
            'vida' => limpiar_entero($row[10]),
            'valor_residual' => $valor_residual,
            'costo_mantenimiento' => $costo_mantenimiento,
            'foto_path' => $row[13],
            'observaciones' => $row[14],
        ];

        $sql = "INSERT INTO items 
            (codigo, nombre, descripcion, estado_id, cantidad, costo, elemento_id, seccion_id, fecha, uso, vida, valor_residual, costo_mantenimiento, foto_path, observaciones)
            VALUES
            (:codigo, :nombre, :descripcion, :estado_id, :cantidad, :costo, :elemento_id, :seccion_id, :fecha, :uso, :vida, :valor_residual, :costo_mantenimiento, :foto_path, :observaciones)
            ON DUPLICATE KEY UPDATE 
            nombre=VALUES(nombre), descripcion=VALUES(descripcion), estado_id=VALUES(estado_id), cantidad=VALUES(cantidad), costo=VALUES(costo), elemento_id=VALUES(elemento_id), seccion_id=VALUES(seccion_id), fecha=VALUES(fecha), uso=VALUES(uso), vida=VALUES(vida), valor_residual=VALUES(valor_residual), costo_mantenimiento=VALUES(costo_mantenimiento), foto_path=VALUES(foto_path), observaciones=VALUES(observaciones)";

        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
    }

    header('Location: ../pages/inventario.php?import=ok');
    exit;
} else {
    echo "No se subió ningún archivo.";
}