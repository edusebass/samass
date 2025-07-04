/**
 * Guardar Solicitud - Procesamiento de solicitudes de compra
 * 
 * Script para procesar y guardar solicitudes de compra
 * enviadas por los usuarios del sistema.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../usuarios/voluntario.php');
    exit;
}

// Obtener datos del formulario
$voluntario_id = $_POST['voluntarioid'] ?? '';
$nombre_item = $_POST['nombreitem'] ?? '';
$cantidad = $_POST['cantidad'] ?? 1;
$observaciones = $_POST['observaciones'] ?? '';

// Validaciones básicas
if (empty($voluntario_id) || empty($nombre_item)) {
    die('Faltan campos obligatorios');
}

if (!is_numeric($cantidad) || $cantidad < 1) {
    die('La cantidad debe ser un número positivo');
}

try {
    // Insertar la nueva solicitud (el ID será autoincremental)
    $stmt = $conn->prepare("INSERT INTO solicitudes_herramientas 
                          (nombreitem, cantidad, voluntarioid, observaciones, estado_entrega) 
                          VALUES (?, ?, ?, ?, 'Pendiente')");
    $stmt->execute([
        $nombre_item,
        $cantidad,
        $voluntario_id,
        $observaciones
    ]);
    
    // Obtener el ID generado automáticamente
    $solicitud_id = $conn->lastInsertId();
    
    // Respuesta para AJAX
    echo json_encode(['success' => true, 'id' => $solicitud_id]);
    exit;
    
} catch(PDOException $e) {
    http_response_code(500);
    die('Error al guardar la solicitud: ' . $e->getMessage());
}