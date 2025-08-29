<?php
/**
 * Eliminar Todos los Prestados - Endpoint para limpieza masiva de herramientas
 *
 * Modificado para funcionar con bodega.php:
 * - Ahora recibe voluntario_id desde POST en lugar de usar el de sesión
 * - Compatible con el sistema de búsqueda de voluntarios
 */

require_once './../../db/dbconn.php';
require './../../utils/session_check.php';

// Configurar cabeceras para respuesta JSON
header('Content-Type: application/json');

// Verificar método POST y parámetros requeridos
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['voluntario_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Solicitud inválida - parámetros faltantes']);
    exit();
}

$voluntario_id = $_POST['voluntario_id'];
$search_term = $_POST['search_term'] ?? '';

try {
    // Verificar que hay items para eliminar
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bodega_herramientas 
                           WHERE voluntarioid = ? 
                           AND estado_entrega = 'Entregado'
                           AND (estado_devolucion IS NULL OR estado_devolucion = '' OR estado_devolucion = 'Devuelto')");
    $stmt->execute([$voluntario_id]);
    $count = $stmt->fetchColumn();
    
    if ($count === 0) {
        echo json_encode(['success' => true, 'message' => 'No hay items prestados para eliminar', 'deleted' => 0]);
        exit();
    }
    
    // Eliminar todos los items prestados del voluntario
    $stmt = $conn->prepare("DELETE FROM bodega_herramientas 
                           WHERE voluntarioid = ? 
                           AND estado_entrega = 'Entregado'
                           AND (estado_devolucion IS NULL OR estado_devolucion = '' OR estado_devolucion = 'Devuelto')");
    $stmt->execute([$voluntario_id]);
    
    // Verificar cuántas filas fueron afectadas
    $rowsAffected = $stmt->rowCount();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Items eliminados correctamente', 
        'deleted' => $rowsAffected,
        'search_term' => $search_term // Mantenemos el término de búsqueda para redirección
    ]);
} catch(PDOException $e) {
    error_log("Error al eliminar items prestados: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al eliminar items prestados']);
}
?>