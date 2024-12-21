<?php
require './../layout/head.html';
include('./../utils/verificar_rol.php');
require './../layout/header.php';
require './../utils/session_check.php';
unset($_SESSION['qr_content']);
unset($_SESSION['id_voluntario']);
unset($_SESSION['codigo_item']);

?>
    <title>SAM Assistant</title>
    </head>
    <body>
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="w-100 mb-2 p-1" style="text-align:left; background-color: #e8ecf2; color:#5C6872;"><b>BODEGA</b></div>
                <div class="card rounded-4 p-2">
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <a class="w-100 p-3" href="gestionbodega.php" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/operaciones.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Operaciones</h5>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a class="w-100 p-3" href="inventario.php" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/inventario.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Inventario</h5>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a class="w-100 p-3" href="nuevoitem.php" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/nuevo.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Nuevo Item</h5>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a class="w-100 p-3" href="#" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/trazabilidad.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Trazabilidad</h5>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <?php
                $codigo = $_SESSION['codigo']; 
                if ($codigo == 'ADM' || $codigo == 'SU') {
                    //A continuación código html en caso de que sea un administrador
            ?>

            <div class="col-md-6">
                <div class="w-100 mb-2 p-1" style="text-align:left; background-color: #e8ecf2; color:#5C6872;"><b>ESTADISTICAS</b></div>
                <div class="card rounded-4 p-2" style="border: solid 2px #E4640D;">
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <a class="w-100 p-3" href="operaciones.php" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/estadistica.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Movimientos</h5>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <a class="w-100 p-3" href="#" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/report.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Reportes</h5>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="w-100 mb-2 p-1" style="text-align:left; background-color: #e8ecf2; color:#5C6872;"><b>ADMINISTRACION</b></div>
                <div class="card rounded-4 p-2" style="border: solid 2px #E4640D;">
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <a class="w-100 p-3" href="usuarios.php" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/user.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Usuarios</h5>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6">
                            <a class="w-100 p-3" href="secciones.php" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/report.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Secciones</h5>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php
                } else {
                    //A continuación código html en caso de que NO sea un administrador
            ?>

            <div class="col-12">
                <div class="w-100 mb-2 p-1" style="text-align:left; background-color: #e8ecf2; color:#5C6872;"><b>ESTADISTICAS</b></div>
                <div class="card rounded-4 p-2" style="border: solid 2px #E4640D;">
                    <div class="row">
                        <div class="col-12 col-sm-6 col-md-3">
                            <a class="w-100 p-3" href="operaciones.php" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/estadistica.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Movimientos</h5>
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <a class="w-100 p-3" href="#" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/report.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Reportes</h5>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            }
            ?>
        </div>

        <?php
            //$codigo = $_SESSION['codigo'];  No hace falta llamar nuevamente a la variable de sesión CODIGO.
            if ($codigo == 'SU'|| $codigo == 'DSR') {
        ?>

        <div class="row mb-3">
            <div class="col-12">
                <div class="w-100 mb-2 p-1" style="text-align:left; background-color: #e8ecf2; color:#5C6872;"><b>DESARROLLO</b></div>
                <div class="card rounded-4 p-2" style="border: solid 2px #E4640D;">
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <a class="w-100 p-3" href="#" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/mysql.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">MySQL</h5>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a class="w-100 p-3" href="mail.php" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/mail.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Mensajistica</h5>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a class="w-100 p-3" href="#" style="text-align:center;">
                                <img class="m-auto d-block" src="/public/ico/tema.png" style="height:120px; width: auto;">
                                <h5 class="d-block" style="color:#5C6872;">Otro tema</h5>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
            } 
        ?>

    </div>

    <?php
require './../layout/footer.htm';
    ?>   
    </div></div> 
    <script src="js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
</html>