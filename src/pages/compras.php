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
        
        <section class="row mb-3">
            <!-- INFORMACIONES -->
            <div class="col-md-6 pt-2 ">
                <div class="w-100 bg-plomo mb-2 p-1"><b>INFORMACIONES</b></div>
                <div class="card rounded-4 p-2">
                    <div class="row ">
                        <!-- FECHA/SOLICITUD --> 
                         <label for="" class="col-sm-4">Fecha de solicitud:<!--?php echo htmlspecialchars($item['fechaSolicitud']); ?--></label>
                           <div class="col-sm-8">
                                <!-- <input type="date" class="form-control" id="fechaSolicitud"> -->
                            </div>
                            <!--*****Si necesita se puede ocupar este código, caso
                            contrario, favor borrar-->
                    </div>
                    <div class="row ">
                        <label for="areaDestino" class="col-sm-4 col-form-label">Área de destino: </label>
                        <div class="col-sm-8">
                            <select class="form-select border border-gray" id="seleccionArea" aria-label="Área de destino">
                                <option disabled selected>Seleccione</option>
                                <option value="1">Residencia</option>
                                <option value="2">Oficina</option>
                                <option value="3">Bodega</option>
                                <option value="4">Auditorio</option>
                                <option value="5">Asamblea</option>
                            </select>
                        </div>
                    </div>
                    <div class="row ">
                    <!-- SOLICITADO POR--> 
                        <label for="" class="col-sm-4">Solicitado por: <!--?php echo htmlspecialchars($item['solicitado']); ?--></label>
                        <div class="col-sm-8">
                            <!-- <input type="text" class="form-control" id="solicitado"> -->
                        </div> 
                        <!--*****Si necesita se puede ocupar este código, caso
                        contrario, favor borrar>-->
                    </div>
                </div>
            </div>
            <!-- INFORMACIÓN -->
            <div class="col-md-6 "> 
                <div class="w-100 bg-plomo mb-2 p-1"><b>INFORMACIÓN</b></div>
                <div class="card rounded-4 p-2">
                    <div class="row">
                        <div class="col-12">
                            <label for="" class="col-sm-4"> <!--?php echo htmlspecialchars($item['solicitado']); ?--></label>
                        </div> 
                    </div>
                </div>
            </div>
        </section>
        <section class="row mb-3">
            <!-- MATERIALES -->
            <div class="col-12">
                <div class="w-100 bg-plomo mb-2 p-1"><b>MATERIALES QUE NECESITA</b></div>
                <div class="card rounded-4 p-2">
                    <form method="post">
                        <div class="row row-cols-lg-auto g-3 justify-content-between align-items-center">
                          <!-- <div class="col-12"> -->
                            <div class="col-12">
                                <label for="tipo_item" col-sm-2 col-form-label col-form-label-sm>Tipo de item:</label>
                                <div class=col-sm-10">
                                    <select class="form-select border border-gray" id="seleccionTipoItem" aria-label="Tipo de item">
                                        <option disabled selected>Seleccione</option>    
                                        <option value="1">Opción 1</option>
                                        <option value="2">Opción 2</option>
                                        <option value="3">Opción 3</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <label for="codigo_item" col-sm-2 col-form-label col-form-label-sm>Código item:</label>
                                <select class="form-select border border-gray" id="seleccionCodigoItem" aria-label="Código item">
                                    <option disabled selected>Seleccione</option>    
                                    <option value="1">Opción 1</option>
                                    <option value="2">Opción 2</option>
                                    <option value="3">Opción 3</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="cantidad" col-sm-2 col-form-label col-form-label-sm>Cantidad:</label>
                                <input type="number" class="form-control form-control-sm" name="cantidad" id="cantidad" min="1" max="100">
                            </div>
                          <!-- </div> -->
                        </div>
                        <div class="row row-cols-lg-auto g-3 align-items-center">
                            <div class="col-10">
                                <label for="observaciones">Observaciones</label>
                                <textarea class="form-control" name="observaciones" id="obsevaciones" row="3" ></textarea>
                            </div>
                            <div class="col-2">
                                <button type="submit" class="btn btn-primary">Confirmar Item</button>
                            </div>
                        </div>   
                        
                    </form>
                </div>
            </div>  
        </section> 
        <div class="row mb-3">
            <div class="col-12">
                <div class="w-100 bg-plomo mb-2 p-1"><b>PEDIDO <span class="#">#</span></b></div>
                <div class="card rounded-4 p-2">
                    <div class="row">
                        <div class="col-md-12"></div>
                    </div>
                </div>
            </div>
        </div> 
        <div class="row mb-3">
            <div class="col-2 justify-items-right">
                <button type="submit" class="btn btn-primary">Enviar pedido</button>
            </div>
        </div>  
 
    </div>
<?php
require './../layout/footer.htm';
    ?>   
        <script src="js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
</body>
