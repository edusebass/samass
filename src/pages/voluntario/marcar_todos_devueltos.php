<?php
/**
 * Marcar Todos Devueltos - Endpoint para actualización masiva de devoluciones
 *
 * Modificado para funcionar con bodega.php:
 * - Ahora recibe voluntario_id desde POST en lugar de usar el de sesión
 * - Compatible con el sistema de búsqueda de voluntarios
 * - Mantiene el término de búsqueda para redirección
 * - Excluye items con estado "No devuelto" o "Perdido"
 */

require_once './../../db/dbconn.php';
require './../../utils/session_check.php';

header('Content-Type: application/json');

// Validar parámetros requeridos
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['voluntario_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

$voluntario_id = $_POST['voluntario_id'];
$search_term = $_POST['search_term'] ?? '';

try {
    // Primero contar cuántos registros cumplen los criterios
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bodega_herramientas 
                           WHERE voluntarioid = ? 
                           AND estado_entrega = 'Entregado'
                           AND (estado_devolucion IS NULL 
                               OR (estado_devolucion != 'Devuelto' 
                                   AND estado_devolucion != 'No devuelto' 
                                   AND estado_devolucion != 'Perdido'))");
    $stmt->execute([$voluntario_id]);
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        echo json_encode([
            'success' => true, 
            'updated' => 0,
            'message' => 'No hay items pendientes de marcar como devueltos',
            'search_term' => $search_term
        ]);
        exit;
    }

    // Actualizar todos los items elegibles (excluyendo "No devuelto" y "Perdido")
    $stmt = $conn->prepare("UPDATE bodega_herramientas 
                           SET estado_devolucion = 'Devuelto', 
                               fecha_recibido = NOW() 
                           WHERE voluntarioid = ? 
                           AND estado_entrega = 'Entregado'
                           AND (estado_devolucion IS NULL 
                               OR (estado_devolucion != 'No devuelto' 
                                   AND estado_devolucion != 'Perdido'))");
    
    $stmt->execute([$voluntario_id]);
    $rowsAffected = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'updated' => $rowsAffected,
        'search_term' => $search_term
    ]);
    
} catch(PDOException $e) {
    error_log("Error en marcar_todos_devueltos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al actualizar los registros']);
}