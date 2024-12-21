<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function checkSessionTimeout() {
    $timeout = 60 * 15; // 1 hora

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Limpiar la sesión de forma segura
        $_SESSION = array(); // Limpiar todos los datos de sesión
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy(); // Destruir la sesión
        
        // Redirigir al login con un parámetro de expiración
        header("Location: ./../pages/login.php?expired=1");
        exit();
    }
    
    // Actualizar el tiempo de última actividad
    $_SESSION['last_activity'] = time();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectBasedOnSession() {
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    if (!isLoggedIn()) {
        // Si no hay sesión y no estamos en login, redirigir
        if ($currentPage !== 'login.php') {
            header("Location: ./../pages/login.php");
            exit();
        }
    } else {
        // Si hay sesión y estamos en login, redirigir a enlaces
        if ($currentPage === 'login.php') {
            header("Location: ./../pages/enlaces.php");
            exit();
        }
    }
}

// Verificar y redirigir según la sesión
redirectBasedOnSession();

// Verificar timeout de sesión
checkSessionTimeout();
?>