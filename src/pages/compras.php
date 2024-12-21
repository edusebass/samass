<?php
// Incluir archivos necesarios
require './../layout/head.html';
include('./../utils/verificar_rol.php');
require './../layout/header.php';
require './../utils/session_check.php';

// Limpiar variables de sesión
unset($_SESSION['qr_content']);
unset($_SESSION['id_voluntario']);
unset($_SESSION['codigo_item']);
?>
    <title>SAM Assistant</title>
    </head>
    <body>
    <div class="container-fluid">
        <?php
            $codigo = $_SESSION['codigo']; 
            if ($codigo == 'ADM' || $codigo == 'SU' || $codigo == 'BDG') 
                //A continuación código html en caso de que sea un administrador
        ?>
        <div class="row mb-3">
            <div class="col-md-6 pt-2 ">
                <div class="w-100 bg-plomo mb-2 p-1"><b>IFORMACIONES</b></div>
                <div class="card rounded-4 p-2">
                    <form action="POST"></form>
                </div>
            </div>
            <div class="col-md-6 "> 
                <div class="w-100 bg-plomo mb-2 p-1"><b>INFORMACIÓN</b></div>
                <div class="card rounded-4 p-2"></div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="w-100 bg-plomo mb-2 p-1"><b>MaTERIALES QUE NECESITA</b></div>
            <div class="card rounded-4 p-2">
                <form action="POST"></form>
            </div>
        </div> 
        <div class="row mb-3">
            <div class="w-100 bg-plomo mb-2 p-1"><b>PEDIDO <span class="#">#</span></b></div>
            <div class="card rounded-4 p-2"></div>
        </div> 
        <!-- <div class="row mb-3"></div>  Botón enviar    -->
<?php
require './../layout/footer.htm';
    ?>   
        <script src="js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
