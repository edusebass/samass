/**
 * Eliminar Item - Eliminación de registros de inventario
 * 
 * Script para eliminar items del inventario.
 * Valida la tabla y el ID antes de proceder con la eliminación.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
require_once './../../db/dbconn.php';

$tabla = $_GET['tabla'] ?? '';
$id = $_GET['id'] ?? null;

// Definir los campos igual que en los otros archivos
$tablas_campos = [
    'equipo_seguridad', 'habitacion_huesped_betel', 'herramientas_equipo_jardineria', 'herramientas_manuales', 'maquinas', 'items_generales_por_edificio'
];

if (!in_array($tabla, $tablas_campos) || !$id) {
    die('Petición no válida');
}

$stmt = $conn->prepare("DELETE FROM $tabla WHERE id = ?");
$stmt->execute([$id]);

header("Location: inventario.php?tabla=" . urlencode($tabla));
exit;