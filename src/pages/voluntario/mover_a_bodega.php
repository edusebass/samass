<?php
require_once './../../db/dbconn.php';

$item_id = $_POST['item_id'];
$voluntario_id = $_POST['voluntario_id'];
$search_term = $_POST['search_term'] ?? '';

try {
    // Obtener los datos de la solicitud
    $stmt = $conn->prepare("SELECT * FROM solicitudes_herramientas WHERE idsolicitud = ? AND voluntarioid = ?");
    $stmt->execute([$item_id, $voluntario_id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($solicitud) {
        // Insertar en bodega_herramientas
        $stmt = $conn->prepare("INSERT INTO bodega_herramientas 
                              (nombreitem, cantidad, voluntarioid, fecha_solicitud, estado_entrega, observaciones)
                              VALUES (?, ?, ?, ?, 'Entregado', ?)");
        $stmt->execute([
            $solicitud['nombreitem'],
            $solicitud['cantidad'],
            $solicitud['voluntarioid'],
            $solicitud['fecha_solicitud'],
            $solicitud['observaciones']
        ]);
        
        // Eliminar de solicitudes_herramientas
        $stmt = $conn->prepare("DELETE FROM solicitudes_herramientas WHERE idsolicitud = ?");
        $stmt->execute([$item_id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Solicitud no encontrada']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>