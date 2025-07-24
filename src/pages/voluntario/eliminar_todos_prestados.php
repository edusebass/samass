<?php
/**
 * Eliminar Todos los Prestados - Endpoint para limpieza masiva de herramientas
 *
 * Descripción:
 * Script API que maneja la eliminación masiva de herramientas marcadas como entregadas/devueltas
 * pertenecientes al voluntario autenticado. Opera como acción final tras aprobación de entrega.
 *
 * Funcionalidades clave:
 * - Eliminación masiva segura de múltiples registros
 * - Verificación exhaustiva de condiciones previas
 * - Validación de existencia de registros a eliminar
 * - Conteo preciso de registros afectados
 * - Manejo robusto de errores y códigos HTTP
 *
 * Criterios de selección:
 * - Items pertenecientes al voluntario actual
 * - Con estado_entrega = 'Entregado'
 * - Con estado_devolucion NULL, vacío o 'Devuelto'
 *
 * Seguridad:
 * - Autenticación obligatoria via sesión
 * - Exclusivo método POST
 * - Restricción por voluntario_id
 * - Consultas preparadas
 * - Headers HTTP seguros
 *
 * Respuestas:
 * - 200 OK (éxito):
 *   - JSON: {success: true, message: "OK", deleted: X}
 * - 200 OK (sin items):
 *   - Texto: "No hay items prestados para eliminar"
 * - 400 Bad Request:
 *   - Texto: "Solicitud inválida"
 * - 401 Unauthorized:
 *   - Texto: "Acceso no autorizado"
 * - 500 Internal Server Error:
 *   - JSON: {success: false, error: "..."}
 *
 * Flujo de operación:
 * 1. Validar sesión y método HTTP
 * 2. Contar registros elegibles
 * 3. Si count > 0 → Ejecutar DELETE
 * 4. Retornar resultado con conteo
 *
 * Variables clave:
 * - $_SESSION['user_id']: Identificador del voluntario
 * - $count: Número de registros elegibles
 * - $rowsAffected: Registros eliminados efectivamente
 *
 * Auditoría:
 * - Loggeo de errores en error_log
 * - Códigos HTTP precisos
 * - Mensajes claros sin detalles internos
 *
 * Precondiciones:
 * - Sesión activa válida
 * - Petición POST
 * - Items en estado entregado/devuelto
 *
 * Notas técnicas:
 * - Operación irreversible (DELETE físico)
 * - No afecta items marcados como no devueltos/perdidos
 * - Optimizado para uso desde AJAX
 *
 * Integración:
 * - Invocado desde botón "Entrega Aprobado"
 * - Requiere confirmación previa en UI
 */
require_once './../../db/dbconn.php';
require './../../utils/session_check.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    die('Acceso no autorizado');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 400 Bad Request');
    die('Solicitud inválida');
}

$voluntario_id = $_SESSION['user_id'];

try {
    // Verificar que hay items para eliminar
    $stmt = $conn->prepare("SELECT COUNT(*) FROM solicitudes_herramientas 
                           WHERE voluntarioid = ? 
                           AND estado_entrega = 'Entregado'
                           AND (estado_devolucion IS NULL OR estado_devolucion = '' OR estado_devolucion = 'Devuelto')");
    $stmt->execute([$voluntario_id]);
    $count = $stmt->fetchColumn();
    
    if ($count === 0) {
        header('HTTP/1.1 200 OK');
        echo 'No hay items prestados para eliminar';
        exit();
    }
    
    // Eliminar todos los items prestados del voluntario
    $stmt = $conn->prepare("DELETE FROM solicitudes_herramientas 
                           WHERE voluntarioid = ? 
                           AND estado_entrega = 'Entregado'
                           AND (estado_devolucion IS NULL OR estado_devolucion = '' OR estado_devolucion = 'Devuelto')");
    $stmt->execute([$voluntario_id]);
    
    // Verificar cuántas filas fueron afectadas
    $rowsAffected = $stmt->rowCount();
    
    header('HTTP/1.1 200 OK');
    echo json_encode(['success' => true, 'message' => 'OK', 'deleted' => $rowsAffected]);
} catch(PDOException $e) {
    error_log("Error al eliminar items prestados: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'error' => 'Error al eliminar items prestados']);
}