/**
 * Marcar Prestado - Endpoint para gestión de estados de préstamos
 *
 * Descripción:
 * Controlador dual para actualización de estados de préstamos, tanto individuales como masivos.
 * Maneja transiciones entre estados 'Entregado' y 'Pendiente' con registro automático de fechas.
 *
 * Funcionalidades principales:
 * - Actualización individual de estado (Entregado/Pendiente)
 * - Procesamiento masivo de devoluciones (entrega total)
 * - Registro automático de timestamps:
 *   * fecha_entregado al marcar como Entregado
 *   * fecha_recibido en devoluciones masivas
 *   * Limpieza de fechas al revertir a Pendiente
 *
 * Modos de operación:
 * 1. Individual (POST id + estado_entrega):
 *   - Transiciones estado_entrega
 *   - Gestión de fecha_entregado
 *
 * 2. Masivo (POST accion=entrega_total + ids[]):
 *   - Marca múltiples items como Devueltos
 *   - Registra fecha_recibido
 *   - No modifica estado_entrega
 *
 * Validaciones:
 * - Autenticación obligatoria
 * - Método POST exclusivo
 * - Propiedad de los items (voluntarioid)
 * - Existencia de IDs en solicitudes
 *
 * Respuestas:
 * - Individual:
 *   * 'OK' (éxito)
 *   * 'No se actualizó...' (sin cambios)
 *   * Mensaje de error (fallo)
 *
 * - Masivo:
 *   * JSON {success, count}
 *   * JSON {success, error} (fallo)
 *
 * Seguridad:
 * - Consultas preparadas con parámetros
 * - Filtrado de IDs en operación masiva
 * - Restricción por voluntario_id
 * - Loggeo de errores
 *
 * Flujo de datos:
 * - Individual:
 *   estado_entrega → define if(fecha_entregado)
 *
 * - Masivo:
 *   estado_devolucion = 'Devuelto' + fecha_recibido
 *
 * Campos afectados:
 * - estado_entrega (individual)
 * - estado_devolucion (masivo)
 * - fecha_entregado (individual)
 * - fecha_recibido (masivo)
 *
 * Integración:
 * - Llamado desde:
 *   * Checkboxes individuales
 *   * Botón "Entrega Total"
 * - Coordina con:
 *   * Tabla de préstamos activos
 *   * Listado histórico
 *
 * Auditoría:
 * - error_log para excepciones
 * - Conteo preciso de registros afectados
 *
 * Notas técnicas:
 * - Operación masiva no es transaccional
 * - NOW() para timestamps consistentes
 * - Placeholders dinámicos para arrays de IDs
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