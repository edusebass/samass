/**
 * Eliminar Todos Prestados - Gestión masiva de préstamos
 * 
 * Script para eliminar masivamente todos los items prestados
 * a un voluntario específico en bodega.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
require_once './../../db/dbconn.php';
require './../../utils/session_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirmar'])) {
    header('HTTP/1.1 400 Bad Request');
    die('Solicitud inválida');
}

$voluntario_id = $_SESSION['user_id'];

try {
    // Verificar que hay items para eliminar
    $stmt = $conn->prepare("SELECT COUNT(*) FROM solicitudes_herramientas 
                           WHERE voluntarioid = ? 
                           AND estado_entrega = 'Entregado'
                           AND (estado_devolucion IS NULL OR estado_devolucion = '')");
    $stmt->execute([$voluntario_id]);
    $count = $stmt->fetchColumn();
    
    if ($count === 0) {
        header('HTTP/1.1 200 OK');
        die('No hay items prestados para eliminar');
    }
    
    // Eliminar todos los items prestados del voluntario que no tengan estado_devolucion
    $stmt = $conn->prepare("DELETE FROM solicitudes_herramientas 
                           WHERE voluntarioid = ? 
                           AND estado_entrega = 'Entregado'
                           AND (estado_devolucion IS NULL OR estado_devolucion = '')");
    $stmt->execute([$voluntario_id]);
    
    header('HTTP/1.1 200 OK');
    echo 'OK';
} catch(PDOException $e) {
    error_log("Error al eliminar items prestados: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    die("Error al eliminar items prestados");
}