/**
 * Logout - Cierre de sesión seguro
 * 
 * Script para cerrar sesión de manera segura,
 * limpiando cookies y variables de sesión.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
// Inicia la sesión si no está iniciada
session_start();

// Unset todas las variables de sesión
$_SESSION = array();

// Si se está usando un cookie de sesión, destruirlo
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruye la sesión
session_destroy();

// Redirige al usuario a la página de login
header("Location: ./../pages/auth/login.php");
exit();
?>