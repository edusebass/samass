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
            
            $displayName = htmlspecialchars($_SESSION['display_name']);
            return '
            <style>
    .user-dropdown {
        position: relative;
        display: inline-block;
    }
    .user-dropdown-content {
        display: none;
        position: fixed;
        background-color: #f3f4f6; /* gris claro */
        min-width: 140px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 9999;
        right: 0;
        border-radius: 6px;
        padding: 0.5rem 0;
    }
    .user-dropdown-content a {
        color: #333;
        padding: 10px 16px;
        text-decoration: none;
        display: block;
        background: #f3f4f6; /* gris claro */
        transition: background 0.2s;
    }
    .user-dropdown-content a:hover {
        background: #e5e7eb; /* gris más claro al pasar el mouse */
    }
    .user-dropdown-content .divider {
        height: 1px;
        background: #e0e0e0;
        margin: 0 8px;
    }
    .user-dropdown.active .user-dropdown-content {
        display: block;
    }
    .user-dropdown .user-name {
        cursor: pointer;
        font-weight: bold;
        padding: 0 8px;
    }
</style>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var userDropdown = document.querySelector(".user-dropdown");
                    if(userDropdown){
                        userDropdown.querySelector(".user-name").onclick = function(e) {
                            e.stopPropagation();
                            userDropdown.classList.toggle("active");
                        };
                        document.addEventListener("click", function() {
                            userDropdown.classList.remove("active");
                        });
                    }
                });
            </script>
            <div class="w-100 align-items-center py-2 btns-user" style="background-color:#e8ecf2">
                <div class="align-items-center px-3">
                    <a href="/src/utils/Redirect.php" class="px-2">
                        <img src="/public/ico/home.png" class="center" style="height: 20px; width: 20px;" alt="Home">
                    </a>
                    <div class="user-dropdown" style="display:inline-block;">
                        <span class="user-name">' . $displayName . '</span>
                        <div class="user-dropdown-content">
                            <a href="perfil.php" style="">Perfil</a>
                            <a href="/src/utils/logout.php">
                                <img src="/public/ico/exit.png" style="height: 20px; vertical-align:middle;" alt="Logout">
                                Cerrar sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>';
        } else {
            return '';
        }
    }
}



?>
<body>
<header class="position-relative overflow-hidden w-100 header-user">
    <div class="container-fluid p-0 w-100 position-absolute" style="left:0;">
        <div class="row p-0 m-0 align-center">
                <div class="col-auto p-0">
  <a href="/src/utils/Redirect.php">
    <img src="/public/ico/logoSAM.svg" alt="Logo" class="img-fluid" style="height: 80px; width: 80px;">
  </a>
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

