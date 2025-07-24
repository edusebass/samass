/**
 * Redirect - Redirección basada en roles
 * 
 * Script para redirigir usuarios a sus páginas correspondientes
 * según su rol en el sistema.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
session_start();
if (!isset($_SESSION['rol'])) {
    echo "Role not set in session.";
    exit();
}

$role = (int)$_SESSION['rol'];
echo "Role: $role<br>"; // Debug output

if ($role === 1) {
    //Administracion;
    header('Location: ./../pages/dashboard/admin.php');
    exit();
} elseif ($role === 2) {
    //Bodega;
    header('Location: ./../pages/bodega/bodega.php');
    exit();
} elseif ($role === 3) {
    //Capitan;
    header('Location: ./../pages/enlaces.php');
    exit();
} elseif ($role === 4) {
    //Representante;
    header('Location: ./../pages/enlaces.php');
    exit();
} elseif ($role === 5) {
    //Comite Asamblea;
    header('Location: ./../pages/regional.php');
    exit();
} elseif ($role === 6) {
    //Super user;
    header('Location: ./../pages/enlaces.php');
    exit();
} elseif ($role === 7) {
    //Desarollo;
    header('Location: ./../pages/administracion.php');
    exit();
}
 elseif ($role === 8) {
    //Desarollo;
    header('Location: ./../pages/voluntario/voluntario.php');
    exit();
} else {
    //Varios;
    header('Location: ./../pages/enlaces.php');
    exit();
}
?>
