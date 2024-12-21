<?php
ob_start(); // Iniciar el búfer de salida al principio

// Iniciar la sesión si aún no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para mostrar el nombre de usuario y los enlaces relacionados
if (!function_exists('displayUsername')) {
    function displayUsername() {
        if (isset($_SESSION['display_name'])) {
            // Corregir la redirección
            if (strpos($_SERVER['REQUEST_URI'], 'login') !== false) {
                header("Location: enlaces.php");
                exit();
            }
            
            // Resto del código de displayUsername...
            $displayName = htmlspecialchars($_SESSION['display_name']);
            return '
            <div class="w-100 align-items-center py-2 btns-user" style="background-color:#e8ecf2">
                <div class="align-items-center px-3">
                    <a href="./../utils/Redirect.php" class="px-2">
                        <img src="/public/ico/home.png" class="center" style="height: 20px; width: 20px;" alt="Home">
                    </a>
                    <a href="perfil.php"><span>' . $displayName . '</span></a>
                    <a href="#" class="px-2">
                        <img src="/public/ico/Mailno.png" class="center" style="height: 20px; width: 30px;" alt="Mail">
                    </a>
                    <a href="./../utils/logout.php" class="px-2">
                        <img src="/public/ico/exit.png" class="center" style="height: 20px; width: 30px;" alt="Logout">
                    </a>
                </div>
            </div>';
        } else {
            return '';
        }
    }
}
?>

<header class="position-relative overflow-hidden w-100 header-user">
    <div class="container-fluid p-0 w-100 position-absolute" style="left:0;">
        <div class="row p-0 m-0 align-center">
            <div class="col-auto p-0">
                <img src="/public/ico/logo1.png" alt="Logo" class="img-fluid" style="height: 80px; width: 80px;">
            </div>
            <div class="col p-0">
                <div class="bg-chocolate w-100 d-block" style="height: 10px;"></div>
                <div class="row p-0 m-0">

                <?php 
                    if (isset($_SESSION['display_name'])) {
                ?>
                    <div class="col-md-6 p-0">
                        <h1 class="w-100 d-block py-2" style="font-weight: bold; font-size: x-large; color: #666768; background-color:#e8ecf2;">SAM Assistant</h1>
                        <h2 class="w-100 d-block" style="font-weight: bold; font-size: large; color: #666768;">Salón de Asambleas Mantenimiento Asistente</h2>
                    </div>
                    <div class="col-md-6 p-0 d-none d-md-inline-block">
                        <?php echo displayUsername(); ?>
                    </div>
                <?php
                    }else{
                ?>
                    <div class="col-12 p-0">
                        <h1 class="w-100 d-block py-2" style="font-weight: bold; font-size: x-large; color: #666768; background-color:#e8ecf2;">SAM Assistant</h1>
                        <h2 class="w-100 d-block" style="font-weight: bold; font-size: large; color: #666768;">Salón de Asambleas Mantenimiento Asistente</h2>
                    </div>
                <?php
                    } 
                ?>
                </div>
            </div>
        </div>
        <div class="row p-0 m-0 align-center d-block d-md-none">
            <div class="col-12 p-0 m-0">
                <?php echo displayUsername(); ?>
            </div>
        </div>
    </div>
</header>

<?php

