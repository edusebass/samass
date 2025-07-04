/**
 * Cancelar Solicitud - Cancelación de solicitudes de compra
 * 
 * Script para cancelar solicitudes de compra existentes.
 * Valida que la solicitud pertenezca al usuario actual.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

if (!isset($_GET['id'])) {
    header('Location: ../usuarios/voluntario.php');
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
        header('Location: ../usuarios/voluntario.php');
        exit;
    }
    
    // Eliminar la solicitud
    $stmt = $conn->prepare("DELETE FROM solicitudes_herramientas WHERE idsolicitud = ?");
    $stmt->execute([$id_solicitud]);
    
    $_SESSION['success'] = "Solicitud eliminada correctamente";
    header('Location: ../usuarios/voluntario.php');
    exit;
} catch(PDOException $e) {
    $_SESSION['error'] = "Error al eliminar la solicitud: " . $e->getMessage();
    header('Location: ../usuarios/voluntario.php');
    exit;
}