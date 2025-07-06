<?php
/**
 * Eliminar Todos los Registros - Procesamiento de eliminación masiva
 * 
 * Script para eliminar todos los registros de una tabla específica del inventario.
 * Requiere autenticación con contraseña para mayor seguridad.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

header('Content-Type: application/json');
require_once './../../db/dbconn.php';
require_once './../../utils/session_check.php';

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$tabla = $input['tabla'] ?? '';
$password = $input['password'] ?? '';

// Validar contraseña
if ($password !== '12345678') {
    echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
    exit;
}

// Tablas del inventario
$tablas_inventario = [
    'maquinas' => 'Máquinas',
    'herramientas_manuales' => 'Herramientas Manuales',
    'herramientas_equipo_jardineria' => 'Herramientas y Equipo de Jardinería',
    'equipo_seguridad' => 'Equipo de Seguridad',
    'habitacion_huesped_betel' => 'Habitación Huésped Betel',
    'items_generales_por_edificio' => 'Items Generales por Edificio'
];

// Validar que se quiere eliminar todo el inventario
if ($tabla !== 'TODAS') {
    echo json_encode(['success' => false, 'message' => 'Operación no válida']);
    exit;
}

try {
    // Contar registros totales antes de eliminar
    $totalRegistrosGeneral = 0;
    $detallesEliminacion = [];

    // Contar registros por tabla
    foreach ($tablas_inventario as $tabla_nombre => $tabla_label) {
        $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM $tabla_nombre");
        $stmtCount->execute();
        $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        $totalRegistrosGeneral += $total;
        $detallesEliminacion[$tabla_label] = $total;
    }

    if ($totalRegistrosGeneral == 0) {
        echo json_encode(['success' => false, 'message' => 'Todo el inventario ya está vacío']);
        exit;
    }

    // Eliminar registros de todas las tablas
    $tablas_eliminadas = 0;
    foreach ($tablas_inventario as $tabla_nombre => $tabla_label) {
        $stmt = $conn->prepare("DELETE FROM $tabla_nombre");
        $stmt->execute();
        
        // Reiniciar el AUTO_INCREMENT
        $conn->exec("ALTER TABLE $tabla_nombre AUTO_INCREMENT = 1");
        $tablas_eliminadas++;
    }

    // Registrar la acción en logs (opcional)
    $usuario = $_SESSION['username'] ?? 'Usuario desconocido';
    $fecha = date('Y-m-d H:i:s');
    
    // Preparar mensaje detallado
    $mensaje = "¡INVENTARIO COMPLETAMENTE ELIMINADO!\n\n";
    $mensaje .= "Registros eliminados por tabla:\n";
    foreach ($detallesEliminacion as $tabla => $cantidad) {
        $mensaje .= "• $tabla: $cantidad registros\n";
    }
    $mensaje .= "\nTotal eliminado: $totalRegistrosGeneral registros de $tablas_eliminadas tablas";

    echo json_encode([
        'success' => true, 
        'message' => $mensaje,
        'total_eliminado' => $totalRegistrosGeneral,
        'tablas_procesadas' => $tablas_eliminadas,
        'detalles' => $detallesEliminacion
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error inesperado: ' . $e->getMessage()
    ]);
}
?>
