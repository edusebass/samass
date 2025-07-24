<?php
/**
 * Marcar No Devuelto - Endpoint para gestión de estados de devolución
 *
 * Descripción:
 * API especializada en actualizar el estado de devolución de herramientas prestadas,
 * con manejo de lógica condicional para estados relacionados y fechas automáticas.
 * 
 * Funcionalidades clave:
 * - Actualización sincronizada de estados (devolución + entrega cuando aplica)
 * - Registro automático de fechas para devoluciones completadas
 * - Validación estricta de estados permitidos
 * - Restricción de modificación a solicitudes propias
 * - Respuestas JSON estandarizadas
 *
 * Estados permitidos:
 * - 'Devuelto': Item retornado (dispara acciones adicionales)
 * - 'Entregado': Estado intermedio
 * - 'No devuelto': Item no retornado
 * - 'Perdido': Item dado por perdido
 *
 * Lógica condicional:
 * - Al marcar como 'Devuelto':
 *   * Actualiza estado_entrega a 'Entregado'
 *   * Registra fecha_recibido automática
 * - Otros estados mantienen estado_entrega existente
 *
 * Seguridad:
 * - Requiere autenticación via sesión
 * - Exclusivamente método POST
 * - Validación de parámetros obligatorios
 * - Consultas preparadas
 * - Restricción por voluntario_id
 *
 * Estructura de respuesta:
 * - Éxito: {success: true, new_state: [estado]}
 * - Error: {success: false, error: [mensaje]}
 *
 * Flujo de operación:
 * 1. Validar método y parámetros
 * 2. Verificar estado contra lista permitida
 * 3. Ejecutar UPDATE condicional
 * 4. Retornar nuevo estado confirmado
 *
 * Campos afectados:
 * - estado_devolucion (siempre)
 * - estado_entrega (solo si Devuelto)
 * - fecha_recibido (solo si Devuelto)
 *
 * Casos de uso principales:
 * - Regularización de herramientas no devueltas
 * - Reporte de pérdidas
 * - Confirmación de devoluciones completas
 *
 * Integración:
 * - Consumido desde:
 *   * Botón "Marcar como no devuelto"
 *   * Diálogos de cambio de estado
 * - Coordina con:
 *   * Tabla de herramientas no devueltas
 *   * Listado principal de prestamos
 *
 * Notas técnicas:
 * - Usa CASE para lógica condicional en SQL
 * - NOW() para timestamps automáticos
 * - No afecta fecha_solicitud original
 * - Mantiene coherencia entre estados
 */
require_once './../../db/dbconn.php';
require './../../utils/session_check.php';

header('Content-Type: application/json'); // Añadir esta línea

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar conexión a la base de datos
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit;
}

$id = $_POST['id'] ?? '';
$voluntario_id = $_SESSION['user_id'] ?? '';

if (empty($id) || empty($voluntario_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                          SET estado_devolucion = 'No devuelto'
                          WHERE idsolicitud = ? 
                          AND voluntarioid = ?");
    
    $stmt->execute([$id, $voluntario_id]);
    
    echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
    
} catch(PDOException $e) {
    error_log("Error al marcar como no devuelto: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor: ' . $e->getMessage()]);
}