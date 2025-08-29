<?php
/**
 * Marcar No Devuelto - Endpoint para gestión de estados de devolución
 *
 * Modificado para funcionar con bodega.php:
 * - Ahora recibe voluntario_id desde POST en lugar de usar el de sesión
 * - Compatible con el sistema de búsqueda de voluntarios
 * - Mantiene el término de búsqueda para redirección
 */

require_once './../../db/dbconn.php';
require './../../utils/session_check.php';

header('Content-Type: application/json');

// Validar método y parámetros
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'], $_POST['voluntario_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Solicitud inválida - parámetros faltantes']);
    exit;
}

$id = $_POST['id'];
$voluntario_id = $_POST['voluntario_id'];
$search_term = $_POST['search_term'] ?? '';

try {
    // Verificar si la solicitud existe y pertenece al voluntario
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bodega_herramientas 
                           WHERE idsolicitud = ? AND voluntarioid = ?");
    $stmt->execute([$id, $voluntario_id]);
    
    if ($stmt->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada o no autorizada']);
        exit;
    }

    // Actualizar estado a "No devuelto"
    $stmt = $conn->prepare("UPDATE bodega_herramientas 
                          SET estado_devolucion = 'No devuelto',
                              estado_entrega = 'Entregado'
                          WHERE idsolicitud = ? 
                          AND voluntarioid = ?");
    
    $stmt->execute([$id, $voluntario_id]);
    $rowsAffected = $stmt->rowCount();
    
    if ($rowsAffected > 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Item marcado como no devuelto',
            'new_state' => 'No devuelto',
            'search_term' => $search_term
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el estado']);
    }
    
} catch(PDOException $e) {
    error_log("Error al marcar como no devuelto: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor: ' . $e->getMessage()]);
}