<?php
/**
 * Marcar Prestado - Endpoint para gestión de estados de préstamos
 *
 * Modificado para funcionar con bodega.php:
 * - Ahora recibe voluntario_id desde POST en lugar de usar el de sesión
 * - Compatible con el sistema de búsqueda de voluntarios
 * - Mantiene el término de búsqueda para redirección
 */

require_once './../../db/dbconn.php';
require './../../utils/session_check.php';

header('Content-Type: application/json');

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Manejar entrega total de múltiples items
if (isset($_POST['accion']) && $_POST['accion'] === 'entrega_total' && isset($_POST['ids'], $_POST['voluntario_id'])) {
    try {
        $voluntario_id = $_POST['voluntario_id'];
        $search_term = $_POST['search_term'] ?? '';
        $ids = is_array($_POST['ids']) ? $_POST['ids'] : explode(',', $_POST['ids']);
        
        // Validar IDs
        if (empty($ids)) {
            throw new Exception('No se proporcionaron IDs válidos');
        }

        // Crear placeholders para la consulta
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        // Actualizar todos los items a la vez
        $stmt = $conn->prepare("UPDATE bodega_herramientas 
                              SET estado_entrega = 'Entregado', fecha_entregado = NOW() 
                              WHERE idsolicitud IN ($placeholders) 
                              AND voluntarioid = ?");
                              
        // Combinar parámetros (IDs + voluntario_id)
        $params = array_merge($ids, [$voluntario_id]);
        $stmt->execute($params);
        
        echo json_encode([
            'success' => true, 
            'count' => $stmt->rowCount(),
            'search_term' => $search_term
        ]);
    } catch(PDOException $e) {
        error_log("Error en entrega total: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al procesar la entrega total']);
    } catch(Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Manejo individual
if (isset($_POST['id'], $_POST['estado'], $_POST['voluntario_id'])) {
    $id = $_POST['id'];
    $estado = $_POST['estado'];
    $voluntario_id = $_POST['voluntario_id'];
    $search_term = $_POST['search_term'] ?? '';

    try {
        // Validar estado
        if (!in_array($estado, ['Entregado', 'Pendiente'])) {
            throw new Exception('Estado no válido');
        }

        // Actualizar según el estado
        if ($estado === 'Entregado') {
            $stmt = $conn->prepare("UPDATE bodega_herramientas 
                                  SET estado_entrega = ?, fecha_entregado = NOW() 
                                  WHERE idsolicitud = ? 
                                  AND voluntarioid = ?");
        } else {
            $stmt = $conn->prepare("UPDATE bodega_herramientas 
                                  SET estado_entrega = ?, fecha_entregado = NULL 
                                  WHERE idsolicitud = ? 
                                  AND voluntarioid = ?");
        }
        
        $stmt->execute([$estado, $id, $voluntario_id]);
        
        echo json_encode([
            'success' => true,
            'updated' => $stmt->rowCount(),
            'new_state' => $estado,
            'search_term' => $search_term
        ]);
    } catch(PDOException $e) {
        error_log("Error al actualizar estado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al actualizar el estado']);
    } catch(Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Si no se cumplió ninguno de los casos anteriores
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);