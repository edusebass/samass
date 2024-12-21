<?php
session_start();

if (!isset($_SESSION['rol'])) {
    echo "Role not set in session.";
    exit();
}

$role = (int)$_SESSION['rol'];

// Verificación de rol 8
if ($role === 8) {
    // Si el rol es 8, solo permitimos acceso a voluntarios.php
    if (basename($_SERVER['PHP_SELF']) !== 'src/pages/voluntario.php') {
        header('Location: ./../pages/voluntario.php');
        exit();
    }
}
?>
