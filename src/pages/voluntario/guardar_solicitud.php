<?php
/**
 * Guardar Solicitud - Endpoint para creación de nuevas solicitudes de herramientas
 *
 * Descripción:
 * Procesa el registro de nuevas solicitudes de herramientas por parte de voluntarios,
 * validando y almacenando la información en ambas tablas (solicitudes_herramientas y bodega_herramientas).
 * 
 * @package SAM Assistant
 * @version 1.1
 * @author Sistema SAM
 */

require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die(json_encode(['success' => false, 'error' => 'Método no permitido']));
}

// Obtener y sanitizar datos del formulario
$voluntario_id = $_POST['voluntarioid'] ?? '';
$nombre_item = trim($_POST['nombreitem'] ?? '');
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
$observaciones = trim($_POST['observaciones'] ?? '');

// Validaciones
if (empty($voluntario_id)) {
    header('HTTP/1.1 400 Bad Request');
    die(json_encode(['success' => false, 'error' => 'ID de voluntario requerido']));
}

if (empty($nombre_item)) {
    header('HTTP/1.1 400 Bad Request');
    die(json_encode(['success' => false, 'error' => 'Nombre del item requerido']));
}

if ($cantidad < 1) {
    header('HTTP/1.1 400 Bad Request');
    die(json_encode(['success' => false, 'error' => 'La cantidad debe ser un número positivo mayor a 0']));
}

try {
    // Iniciar transacción
    $conn->beginTransaction();

    // Insertar en tabla de voluntarios
    $stmt1 = $conn->prepare("INSERT INTO solicitudes_herramientas 
                           (nombreitem, cantidad, voluntarioid, observaciones, estado_entrega) 
                           VALUES (?, ?, ?, ?, 'Pendiente')");
    $stmt1->execute([$nombre_item, $cantidad, $voluntario_id, $observaciones]);
    $id_voluntario = $conn->lastInsertId();

    // Insertar en tabla de bodega
    $stmt2 = $conn->prepare("INSERT INTO bodega_herramientas 
                           (nombreitem, cantidad, voluntarioid, observaciones, estado_entrega) 
                           VALUES (?, ?, ?, ?, 'Pendiente')");
    $stmt2->execute([$nombre_item, $cantidad, $voluntario_id, $observaciones]);
    $id_bodega = $conn->lastInsertId();

    // Confirmar transacción
    $conn->commit();

    // Preparar respuesta
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'id' => $id_voluntario,
        'id_bodega' => $id_bodega
    ]);
    exit;

} catch(PDOException $e) {
    // Revertir transacción si está activa
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('HTTP/1.1 500 Internal Server Error');
    die(json_encode([
        'success' => false,
        'error' => 'Error en el servidor',
        'message' => 'No se pudo completar la solicitud'
    ]));
}