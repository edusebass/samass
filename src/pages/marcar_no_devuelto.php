<?php
require_once './../db/dbconn.php';
require './../utils/session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Método no permitido');
}

$id = $_POST['id'] ?? '';
$voluntario_id = $_SESSION['user_id'];

if (empty($id)) {
    header('HTTP/1.1 400 Bad Request');
    die('ID no válido');
}

try {
    // Actualizar solo el estado de devolución (sin cambiar estado_entrega)
    $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                          SET estado_devolucion = 'No devuelto'
                          WHERE idsolicitud = ? 
                          AND voluntarioid = ?");
    
    $stmt->execute([$id, $voluntario_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se actualizó ningún registro']);
    }
} catch(PDOException $e) {
    error_log("Error al marcar como no devuelto: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
}