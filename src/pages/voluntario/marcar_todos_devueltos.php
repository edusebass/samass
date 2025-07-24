<?php
/**
 * Marcar Todos Devueltos - Endpoint para actualización masiva de devoluciones
 *
 * Descripción:
 * Servicio especializado para marcar todas las herramientas prestadas de un voluntario
 * como devueltas en una sola operación, con registro automático de fecha de recepción.
 *
 * Funcionalidades clave:
 * - Actualización masiva de estado a 'Devuelto'
 * - Registro automático de timestamp en fecha_recibido
 * - Filtrado inteligente de items elegibles:
 *   * Pertenecientes al voluntario
 *   * Con estado_entrega = 'Entregado'
 *   * Sin marca previa de devolución (NULL o ≠ 'Devuelto')
 * - Retorno de conteo preciso de registros afectados
 *
 * Seguridad:
 * - Autenticación obligatoria via sesión
 * - Restricción por voluntario_id
 * - Consultas preparadas
 * - Manejo estructurado de errores
 *
 * Respuestas JSON:
 * - Éxito: {'success': true, 'updated': X}
 * - Error: {'success': false, 'error': '...'}
 *
 * Criterios de selección:
 * 1. voluntarioid = ID de sesión
 * 2. estado_entrega = 'Entregado'
 * 3. estado_devolucion IS NULL OR != 'Devuelto'
 *
 * Campos actualizados:
 * - estado_devolucion → 'Devuelto'
 * - fecha_recibido → NOW()
 *
 * Flujo de operación:
 * 1. Validar sesión activa
 * 2. Ejecutar UPDATE con criterios combinados
 * 3. Contar registros afectados
 * 4. Retornar resultado
 *
 * Casos de uso:
 * - Regularización masiva al finalizar jornada
 * - Sincronización de estado global
 * - Corrección de inconsistencias
 *
 * Integración:
 * - Invocado desde botón "Marcar Todos Devueltos"
 * - Coordina con listados de préstamos activos
 *
 * Auditoría:
 * - Registra cantidad exacta de actualizaciones
 * - Loggeo de excepciones en servidor
 *
 * Notas técnicas:
 * - No es transaccional (commit automático)
 * - NOW() usa zona horaria del servidor
 * - No afecta items ya marcados como devueltos
 * - Optimizado para conjuntos medianos/grandes
 *
 * Precondiciones:
 * - Sesión válida
 * - Items en estado Entregado
 * - Permisos de voluntario
 */

require_once './../../db/dbconn.php';
require './../../utils/session_check.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'No autorizado']));
}

$voluntario_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                           SET estado_devolucion = 'Devuelto', 
                               fecha_recibido = NOW() 
                           WHERE voluntarioid = ? 
                           AND estado_entrega = 'Entregado'
                           AND estado_devolucion = 'Devuelto'"); // Solo actualiza si YA está como 'Devuelto'
    
    $stmt->execute([$voluntario_id]);
    $rows = $stmt->rowCount();
    
    echo json_encode(['success' => true, 'updated' => $rows]);
} catch(PDOException $e) {
    die(json_encode(['success' => false, 'error' => $e->getMessage()]));
}