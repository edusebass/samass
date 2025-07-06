<?php
/**
 * Reinicio Sesión - Gestión de páginas protegidas
 * 
 * Utilidad para proteger páginas y reiniciar sesiones
 * cuando sea necesario.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

require_once 'session_check.php';

function protegerPagina($nombre_pagina) {
    $_SESSION['pagina_protegida'] = $nombre_pagina;
}

function reiniciarSesionSiNecesario() {
    $pagina_actual = basename($_SERVER['PHP_SELF']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['pagina_protegida'] = $pagina_actual;
        return;
    }
    
    $_SESSION['pagina_protegida'] = $pagina_actual;
}

reiniciarSesionSiNecesario();
?>