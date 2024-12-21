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
        <div class="w-100 bg-plomo m-2 p-1 h5"><b>ASAMBLEA REGIONAL "PREDIQUEN LAS BUENAS NOTICIAS"<br>
        2-4 de agosto de 2024</b>
    </div>
    <div class="row mb-3">
        <div class="col-md-10">
            <div class="row">
            <div class="col-md-7">
                <div class="w-100 bg-plomo mb-2 p-1"><b>DOCUMENTOS</b></div>
                <div class="card rounded-4 p-2">
                    <ul class="list-docs">
                    <li class="py-adm-list-style">
                        <img src="/public/ico/pdf.svg" alt="Formulario Registro de contribuciones para OM" style="height: 30px; width: 30px;">
                        <a href="#">Formulario "Registro de contribuciones para la Obra Mundial"</a>
                    </li>
                    <li class="py-adm-list-style">
                        <img src="/public/ico/pdf.svg" alt="Formulario Registro de bodega de limpieza" style="height:30px; width: 30px;">
                        <a href="#">Formulario "Registro de bodega de limpieza"</a>
                    </li>
                    <li class="py-adm-list-style">
                        <img src="/public/ico/png.svg" alt="Permiso de descarga de desechos sólidos" style="height:30px; width: 30px;">
                        <a href="#">"Permiso de descarga de desechos sólidos"</a>
                    </li>
                    <li class="py-adm-list-style">
                        <img src="/public/ico/pdf.svg" alt="Inspección inicial y final de las instalaciones" style="height:30px; width: 30px;">
                        <a href="#">"Inspección inicial y final de las instalaciones"</a>
                    </li>
                    <li class="py-adm-list-style">
                        <img src="/public/ico/pdf.svg" alt="Registro de uso de radio comunicadores en las asambleas" style="height:30px; width: 30px;">
                        <a href="#">Registro de uso de radio comunicadores en las asambleas</a>
                    </li>
                    <li class="py-adm-list-style">
                        <img src="/public/ico/pdf.svg" alt="Registro de llaves" style="height:30px; width: 30px;">
                        <a href="#">"Registro de llaves"</a>
                    </li>
                    </ul>
                </div>
             </div>
                <div class="col-md-5">
                    <div class="w-100 bg-plomo mb-2 p-1"> <b>UTILIDAD</b></div>
                    <div class="card rounded-4 p-2">
                        <div class="row justify-content-around align-items-center text-center">     
                            <div class="col-md-6">
                                <a class="w-100 p-3 " href="#"> 
                                    <img class="m-auto d-block" src="/public/ico/conteo.svg" alt="Asistencia" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                                    <h5>Asistencia</h5>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a class="w-100 p-3 " href="#"> 
                                    <img class="m-auto d-block" src="/public/ico/anuncios.svg" alt="Anuncios" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                                    <h5>Anuncios</h5>
                                </a>
                            </div>
                        </div>
                        <div class="row justify-content-around align-items-center text-center">
                            <div class="col-sm-6 col-md-4">
                                <a class="w-100 p-3 " href="https://www.jw.org/es/"> 
                                    <img class="m-auto d-block" src="/public/ico/ico-jw.png" alt="jw.org" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                                </a>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <a class="w-100 p-3 " href="https://wol.jw.org/es/wol/h/r4/lp-s"> 
                                    <img class="m-auto d-block" src="/public/ico/ico-wol.svg" alt="WOL" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                                </a>
                            </div>
                            <div class="col-sm-6 col-md-4">
                                <a class="w-100 p-3 " href="https://hub.jw.org/home/es"> 
                                    <img class="m-auto d-block" src="/public/ico/ico-jw-hub.svg" alt="JW Hub" style="height:auto; max-height:120px; width: 100%; max-width:120px; min-width:60px;">
                                </a>
                            </div>  
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="w-100 bg-plomo mb-2 p-1"> <b>COMUNICACIONES DE LA SUPERINTENDENCIA DEL LOCAL DE ASAMBLEAS</b></div>
                    <div class="card card-body">
                        Hola mundo
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="row">
                <div class="w-100 bg-plomo mb-2 p-1"> <b>ASISTENCIA</b></div>
                <div class="card rounded-4 p-2"> 
                    <div class="row">
                    <div class="col-12">
                        <table class="table table-borderless table-sm">
                            <tr>
                            <th scope="col" colspan="2" class="interlineado">VIERNES</th>
                            </tr>
                            <tr>
                                <td colspan="2" class="no-interlineado">AM: <b>768</b></td>
                            </tr>
                            <tr>
                                <td>PM: <b>752</b></td>
                                <td><b>1520</b></td>
                            </tr>
                            <th scope="col" colspan="2" class="interlineado">SÁBADO</th>
                            </tr>
                            <tr>
                                <td colspan="2" class="no-interlineado">AM: <b>768</b></td>
                            </tr>
                            <tr>
                                <td>PM: <b>752</b></td>
                                <td><b>1520</b></td>
                            </tr><th scope="col" colspan="2" class="interlineado">DOMINGO</th>
                            </tr>
                            <tr>
                                <td colspan="2" class="no-interlineado">AM: <b>768</b></td>
                            </tr>
                            <tr>
                                <td>PM: <b>752</b></td>
                                <td><b>1520</b></td>
                            </tr>
                        </table>
                     </div>
                    </div>
                    
                </div>
            </div>
            <div class="card card-body">
                <b>4560</b>
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