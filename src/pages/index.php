<?php
/**
 * Archivo de redirección principal
 * 
 * Descripción:
 * Redirige automáticamente al dashboard principal del sistema.
 * Mantiene compatibilidad con enlaces existentes.
 * 
 * @author  SAM Assistant Team
 * @version 1.0
 * @since   2025-07-04
 */

// Redirección al dashboard principal
header('Location: dashboard/index.php');
exit();
?>
