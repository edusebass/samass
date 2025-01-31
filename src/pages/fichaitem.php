<?php
require './../layout/head.html';
require './../layout/header.php'; 
require './../utils/session_check.php';
require_once './../db/dbconn.php';
require './../utils/ejecutar_query.php';

$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : null;


if ($codigo !== null && str_starts_with($codigo, 'M_')) {
    // Quitar el prefijo "M_"
    $codigo = substr($codigo, 2); // Quitar los dos primeros caracteres
}

function obtener_item($conn, $codigo) {
    $query_obtener_item = "SELECT * FROM items WHERE codigo = ?";
    return ejecutar_query($conn, $query_obtener_item, [$codigo]);
}

$item = obtener_item($conn, $codigo)->fetch(PDO::FETCH_ASSOC);

function calcular_progreso_mantenimiento($fecha_creacion, $tipo_mantenimiento) {
    $fecha_actual = new DateTime();
    $fecha_inicio = new DateTime($fecha_creacion);
    $intervalo = $fecha_actual->diff($fecha_inicio);

    switch ($tipo_mantenimiento) {
        case 'Diario':
            $dias_totales = 1;
            break;
        case 'Semanal':
            $dias_totales = 7;
            break;
        case 'Mensual':
            $dias_totales = 30;
            break;
        case 'Trimestral':
            $dias_totales = 90;
            break;
        case 'Anual':
            $dias_totales = 365;
            break;
        default:
            $dias_totales = 0;
    }

    $dias_transcurridos = $intervalo->days;
    $progreso = min(100, ($dias_transcurridos / $dias_totales) * 100);

    return $progreso;
}

// Función para obtener detalles de otra tabla usando IDs de referencia
function obtener_detalle_por_id($conn, $tabla, $campo_id, $id) {
    $query = "SELECT * FROM $tabla WHERE $campo_id = ?";
    return ejecutar_query($conn, $query, [$id])->fetch(PDO::FETCH_ASSOC);
}

// Obtener detalles basados en los IDs del registro de items
$estado = obtener_detalle_por_id($conn, 'estado', 'idestado', $item['estado_id']);
$seccion = obtener_detalle_por_id($conn, 'secciones', 'idsecciones', $item['seccion_id']);
$area = obtener_detalle_por_id($conn, 'areas', 'idareas', $item['area_id']);
$elemento = obtener_detalle_por_id($conn, 'elemento_tipo', 'idelementos', $item['elemento_id']);
$categoria = obtener_detalle_por_id($conn, 'categorias', 'idcategorias', $item['categoria_id']);
$fuentePoder = obtener_detalle_por_id($conn, 'man_fuentepoder', 'idfuentepoder', $item['id_fuentepoder']);


function obtener_manuales($conn, $codigo){
    $query_obtener_grupo_id = "SELECT grupo_id FROM items WHERE codigo = ?";
    $grupo_id = ejecutar_query($conn, $query_obtener_grupo_id, [$codigo])->fetch(PDO::FETCH_ASSOC)['grupo_id'];
    $query_manual = "SELECT * FROM manuales WHERE grupo_id = ?";
    return ejecutar_query($conn, $query_manual, [$grupo_id])->fetchAll(PDO::FETCH_ASSOC);
}
$manuales = obtener_manuales($conn, $codigo);

function obtener_mantenimientos($conn, $codigo){
    $query_obtener_grupo_id = "SELECT grupo_id FROM items WHERE codigo = ?";
    $grupo_id = ejecutar_query($conn, $query_obtener_grupo_id, [$codigo])->fetch(PDO::FETCH_ASSOC)['grupo_id'];
    $query_manual = "SELECT * FROM mantenimiento WHERE grupo_id = ?";
    return ejecutar_query($conn, $query_manual, [$grupo_id])->fetchAll(PDO::FETCH_ASSOC);
}
$mantenimientos = obtener_mantenimientos($conn, $codigo);

function obtener_descripcion_codigo_man($conn, $id_codigo_man) {
    $query = "SELECT descripcion FROM man_codigo WHERE idman_codigo = ?";
    return ejecutar_query($conn, $query, [$id_codigo_man])->fetch(PDO::FETCH_ASSOC)['descripcion'];
}
?>
<?php
function renderRow($label, $value, $useSpan = false) {
    $element = $useSpan ? 'span' : 'label';
    return "
    <div class='row mb-2'>
        <$element class='col-sm-5 fw-bold'>$label</$element>
        <div class='col-sm-7'>
            <span>" . htmlspecialchars($value) . "</span>
        </div>
    </div>";
}
?>
    <title>SAM Assistant</title>
