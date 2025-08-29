<?php
/**
 * Obtener Datos de Solicitud - Versión adaptada para bodega.php y voluntario.php
 *
 * Cambios principales:
 * - Acepta voluntario_id desde POST (para bodega) o sesión (para voluntario)
 * - Retorna datos estructurados consistentes
 * - Mantiene seguridad con consultas preparadas
 */

require_once './../../db/dbconn.php';
require_once './../../utils/session_check.php';

header('Content-Type: application/json');

$response = ['success' => false];

// Validar método y parámetros
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    $response['error'] = 'Solicitud inválida';
    echo json_encode($response);
    exit;
}

// Obtener parámetros
$id_solicitud = $_POST['id'];
$voluntario_id = $_POST['voluntario_id'] ?? $_SESSION['user_id'] ?? '';
$search_term = $_POST['search_term'] ?? '';

try {
    // Consulta preparada con selección explícita de campos
    $stmt = $conn->prepare("SELECT 
                           nombreitem, 
                           cantidad, 
                           observaciones,
                           estado_entrega,
                           estado_devolucion,
                           DATE_FORMAT(fecha_solicitud, '%d/%m/%Y %H:%i') as fecha_solicitud_formateada,
                           DATE_FORMAT(fecha_entregado, '%d/%m/%Y %H:%i') as fecha_entregado_formateada,
                           DATE_FORMAT(fecha_recibido, '%d/%m/%Y %H:%i') as fecha_recibido_formateada
                           FROM bodega_herramientas 
                           WHERE idsolicitud = ? 
                           AND voluntarioid = ?");
    
    $stmt->execute([$id_solicitud, $voluntario_id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($solicitud) {
        $response = [
            'success' => true,
            'data' => [
                'nombreitem' => $solicitud['nombreitem'],
                'cantidad' => $solicitud['cantidad'],
                'observaciones' => $solicitud['observaciones'] ?? '',
                'estado_entrega' => $solicitud['estado_entrega'] ?? 'Pendiente',
                'estado_devolucion' => $solicitud['estado_devolucion'] ?? '',
                'fecha_solicitud' => $solicitud['fecha_solicitud_formateada'],
                'fecha_entregado' => $solicitud['fecha_entregado_formateada'] ?? 'No entregado',
                'fecha_recibido' => $solicitud['fecha_recibido_formateada'] ?? 'No recibido'
            ],
            'meta' => [
                'id' => $id_solicitud,
                'voluntario_id' => $voluntario_id
            ]
        ];
    } else {
        $response['error'] = 'Solicitud no encontrada o no tienes permisos';
    }
} catch(PDOException $e) {
    error_log("Error en obtener_datos_solicitud.php: " . $e->getMessage());
    $response['error'] = 'Error en la base de datos';
}

echo json_encode($response);