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
$cantidad = $item['cantidad'];
$costo_mantenimiento = $item['costo_mantenimiento']; // Definir la variable costo_mantenimiento desde la base de datos

function calcular_progreso_mantenimiento($fecha_creacion, $tipo_mantenimiento) {
    $fecha_actual = new DateTime();
    $fecha_inicio = new DateTime($fecha_creacion);
    $intervalo = $fecha_actual->diff($fecha_inicio);
    
    // Normalizar el tipo de mantenimiento para evitar discrepancias
    $tipo_mantenimiento = ucfirst(strtolower(trim($tipo_mantenimiento)));

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
            // $dias_totales = 0;
            $dias_totales = 1; // Asignar un valor mínimo para evitar división por cero
            break;
    }

    $dias_transcurridos = $intervalo->days;
    $progreso = ($dias_transcurridos / $dias_totales) * 100;
     $progreso = min(100, max(0, $progreso)); // Asegurar que el progreso esté entre 0 y 100
    // $progreso = min(100, ($dias_transcurridos / $dias_totales) * 100);
    

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
// Función para renderizar sección Información
function renderInformationRow($label, $value, $useSpan = false) {
    $element = $useSpan ? 'span' : 'label';
    return "
    <div class='row'>
        <$element class='col-sm-3 col-md-5 fw-bold d-flex '>$label</$element>
        <div class='col-sm-9 col-md-7 d-flex align-items-end'>
            <span>" . htmlspecialchars($value) . "</span>
        </div>
    </div>";
}

// Función para renderizar sección Detalles
function renderDetailRow($label, $value, $useSpan = false) {
    $element = $useSpan ? 'span' : 'label';
    return "
    <div class='row'>
        <$element class='col-sm-7 fw-bold d-flex align-items-center'>$label</$element>
        <div class='col-sm-5 d-flex align-items-end'>
            <span>" . htmlspecialchars($value) . "</span>
        </div>
    </div>";
}



?>
    <title>SAM Assistant</title>
