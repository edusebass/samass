/**
 * Cancelar Solicitud - Script para eliminación de solicitudes de herramientas
 *
 * Descripción:
 * Procesa la eliminación segura de solicitudes de herramientas, verificando previamente
 * que pertenezcan al voluntario que realiza la acción.
 *
 * Funcionalidades clave:
 * - Elimina permanentemente solicitudes de la base de datos.
 * - Verifica propiedad de la solicitud antes de eliminarla.
 * - Manejo de mensajes de éxito/error en sesión.
 * - Redirección segura tras la operación.
 *
 * Flujo de operación:
 * 1. Verifica parámetro ID en GET y sesión activa.
 * 2. Confirma que la solicitud pertenece al voluntario.
 * 3. Ejecuta eliminación si pasa validaciones.
 * 4. Redirige con feedback de operación.
 *
 * Variables importantes:
 * - $_GET['id']: ID de la solicitud a cancelar.
 * - $_SESSION['user_id']: ID del voluntario autenticado.
 * - $_SESSION['error']: Mensaje de error (si aplica).
 * - $_SESSION['success']: Mensaje de confirmación.
 *
 * Seguridad:
 * - Verificación de sesión obligatoria.
 * - Confirmación de propiedad antes de eliminar.
 * - Manejo de errores con try-catch.
 * - Redirecciones seguras tras cada operación.
 * - Consultas preparadas para evitar inyección SQL.
 *
 * Mensajes de sesión:
 * - Éxito: "Solicitud eliminada correctamente"
 * - Error: Variantes según fallo (permisos, BD, etc.)
 *
 * Dependencias:
 * - session_check.php: Validación de sesión.
 * - dbconn.php: Conexión a base de datos.
 *
 * Redirecciones:
 * - Siempre redirige a voluntario.php tras operación.
 * - Conserva mensajes en sesión para feedback.
 *
 * Casos de error manejados:
 * - Falta de ID en parámetro GET.
 * - Solicitud no pertenece al usuario.
 * - Errores de base de datos.
 * - Intento de eliminar solicitud inexistente.
 *
 * Notas:
 * - Eliminación permanente (no es borrado lógico).
 * - No requiere interfaz propia (se invoca desde voluntario.php).
 * - El feedback se muestra en la interfaz principal.
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

if (!isset($_GET['id'])) {
    header('Location: ../voluntario/voluntario.php');
    exit;
}

$id_solicitud = $_GET['id'];
$voluntario_id = $_SESSION["user_id"];

try {
    // Verificar que la solicitud pertenece al voluntario actual
    $stmt = $conn->prepare("SELECT idsolicitud FROM solicitudes_herramientas WHERE idsolicitud = ? AND voluntarioid = ?");
    $stmt->execute([$id_solicitud, $voluntario_id]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "No tienes permiso para eliminar esta solicitud o no existe";
        header('Location: ../voluntario/voluntario.php');
        exit;
    }
    
    // Eliminar la solicitud
    $stmt = $conn->prepare("DELETE FROM solicitudes_herramientas WHERE idsolicitud = ?");
    $stmt->execute([$id_solicitud]);
    
    $_SESSION['success'] = "Solicitud eliminada correctamente";
    header('Location: ../voluntario/voluntario.php');
    exit;
} catch(PDOException $e) {
    $_SESSION['error'] = "Error al eliminar la solicitud: " . $e->getMessage();
    header('Location: ../voluntario/voluntario.php');
    exit;
}