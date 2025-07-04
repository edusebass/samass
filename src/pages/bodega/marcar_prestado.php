/**
 * Marcar Prestado - Gestión de préstamos de items
 * 
 * Script para marcar items como prestados y gestionar
 * las operaciones de préstamo en bodega.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
require_once './../../db/dbconn.php';
require './../../utils/session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido');
}

// Manejar entrega total de múltiples items
if (isset($_POST['accion']) && $_POST['accion'] === 'entrega_total' && !empty($_POST['ids'])) {
    try {
        // Convertir IDs a array seguro para la consulta
        $ids = $_POST['ids'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Actualizar todos los items a la vez
        $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                              SET estado_entrega = 'Entregado', fecha_entregado = NOW() 
                              WHERE idsolicitud IN ($placeholders) 
                              AND voluntarioid = ?");
                              
        // Agregar los IDs y el ID del voluntario al array de parámetros
        $params = array_merge($ids, [$_SESSION['user_id']]);
        $stmt->execute($params);
        
        echo json_encode(['success' => true, 'count' => $stmt->rowCount()]);
    } catch(PDOException $e) {
        error_log("Error en entrega total: " . $e->getMessage());
        die(json_encode(['success' => false, 'error' => 'Error al procesar']));
    }
    exit;
}

// Manejo individual (código existente)
$id = $_POST['id'] ?? '';
$estado = $_POST['estado'] ?? '';

if (empty($id)) {
    die('ID no válido');
}

try {
    // Si el estado es "Entregado", actualizamos también la fecha_entregado
    if ($estado === 'Entregado') {
        $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                              SET estado_entrega = ?, fecha_entregado = NOW() 
                              WHERE idsolicitud = ? 
                              AND voluntarioid = ?");
    } else {
        // Si es "Pendiente", solo actualizamos el estado (y opcionalmente limpiar fecha_entregado)
        $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                              SET estado_entrega = ?, fecha_entregado = NULL 
                              WHERE idsolicitud = ? 
                              AND voluntarioid = ?");
    }
    
    $stmt->execute([$estado, $id, $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo 'OK';
    } else {
        echo 'No se actualizó ningún registro';
    }
} catch(PDOException $e) {
    error_log("Error al actualizar estado: " . $e->getMessage());
    die("Error al actualizar estado");
}