</head>
<body>
<div class="container-fluid mt-3">
    <!-- Sección de Información -->
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><strong>INFORMACIÓN</strong></div>
            <div class="card rounded-4 px-1 mb-3">
                <div class="row row-cols-sm-2 m-1">
                    <div class="col d-flex flex-column">
                        <?php
                        echo renderInformationRow('CODIGO:', $item['codigo'], true);
                        echo renderInformationRow('NOMBRE:', $item['nombre'], true);
                        echo renderInformationRow('DESCRIPCIÓN:', $item['descripcion'], true);
                        ?>
                    </div>
                    <div class="col d-flex flex-column">
                        <?php
                        echo renderInformationRow('TIPO ELEMENTO:', $elemento['tipo'], true);
                        echo renderInformationRow('ESTADO:', $estado['descripcion'], true);
                        echo renderInformationRow('CANTIDAD:', $item['cantidad'], true);
                        ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Sección de Detalles -->
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><strong>DETALLES</strong></div>
            <div class="card rounded-4 px-1 mb-3">
                <div class="row row-cols-sm-1 row-cols-md-2 row-cols-lg-4 m-1">
                    <div class="col d-flex flex-column border-end ps-">
                        <?php
                        echo renderDetailRow('COSTO UNITARIO:', $item['costo'], true);
                        echo renderDetailRow('VALOR RESIDUAL:', $item['valor_residual'], true);
                        echo renderDetailRow('COSTO MANTENIMIENTO:', $item['costo_mantenimiento'], true);
                        ?>
                    </div>
                    <div class="col d-flex flex-column border-end ps-">
                        <?php
                        echo renderDetailRow('FECHA ADQUISICIÓN:', $item['fecha'], true);
                        echo renderDetailRow('TIEMPO UTILIZACIÓN:', $item['uso'], true);
                        echo renderDetailRow('TIEMPO VIDA ÚTIL:', $item['vida'], true);
                        ?>
                    </div>
                    <div class="col d-flex flex-column border-end ps-">
                        <?php
                        echo renderDetailRow('FABRICANTE:', $item['fabricante'], true);
                        echo renderDetailRow('S/N:', $item['serial'], true);
                        echo renderDetailRow('MODELO:', $item['modelo'], true);
                        ?>
                    </div>
                    <div class="col d-flex flex-column ps-">
                        <?php
                        echo renderDetailRow('AÑO FABRICACIÓN:', $item['año_fabricacion'], true);
                        echo renderDetailRow('FUENTE PODER:', $fuentePoder['descripcion'], true);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Sección de Mantenimiento -->
    <div class="w-100 bg-plomo mb-2 p-1"><strong>MANTENIMIENTO</strong></div>
    <section class="row row-cols-sm-2 row-cols-md-3 justify-content-around align-items-stretch">
        <!-- Primera Tarjeta -->
        <div class="col-12 col-md-5 d-flex align-items-stretch mb-3">
            <div class="card rounded-4 px-3 py-2 flex-fill">
            <div class="row">
                <div class="col-12">
                    <label for="" class="form-label mt-2 ">VIGENTE SI</label>
                    <?php foreach ($mantenimientos as $mantenimiento):
                        $descripcion_codigo_man = obtener_descripcion_codigo_man($conn, $mantenimiento['id_codigo_man']);
                        $progreso = calcular_progreso_mantenimiento($mantenimiento['fecha_creacion'], $descripcion_codigo_man); ?>

                        <p>Notas: <?= htmlspecialchars($mantenimiento['notas']); ?></p>
                        <p>Descripción del Código de Mantenimiento: <?= htmlspecialchars($descripcion_codigo_man); ?></p>
            
                        <!-- Barra de progreso -->
                        <div class='progress'>
                            <div class='progress-bar' role='progressbar' style='width: <?= $progreso; ?>%;' aria-valuenow='<?= $progreso; ?>' aria-valuemin='0' aria-valuemax='100'><?= $progreso; ?>%</div>
                        </div>

                        <!-- Alertas usando Bootstrap -->
                        <?php if ($progreso >= 100): ?>
                            <div class='alert alert-danger mt-2' role='alert'>
                                El mantenimiento debe realizarse ya.
                            </div>
                        <?php elseif ($progreso >= 80): ?>
                            <div class='alert alert-warning mt-2' role='alert'>
                                El mantenimiento está próximo a vencer.
                            </div>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </div>
            </div>

            </div>
        </div>
        <!-- Segunda Tarjeta - Manuales-->
        <div class="col-12 col-md-5 d-flex align-items-stretch mb-3">
            <div class="card rounded-4 px-3 py-2 flex-fill">
                <div class="row">
                    <div class="col-4 ">
                        <img class="img-fluid" src="/public/ico/manual.png" alt="Manual" style="width: 150px;">
                    </div>
                    <div class="col-8 ">
                        <span class="fw-bold">MANUALES</span>
                        <?php
                        if (!empty($manuales)) {
                            foreach ($manuales as $manual) {
                                echo "<p>Título: " . htmlspecialchars($manual['titulo']) . "</p>";
                                echo "<p>Enlace: <a href='./../../" . htmlspecialchars($manual['enlace']) . "' download>" . htmlspecialchars($manual['enlace']) . "</a></p>";
                            }
                        } else {
                            echo "<p>No hay manuales disponibles.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- Tercera Tarjeta - Código QR -->
        <div class="col-12 col-md-2 d-flex align-items-stretch mb-3">
            <div class="card rounded-4 px-3 py-2 flex-fill">
                <div class="row">
                    <div class="col-12 text-center">
                        <span id="codigoQR" class="fw-bold">CÓDIGO QR</span>
                        <?php
                        if (!empty($item['qr_image_path'])) {
                            echo "<img width='150' src='./../../" . htmlspecialchars($item['qr_image_path']) . "' alt='Código QR' style='max-width: 150px;'>";
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

<!-- <script>
document.addEventListener('DOMContentLoaded', function () {
    <?php foreach ($mantenimientos as $mantenimiento): 
        $progreso = calcular_progreso_mantenimiento($mantenimiento['fecha_creacion'], $descripcion_codigo_man); ?>
        var ctx = document.getElementById('progresoMantenimiento<?= $mantenimiento['id']; ?>').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Progreso'],
                datasets: [{
                    label: 'Porcentaje de Progreso',
                    data: [<?= $progreso; ?>],
                    backgroundColor: ['rgba(75, 192, 192, 0.2)'],
                    borderColor: ['rgba(75, 192, 192, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    <?php endforeach; ?>
});
</script> -->

</body>

</html>