</head>
<body>
<div class="container-fluid mt-3">
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><strong>INFORMACIONES</strong></div>
            <div class="card rounded-4 px-3 mb-3">
                <div class="row row-cols-sm-2 p-1">
                    <div class="col">
                        <?php
                        echo renderRow('CODIGO:', $item['codigo'], true);
                        echo renderRow('NOMBRE:', $item['nombre'], true);
                        echo renderRow('DESCRIPCIÓN:', $item['descripcion'], true);
                        ?>
                    </div>
                    <div class="col">
                        <?php
                        echo renderRow('TIPO ELEMENTO:', $elemento['tipo'], true);
                        echo renderRow('ESTADO:', $estado['descripcion'], true);
                        echo renderRow('CANTIDAD:', $item['cantidad'], true);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><strong>DETALLES</strong></div>
            <div class="card rounded-4 px-3 mb-3">
                <div class="row row-cols-sm-2 row-cols-md-4 p-1">
                    <div class="col border-end">
                        <?php
                        echo renderRow('COSTO UNITARIO:', $item['costo'], true);
                        echo renderRow('VALOR RESIDUAL:', $item['valor_residual'], true);
                        // echo renderRow('COSTO MANTENIMIENTO:', $item['costo_mantenimiento'], true);
                        ?>
                    </div>
                    <div class="col border-end">
                        <?php
                        echo renderRow('FECHA ADQUISICIÓN:', $item['fecha'], true);
                        echo renderRow('TIEMPO UTILIZACIÓN:', $item['uso'], true);
                        echo renderRow('TIEMPO VIDA ÚTIL:', $item['vida'], true);
                        ?>
                    </div>
                    <div class="col border-end">
                        <?php
                        echo renderRow('FABRICANTE:', $item['fabricante'], true);
                        echo renderRow('S/N:', $item['serial'], true);
                        echo renderRow('MODELO:', $item['modelo'], true);
                        ?>
                    </div>
                    <div class="col border-end">
                        <?php
                        echo renderRow('AÑO FABRICACIÓN:', $item['año_fabricacion'], true);
                        echo renderRow('FUENTE PODER:', $fuentePoder['descripcion'], true);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="w-100 bg-plomo mb-2 p-1"><strong>MANTENIMIENTO</strong></div>
    <section class="row row-cols-sm-2 row-cols-md-3">
        <div class="col-12 col-md-5">
            <div class="card rounded-4 px-3 mb-3">
                <div class="row">
                    <div class="col-12">
                        <label for="" class="form-label">VIGENTE SI</label>
                        <?php
                        foreach ($mantenimientos as $mantenimiento) {
                            $descripcion_codigo_man = obtener_descripcion_codigo_man($conn, $mantenimiento['id_codigo_man']);
                            $progreso = calcular_progreso_mantenimiento($mantenimiento['fecha_creacion'], $descripcion_codigo_man);

                            echo "<p>Notas: " . htmlspecialchars($mantenimiento['notas']) . "</p>";
                            echo "<p>Descripción del Código de Mantenimiento: " . htmlspecialchars($descripcion_codigo_man) . "</p>";

                            echo "<div class='progress'>
                                    <div class='progress-bar' role='progressbar' style='width: " . htmlspecialchars($progreso) . "%;' aria-valuenow='" . htmlspecialchars($progreso) . "' aria-valuemin='0' aria-valuemax='100'></div>
                                  </div>";

                            if ($progreso >= 100) {
                                echo "<script>alert('El mantenimiento debe realizarse ya.');</script>";
                            } elseif ($progreso >= 80) {
                                echo "<script>alert('El mantenimiento está próximo a vencer.');</script>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card rounded-4 px-3 mb-3">
                <div class="row">
                    <div class="col-4 ">
                        <img class="img-fluid" src="/public/ico/manual.png" alt="Manual" style="width: 150px;">
                    </div>
                    <div class="col-8 ">
                        <span>MANUALES</span>
                        <?php
                        if (!empty($manuales)) {
                            foreach ($manuales as $manual) {
                                echo "<p>Título: " . htmlspecialchars($manual['titulo']) . "</p>";
                                echo "<p>Enlace: <a href='./../../" . htmlspecialchars($manual['enlace']) . "' download>" . htmlspecialchars($manual['enlace']) . "</a></p>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-2">
            <div class="card rounded-4 px-3 mb-3">
                <div class="row">
                    <div class="col-12">
                        <span id="codigoQR">CÓDIGO QR</span>
                        <?php
                        if (!empty($item['qr_image_path'])) {
                            echo "<img width='150' src='./../../" . htmlspecialchars($item['qr_image_path']) . "' alt='Código QR'>";
                        } else {
                            echo "<p>No hay código QR disponible.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
require './../layout/footer.htm';
    ?> 
    -
</body>

</html>