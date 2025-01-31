<?php
require './../layout/head.html';
require './../layout/header.php'; 
require './../utils/session_check.php';
require_once './../db/dbconn.php';
require './../utils/ejecutar_query.php';

$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : null;

function obtener_material($conn, $codigo) {
    $codigo_m = "M_" . $codigo;
    $query_obtener_item = "SELECT * FROM materiales WHERE codigo = ?";
    return ejecutar_query($conn, $query_obtener_item, [$codigo_m]);
   
}

$material = obtener_material($conn, $codigo)->fetch(PDO::FETCH_ASSOC);


// Función para obtener detalles de otra tabla usando IDs de referencia
function obtener_detalle_por_id($conn, $tabla, $campo_id, $id) {
    $query = "SELECT * FROM $tabla WHERE $campo_id = ?";
    return ejecutar_query($conn, $query, [$id])->fetch(PDO::FETCH_ASSOC);
}

// Obtener detalles basados en los IDs del registro de items
$estado = obtener_detalle_por_id($conn, 'estado', 'idestado', $material['id_estado']);
$area = obtener_detalle_por_id($conn, 'mat_area', 'id_mat_area', $material['id_mat_area']);
$medida = obtener_detalle_por_id($conn, 'mat_medida', 'id_mat_medida', $material['id_mat_medida']);

?>
    <title>SAM Assistant</title>
</head>
<body>
<div class="container-fluid mt-3">
    <div style="text-align:left; background-color: #e8ecf2; color:#5C6872;">INFORMACIONES</>    
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><b>INFORMACIONES</b></div>
            <div class="card rounded-4 px-3 mb-3 gap-3 justify-content-between">
                <div class="row mt-3 p-1 justify-content-start">
                    <div class="col-12 col-sm-6">
                    <!-- <div class="row mb-3"> -->
                        <label for="" class="col-sm-2 col-form-label">CODIGO: </label>
                        <div class="col-sm-8"> 
                            <span class="form-control-plaintext"><?php echo htmlspecialchars(string: $material['codigo']); ?></span> 
                        </div> 
                        <!-- </div> -->
                        <label class="col-sm-4 col-form-label">NOMBRE:</label> 
                        <div class="col-sm-8"> 
                            <span class="form-control-plaintext"><?php echo htmlspecialchars(string: $material['mat_nombre']); ?></span> 
                        </div> 
                        <label class="col-sm-4 col-form-label">DESCRIPCION:</label> 
                        <div class="col-sm-8"> 
                            <span class="form-control-plaintext"><?php echo htmlspecialchars(string: $material['mat_descripcion']); ?></span> 
                        </div>
                        <label class="col-sm-4 col-form-label">ESTADO:</label> 
                        <div class="col-sm-8"> 
                            <span class="form-control-plaintext"><?php echo htmlspecialchars(string:$estado['descripcion']); ?></span> 
                        </div>
                        <div class="d-flex flex-column card flex-grow-1 rounded-4 px-3 mb-3 border-default mx-2">
                            <label for="" class="custom-label">FOTO</label>
                            <?php if (!empty($material['mat_foto'])) { ?>
                                <img width="250" src="./../../<?php echo htmlspecialchars($material['mat_foto']); ?>" alt="mat_foto">
                            <?php } else { ?>
                                <p>No hay foto disponible.</p>
                            <?php } ?>
                        </div>
                    </div>                 
                </div>
            </div>
        </div>
    </section>
    <div class="w-100 bg-plomo mb-2 p-1"><b>DETALLES</b></div>  
    <section class="d-flex flex-row card rounded-4 px-3 mb-3 gap-3 border-default">
        <div class="d-flex flex-column custom-label">
            <label for="">AREA: <?php echo htmlspecialchars(string: $area['mat_area_descripcion']); ?> </label>
            <label for="">CANTIDA MINIMA 1: <?php echo htmlspecialchars(string: $material['mat_minimo1']); ?> </label>
            <label for="">CANTIDA MINIMA 2: <?php echo htmlspecialchars(string: $material['mat_minimo2']); ?> </label>
        </div>
        <div class="d-flex flex-column custom-label">
            <label for="">MEDIDA: <?php echo htmlspecialchars(string: $medida['mat_med_descripcion']); ?> </label>
           
        </div>
        <div class="d-flex flex-column card flex-grow-1 rounded-4 px-3 mb-3 border-default mx-2">
            <label for="" class="custom-label">CÓDIGO QR</label>
            <?php if (!empty($material['qr_image_path'])) { ?>
                <img width="250" src="./../../<?php echo htmlspecialchars($material['qr_image_path']); ?>" alt="Código QR">
            <?php } else { ?>
                <p>No hay código QR disponible.</p>
            <?php } ?>
        </div>
    </section>
</div>  
</body>

</html>