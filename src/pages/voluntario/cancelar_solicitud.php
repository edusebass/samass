<?php
/**
 * Cancelar Solicitud - Script para eliminación de solicitudes de herramientas
 *
 * Descripción:
 * Procesa la eliminación segura de solicitudes de herramientas, verificando previamente
 * que pertenezcan al voluntario que realiza la acción.
 *
 * Modificaciones:
 * - Ahora soporta redirección tanto para voluntario.php como bodega.php
 * - Conserva el parámetro de búsqueda cuando viene de bodega.php
 * - Verifica permisos adicionales para usuarios de bodega
 */

require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID de solicitud no proporcionado";
    header('Location: ' . determinarRedireccion());
    exit;
}

$id_solicitud = $_GET['id'];
$voluntario_id = $_SESSION["user_id"];
$es_bodega = ($_SESSION['rol'] == 2); // Rol 2 = Bodega

try {
    // Verificar que la solicitud pertenece al voluntario actual o el usuario es de bodega
    $sql = "SELECT idsolicitud FROM bodega_herramientas WHERE idsolicitud = ?";
    $params = [$id_solicitud];
    
    if (!$es_bodega) {
        $sql .= " AND voluntarioid = ?";
        $params[] = $voluntario_id;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "No tienes permiso para eliminar esta solicitud o no existe";
        header('Location: ' . determinarRedireccion());
        exit;
    }
    
    // Eliminar la solicitud
    $stmt = $conn->prepare("DELETE FROM bodega_herramientas WHERE idsolicitud = ?");
    $stmt->execute([$id_solicitud]);
    
    $_SESSION['success'] = "Solicitud eliminada correctamente";
    header('Location: ' . determinarRedireccion());
    exit;
} catch(PDOException $e) {
    $_SESSION['error'] = "Error al eliminar la solicitud: " . $e->getMessage();
    header('Location: ' . determinarRedireccion());
    exit;
}

/**
 * Determina la URL de redirección basada en el origen
 */
function determinarRedireccion() {
    $search = isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : '';
    
    // Verificar si viene de bodega.php
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($referer, 'bodega.php') !== false) {
        return '../voluntario/bodega.php' . $search;
    }
    
    // Por defecto redirigir a voluntario
    return '../voluntario/voluntario.php';
}
?>