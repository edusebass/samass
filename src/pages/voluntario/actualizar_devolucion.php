<?php
/**
 * Actualizar Devolución - Endpoint para gestión de estados de devolución
 *
 * Modificado para funcionar con bodega.php:
 * - Ahora recibe voluntario_id desde POST en lugar de usar el de sesión
 * - Compatible con el sistema de búsqueda de voluntarios
 */

require_once './../../db/dbconn.php';
require_once './../../utils/session_check.php';

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['estado'], $_POST['voluntario_id'])) {
    try {
        // Validar el estado recibido
        $estadosPermitidos = ['Devuelto', 'Entregado', 'No devuelto', 'Perdido'];
        if (!in_array($_POST['estado'], $estadosPermitidos)) {
            throw new Exception('Estado no válido');
        }

        $stmt = $conn->prepare("UPDATE bodega_herramientas 
                              SET estado_devolucion = ?,
                                  estado_entrega = CASE
                                      WHEN ? = 'Devuelto' THEN 'Entregado'
                                      ELSE estado_entrega
                                  END,
                                  fecha_recibido = CASE 
                                      WHEN ? = 'Devuelto' THEN NOW() 
                                      ELSE NULL 
                                  END
                              WHERE idsolicitud = ? 
                              AND voluntarioid = ?");
        $stmt->execute([
            $_POST['estado'],
            $_POST['estado'],
            $_POST['estado'],
            $_POST['id'],
            $_POST['voluntario_id'] // Usamos el ID del voluntario que viene del formulario
        ]);
        
        // Verificar si se actualizó algún registro
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['new_state'] = $_POST['estado'];
        } else {
            $response['error'] = 'No se encontró la solicitud o no tienes permisos';
        }
    } catch(PDOException $e) {
        $response['error'] = 'Error al actualizar: ' . $e->getMessage();
    } catch(Exception $e) {
        $response['error'] = $e->getMessage();
    }
} else {
    $response['error'] = 'Solicitud inválida - parámetros faltantes';
}

echo json_encode($response);
?>