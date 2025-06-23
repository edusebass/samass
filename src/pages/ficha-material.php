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
$cantidad = obtener_detalle_por_id($conn, 'materiales', 'mat_cantidad',$material['mat_cantidad']);

// Función para renderizar sección Información
function renderInformationRow($label, $value, $useSpan = false) {
    $element = $useSpan ? 'span' : 'label';
    return "
    <div class='row'>
        <$element class='col-6 fw-bold d-flex'>$label</$element>
        <div class='col-6 d-flex align-items-end'>
            <span>" . htmlspecialchars($value) . "</span>
        </div>
    </div>";
}

// Función para renderizar una fila de detalle
function renderDetailRow($label, $value, $useSpan = false) {
    $element = $useSpan ? 'span' : 'label';
    return "
    <div class='row'>
        <$element class='col-sm-6 fw-bold d-flex align-items-center'>$label</$element>
        <div class='col-sm-6 d-flex align-items-end'>
            <span>" . htmlspecialchars($value) . "</span>
        </div>
    </div>";
}
//Si en el futuro se desea agregar más etiquetas solo bastará con añadir un nuevo par clave/valor.

// Definición de los detalles de materiales (excluyendo el QR)
$materialDetails = [
    'ÁREA:'               => $area['mat_area_descripcion'],
    'MEDIDA:'             => $medida['mat_med_descripcion'],
    'CANTIDAD:'           =>$material['mat_cantidad'],
    'CANTIDAD MÍNIMA 1:'   => $material['mat_minimo1'],
    'CANTIDAD MÍNIMA 2:'   => $material['mat_minimo2']
];

// Agrupar los detalles en pares (columnas de dos elementos)
$detailColumns = array_chunk($materialDetails, 2, true);

?>

<main class="container-fluid mt-3">
    <!-- Sección de Información -->
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><b>INFORMACIÓN</b></div>
            <div class="text-end mb-3">
    <a href="editar.php?codigo=<?= urlencode($codigo); ?>" class="btn btn-primary">
        Editar Material
    </a>
</div>
            <div class="card rounded-4 px-1 mb-3">
                <div class="row m-1">
                    <div class="col-sm-6 d-flex flex-column detail-row ">
                        <?php
                            echo renderInformationRow('CÓDIGO:', $material['codigo'], true);
                            echo renderInformationRow('NOMBRE:', $material['mat_nombre'], true);
                            echo renderInformationRow('DESCRIPCIÓN:', $material['mat_descripcion'], true);
                        ?>
                    </div>
                    <div class="col-sm-6 d-flex flex-column detail-row ">
                        <?php
                            echo renderInformationRow('ESTADO:', $estado['descripcion'], true);
                        ?>
                        <div class="row d-flex flex-column mb-3 justify-content-start ">
                            <?php if (!empty($material['mat_foto'])) { ?>
                                <img class="img-fluid product-image" src="./../../<?php echo htmlspecialchars($material['mat_foto']); ?>" alt="Imagen del material">
                            <?php } else { ?>
                                <img class="img-fluid product-image" src="/public/ico/material.svg" alt="Imagen predeterminada de un pallet de construcción">
                            <?php } ?>
                        </div>
                    </div>                 
                </div>
            </div>
        </div>
    </section>
    <!-- Sección Detalles -->
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><b>DETALLES</b></div>
            <div class="card rounded-4 px-1 mb-3">
                <div class="row row-cols-2 row-cols-md-3 m-1">
                <?php
                // Recorrer cada grupo de dos detalles y renderizarlos en una columna
                foreach ($detailColumns as $index => $column): 
                ?>
                    <div class="col detail-row <?php if ($index !== count($detailColumns) - 1) echo 'separation-edge'; ?>">
                        <?php
                        foreach ($column as $label => $value):
                            echo renderDetailRow($label, $value, true);
                        endforeach;
                        ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- Columna para el Código QR -->
                <div class="col detail-row">
                    <span id="codigoQR" class="fw-bold">CÓDIGO QR</span>
                    <?php if (!empty($material['qr_image_path'])): ?>
                        <img 
                        style="max-width: 100px;" 
                        class="img-fluid" 
                        src="./../../<?= htmlspecialchars($material['qr_image_path']); ?>" 
                        alt="Código QR">
                    <?php else: ?>
                        <p>No hay código QR disponible.</p>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>
    </section>
</main>  
<?php
require './../layout/footer.htm';
?>
</body>

</html>