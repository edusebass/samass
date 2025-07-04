<?php
/**
 * Session Check - Gestión de sesiones y autenticación
 * 
 * Utilidad para verificar y gestionar sesiones de usuario,
 * incluyendo timeout, validación y redirecciones.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Validar la solicitud con un token CSRF para prevenir ataques
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
//         die("Solicitud no válida. Token CSRF inválido.");
//     }
}

// Función para verificar si la sesión ha expirado
function checkSessionTimeout() {
    $timeout = 60 * 15; // 15 minutos de tiempo de inactividad permitido

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Limpiar la sesión de forma segura
        $_SESSION = array(); // Limpiar todos los datos de sesión

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy(); // Destruir la sesión

        // Redirigir al login con un mensaje de sesión expirada
        header("Location: /src/pages/auth/login.php?expired=1");
        exit();
    }

    // Actualizar el tiempo de última actividad
    $_SESSION['last_activity'] = time();
}

// Función para comprobar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para redirigir con base en el estado de la sesión
function redirectBasedOnSession() {
    $currentPage = basename($_SERVER['PHP_SELF']);

    if (!isLoggedIn()) {
        // Si no hay sesión activa y no estamos en login.php, redirigir al login
        if ($currentPage !== 'login.php') {
            header("Location: ./../pages/auth/login.php");
            exit();
        }
    } else {
        // Si hay sesión activa y estamos en login.php, redirigir a la página principal (enlaces.php)
        if ($currentPage === 'login.php') {
            header("Location: ./../pages/enlaces.php");
            exit();
        }
    }
}

// Verificar timeout de la sesión antes de cualquier redirección
checkSessionTimeout();

// Redirigir según el estado de la sesión
redirectBasedOnSession();

