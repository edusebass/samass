<?php
/**
 * Actualizar Solicitud - Endpoint para modificación de solicitudes existentes
 *
 * Modificado para funcionar con bodega.php:
 * - Ahora recibe voluntario_id desde POST en lugar de usar el de sesión
 * - Compatible con el sistema de búsqueda de voluntarios
 */

require_once './../../db/dbconn.php';
require_once './../../utils/session_check.php';

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idsolicitud'], $_POST['voluntario_id'])) {
    try {
        // Validar campos obligatorios
        if (empty($_POST['nombreitem'])) {
            throw new Exception('El nombre del item es requerido');
        }
        
        if (!isset($_POST['cantidad']) || $_POST['cantidad'] <= 0) {
            throw new Exception('La cantidad debe ser un número positivo');
        }

        $stmt = $conn->prepare("UPDATE bodega_herramientas 
                               SET nombreitem = ?, 
                                   cantidad = ?, 
                                   observaciones = ?
                               WHERE idsolicitud = ? 
                               AND voluntarioid = ?");
        $stmt->execute([
            $_POST['nombreitem'],
            (int)$_POST['cantidad'], // Aseguramos que sea entero
            $_POST['observaciones'] ?? null, // Usamos null si no hay observaciones
            $_POST['idsolicitud'],
            $_POST['voluntario_id'] // Usamos el ID del voluntario del formulario
        ]);
        
        // Verificar si se actualizó algún registro
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
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