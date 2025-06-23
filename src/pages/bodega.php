<?php
// Incluir archivos necesarios
try {
    require './../layout/head.html';
    include('./../utils/verificar_rol.php');
    require './../layout/header.php';
    require './../utils/session_check.php';
} catch (Exception $e) {
    // Manejar errores
    echo "Error: " . $e->getMessage();
    exit();
}

// Limpiar variables de sesión
unset($_SESSION['qr_content']);
unset($_SESSION['id_voluntario']);
unset($_SESSION['codigo_item']);
?>
<title>SAM Assistant</title>
</head>
<body>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6"> 
            <div class="w-100 bg-plomo mb-2 p-1"><b>BODEGA</b></div>
            <div class="card d-flex rounded-4 p-2 align-items-stretch">
                <div class="row justify-content-around align-items-center text-center">
                    <div class="col-sm-6 col-md-4">
                        <a class="w-100 p-3 " href="gestionbodega.php"> 
                            <img class="m-auto d-block" src="/public/ico/bodega.svg" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                        <h5>Operaciones</h5>
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <a class="w-100 p-3 " href="inventario.php">
                            <img class="m-auto d-block" src="/public/ico/inventario.svg" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                        <h5>Inventario</h5>
                        </a>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <a class="w-100 p-3 " href="./nuevoItem/herramientas.php">
                            <img class="m-auto d-block" src="/public/ico/nuevo.svg" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                        <h5>Nuevo Item</h5>
                        </a>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-sm-6 col-md-4">
                        <a class="w-100 p-3 " href="">
                            <img class="m-auto d-block" src="/public/ico/report.svg" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                        <h5>Informes</h5>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 "> 
            <div class="row mb-3">
                <div class="w-100 bg-plomo mb-2 p-1"><b>COMPRAS</b></div>
                <div class="card d-flex rounded-4 p-2">
                    <div class="row mt-3 p-1 justify-content-around align-items-center text-center">
                        <div class="col-sm-6 col-md-3 ">
                            <a class="w-100 p-3" href="compras.php"> 
                                <img class="m-auto" src="/public/ico/compras.svg" style="height:auto; max-height: 100px; width: 100%; max-width: 100px; min-width:60px;">
                                <h5>Pedido</h5>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3 ">
                            <a class="w-100 p-3" href=""> 
                                <img class="m-auto" src="/public/ico/recepcion.svg" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                                <h5>Recepción</h5>
                            </a>
                        </div>
                    </div> 
                </div>
            </div>
            <div class="row mb-3">
                <div class="w-100 bg-plomo mb-2 p-1"> <b>INFORMACIONES</b></div>
                <div class="card d-flex rounded-4 p-2">
                    <div class="row my-1 justify-content-around">
                        <div class="col-12 py-adm-list-style">
                            <div class="d-inline-block px-3"> 
                                <img src="/public/ico/herramienta.svg" alt="Items en bodega" style="height:30px; width: 30px;"> 
                            </div> 
                            <div class="d-inline-block px-3">  
                                Items en bodega: <b>#</b>  
                            </div>
                        </div>
                        <div class="col-12 py-adm-list-style"> 
                            <div class="d-inline-block px-3"> 
                                <img src="/public/ico/mantenimiento.svg" alt="Mantenimiento programado" style="height:30px; width: 30px;"> 
                            </div> 
                            <div class="d-inline-block px-3">  
                            Mantenimiento programado: <b>#</b> 
                            </div>
                        </div> 
                        <div class="col-12 py-adm-list-style"> 
                            <div class="d-inline-block px-3"> 
                                <img src="/public/ico/mantenimientoko.png" alt="Mantenimiento pendiente" style="height:30px; width: 30px;"> 
                            </div> 
                            <div class="d-inline-block px-3">  
                                Mantenimiento pendiente: <b>#</b>
                            </div>
                        </div> 
                        <div class="col-12 py-adm-list-style">
                            <div class="d-inline-block px-3"> 
                                <img src="/public/ico/compras.svg" alt="Procesos de compras pendientes" style="height:30px; width: 30px;"> 
                            </div> 
                            <div class="d-inline-block px-3 text-break">  
                            <span class="text-break">Procesos de compras pendientes:</span> <b>#</b> 
                            </div>
                        </div> 
                    </div>
                </div>
            </div>  
        </div>
    </div>
</div>

<?php
require './../layout/footer.htm';
    ?>   
        <script src="js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
</html>