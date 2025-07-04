<?php
/**
 * Verificar Rol - Verificación de permisos de usuario
 * 
 * Utilidad para verificar roles de usuario y controlar
 * acceso a funcionalidades específicas del sistema.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

session_start();

if (!isset($_SESSION['rol'])) {
    echo "
    <div style='max-width: 600px; margin: 30px auto; padding: 20px; border: 1px solid #f5c2c7; background-color: #f8d7da; border-radius: 8px; color: #842029; font-family: Arial, sans-serif;'>
        <h4 style='margin-top: 0; font-size: 18px;'>Error</h4>
        <p style='margin: 10px 0;'>El rol no está definido en la sesión. Por favor, verifica tu acceso.</p>
        <a href='/src/pages/auth/login.php' style='display: inline-block; padding: 10px 15px; background-color: #0d6efd; color: #ffffff; text-decoration: none; border-radius: 5px; font-size: 16px;'>Ir a la página de login</a>
    </div>";
    exit();
}



$role = (int)$_SESSION['rol'];

// Verificación de rol 8
if ($role === 8) {
    // Si el rol es 8, solo permitimos acceso a voluntarios.php
    if (basename($_SERVER['PHP_SELF']) !== 'src/pages/usuarios/voluntario.php') {
        header('Location: ./../pages/usuarios/voluntario.php');
        exit();
    }
}
?>
