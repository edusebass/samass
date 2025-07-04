/**
 * Ficha de Item - Detalle completo de un item de inventario
 * 
 * Muestra información detallada de un item específico del inventario,
 * incluyendo historial, imagen, y funcionalidades de edición.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
require './../../layout/head.html';
require './../../layout/header.php'; 
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';
require './../../utils/ejecutar_query.php';

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
if ($item !== false) {
    $cantidad = $item['cantidad'];
    $costo_mantenimiento = $item['costo_mantenimiento'];
    $observaciones = $item['observaciones'];
} else {
    $cantidad = 0;
    $costo_mantenimiento = "No disponible";
    $observaciones = "Sin observaciones";
}


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
    $result = ejecutar_query($conn, $query, [$id])->fetch(PDO::FETCH_ASSOC);
    
    var_dump($result); // Muestra el resultado de la consulta SQL
    var_dump($conn);

    return $result ?: null; // Devuelve `null` si no hay datos en lugar de `false`
}

// Depuración: verificar los valores antes de ejecutar la consulta

$query = "SELECT * FROM estado WHERE idestado = ?";
var_dump($query, $item['estado_id']); // Muestra la consulta y el ID que se está enviando

// Ahora ejecutamos la consulta con la verificación
$estado = obtener_detalle_por_id($conn, 'estado', 'idestado', $item['estado_id']);
var_dump($estado); // Muestra qué devuelve la consulta

// Obtener detalles basados en los IDs del registro de items
$estado_id = !empty($item['estado_id']) ? $item['estado_id'] : 0;
var_dump($item['estado_id']); // Esto te mostrará si el ID es válido
$estado = obtener_detalle_por_id($conn, 'estado', 'idestado', $estado_id);

if (!empty($item['seccion_id'])) {
    $seccion = obtener_detalle_por_id($conn, 'secciones', 'idsecciones', $item['seccion_id']);
} else {
    $seccion = false;
}

if (!empty($item['estado_id'])) {
    $area = obtener_detalle_por_id($conn, 'areas', 'idareas', $item['area_id']);
} else {
    $area = false;
}

if (!empty($item['seccion_id'])) {
    $elemento = obtener_detalle_por_id($conn, 'elemento_tipo', 'idelementos', $item['elemento_id']);
} else {
    $elemento = false;
}

if (!empty($item['estado_id'])) {
    $categoria = obtener_detalle_por_id($conn, 'categorias', 'idcategorias', $item['categoria_id']);
} else {
    $categoria = false;
}

if (!empty($item['seccion_id'])) {
    $fuentePoder = obtener_detalle_por_id($conn, 'man_fuentepoder', 'idfuentepoder', $item['id_fuentepoder']);
} else {
    $fuentePoder = false;
}
// $estado = obtener_detalle_por_id($conn, 'estado', 'idestado', $item['estado_id']);
// $seccion = obtener_detalle_por_id($conn, 'secciones', 'idsecciones', $item['seccion_id']);
// $area = obtener_detalle_por_id($conn, 'areas', 'idareas', $item['area_id']);
// $elemento = obtener_detalle_por_id($conn, 'elemento_tipo', 'idelementos', $item['elemento_id']);
// $categoria = obtener_detalle_por_id($conn, 'categorias', 'idcategorias', $item['categoria_id']);
// $fuentePoder = obtener_detalle_por_id($conn, 'man_fuentepoder', 'idfuentepoder', $item['id_fuentepoder']);

var_dump($estado, $seccion, $area, $elemento, $categoria, $fuentePoder);


// $estado_descripcion = isset($estado['descripcion']) ? $estado['descripcion'] : "Estado no definido";
$seccion_nombre = isset($seccion['seccion']) ? $seccion['seccion'] : "No disponible";
$area_descripcion = isset($area['descripcion']) ? $area['descripcion'] : "No disponible";
$elemento_tipo = isset($elemento['tipo']) ? $elemento['tipo'] : "No disponible";
$categoria_nombre = isset($categoria['categorias']) ? $categoria['categorias'] : "No disponible";
$fuente_poder = isset($fuentePoder['descripcion']) ? $fuentePoder['descripcion'] : "No disponible";

// if (is_array($estado)) {
//     $estado_descripcion = $estado['descripcion'];
// } else {
//     $estado_descripcion = "Estado no definido";
// }

if (is_array($estado)) {
    $seccion_nombre = $seccion['seccion'];
} else {
    $seccion_nombre = "Sección no definido";
}

if (is_array($estado)) {
    $area_descripcion = $area['descripcion'];
} else {
    $area_descripcion = "Área no definido";
}

if (is_array($estado)) {
    $elemento_tipo = $elemento['tipo'];
} else {
    $elemento_tipo = "Elemento no definido";
}

if (is_array($estado)) {
    $categoria_nombre = $categoria['categorias'];
} else {
    $categoria_nombre = "Categoría no definido";
}

if (is_array($estado)) {
    $fuente_poder = $fuentePoder['descripcion'];
} else {
    $fuente_poder = "Fuente de poder no definido";
}
function obtener_manuales($conn, $codigo){
    $query_obtener_grupo_id = "SELECT grupo_id FROM items WHERE codigo = ?";
    $grupo = ejecutar_query($conn, $query_obtener_grupo_id, [$codigo])->fetch(PDO::FETCH_ASSOC);
    $grupo_id = is_array($grupo) ? $grupo['grupo_id'] : null;
    $query_manual = "SELECT * FROM manuales WHERE grupo_id = ?";
    return ejecutar_query($conn, $query_manual, [$grupo_id])->fetchAll(PDO::FETCH_ASSOC);
}
$manuales = obtener_manuales($conn, $codigo);

function obtener_mantenimientos($conn, $codigo){
    $query_obtener_grupo_id = "SELECT grupo_id FROM items WHERE codigo = ?";
    $grupo = ejecutar_query($conn, $query_obtener_grupo_id, [$codigo])->fetch(PDO::FETCH_ASSOC);
    $grupo_id = is_array($grupo) ? $grupo['grupo_id'] : null;
    $query_manual = "SELECT * FROM mantenimiento WHERE grupo_id = ?";
    return ejecutar_query($conn, $query_manual, [$grupo_id])->fetchAll(PDO::FETCH_ASSOC);
}
$mantenimientos = obtener_mantenimientos($conn, $codigo);

function obtener_descripcion_codigo_man($conn, $id_codigo_man) {
    $query = "SELECT descripcion FROM man_codigo WHERE idman_codigo = ?";
    return ejecutar_query($conn, $query, [$id_codigo_man])->fetch(PDO::FETCH_ASSOC)['descripcion'];
}
// Función genérica para renderizar una fila con el label y el valor.
// Permite pasar las clases personalizadas para el contenedor del label y del valor.
function renderRow($label, $value, $labelClasses, $valueClasses, $useSpan = false) {
    $element = $useSpan ? 'span' : 'label';
    // Comprueba si el valor es numérico para asignar la clase correspondiente
    $isNumber = is_numeric($value);
    $valueClass = $isNumber ? 'number-value' : 'text-value';
    return "
        <div class='row'>
            <{$element} class='{$labelClasses}'>$label</{$element}>
            <div class='{$valueClasses}'>
                <span class='{$valueClass}'>" . htmlspecialchars($value) . "</span>
            </div>
        </div>
    ";
}
// Wrapper para la sección de Información (2 columnas)
function renderInformationRow($label, $value, $useSpan = false) {
    // Se usa 'col-6' para label y 'col-6' para el valor
    return renderRow($label, $value, 'col-6 fw-bold d-flex align-items-center', 'col-6 d-flex align-items-end', $useSpan);
}
// Wrapper para la sección de Detalles (4 columnas)
function renderDetailRow($label, $value, $useSpan = false) {
    return renderRow($label, $value, 'col-sm-6 fw-bold d-flex align-items-start  ps-2', 'col-sm-6 d-flex align-items-end ps-2', $useSpan);
}
// ----------------------------
// Ejemplo de arreglos de datos
$details = [
    'CODIGO'         => $item ? $item['codigo'] : "No disponible",
    'NOMBRE'         => $item ? $item['nombre'] : "No disponible",
    'DESCRIPCIÓN'    => $item ? $item['descripcion'] : "No disponible",
    'TIPO ELEMENTO'  => $elemento ? $elemento['tipo'] : "No disponible",
    'ESTADO'         => $estado ? $estado['descripcion'] : "No disponible",
    'CANTIDAD'       => $item ? $item['cantidad'] : "No disponible",
];

$columns = [
    [
        'ÁREA DE DESTINO'     => $area ? $area['descripcion'] : "No disponible",
        'COSTO UNITARIO'      => $item ? $item['costo'] : 0,
        'VALOR RESIDUAL'      => $item ? $item['valor_residual'] : 0,
        'COSTO MANTENIMIENTO' => $item ? $item['costo_mantenimiento'] : 0,
    ],
    [
        'FECHA ADQUISICIÓN'   => $item ? $item['fecha'] : "No disponible",
        'TIEMPO UTILIZACIÓN'  => $item ? $item['uso'] : 0,
        'TIEMPO VIDA ÚTIL'    => $item ? $item['vida'] : 0,
    ],
    [
        'SECCIÓN'             => $seccion ? $seccion['seccion']  : "No disponible",  
        'CATEGORÍA'           => $categoria ? $categoria['categorias'] : "No disponible",
        'OBSERVACIONES'       => $item ? $item['observaciones'] : "No disponible",
    ],
    [
        'FABRICANTE'          => $item ? $item['fabricante'] : "No disponible",
        'S/N'                 => $item ? $item['serial'] : "No disponible",
        'MODELO'              => $item ? $item['modelo'] : "No disponible",
        'AÑO FABRICACIÓN'     => $item ? $item['año_fabricacion'] : "No disponible",
        'FUENTE PODER'        => $fuentePoder ? $fuentePoder['descripcion'] : "No disponible"
    ]
];

$photos = [
    'photo1' => !empty($item['foto']) ? htmlspecialchars($item['foto']) : '/public/ico/material.svg',
    'photo2' => !empty($item['foto']) ? htmlspecialchars($item['foto']) : '/public/ico/material.svg'
];
?>

<main class="container-fluid mt-3">
    <!-- Sección de Información -->
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1 d-flex justify-content-between align-items-center">
                <strong>INFORMACIÓN</strong>
                <a href="/src/pages/nuevoItem/herramientas.php?tipo_item=herramientas&editar=1&codigo=<?= urlencode($item['codigo']) ?>" class="btn btn-warning btn-sm ms-2">
                    Editar
                </a>
            </div>
            <div class="card rounded-4 px-1 mb-3">
                <div class="row m-1">
                    <!-- Primera columna de información -->
                    <div class="col-10 col-sm-5 d-flex flex-column position-relative detail-row">   
                        <?php
                        echo renderInformationRow('CÓDIGO:', $details['CODIGO'], true);
                        echo renderInformationRow('NOMBRE:', $details['NOMBRE'], true);
                        echo renderInformationRow('DESCRIPCIÓN:', $details['DESCRIPCIÓN'], true);
                        ?>
                    </div>
                    <!-- Segunda columna de información -->
                    <div class="col-10 col-sm-5 d-flex flex-column detail-row">
                        <?php
                        echo renderInformationRow('TIPO ELEMENTO:', $details['TIPO ELEMENTO'], true); 
                        echo renderInformationRow('ESTADO:', $details['ESTADO'], true);
                        echo renderInformationRow('CANTIDAD:', $details['CANTIDAD'], true);
                        ?>
                    </div>
                    <!-- Imágenes (se muestran de forma adaptativa) -->
                    <div class="col-2 d-flex d-md-none justify-content-end align-items-start position-relative">
                        <img class="img-fluid product-image" src="<?= $photos['photo1']; ?>" alt="Imagen del material">
                    </div>
                    <div class="col-2 d-none d-md-flex align-items-start position-relative">
                        <img class="img-fluid product-image" src="<?= $photos['photo2']; ?>" alt="Imagen del material">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Sección de Detalles -->
    <section class="row">
        <div class="col-12">
            <div class="w-100 bg-plomo mb-2 p-1"><strong>DETALLES</strong></div>
            <div class="card rounded-4 px-3 py-1 mb-3">
                <div class="row row-cols-2 row-cols-md-4">
                    <?php foreach ($columns as $index => $column): ?>
                        <div class="col detail-row pe-1 <?php if ($index !== count($columns) - 1) echo 'separation-edge'; ?>">
                            <?php foreach ($column as $label => $value): ?>
                                <?= renderDetailRow($label . ':', $value, true); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <!-- Sección de Mantenimiento -->
    <div class="w-100 bg-plomo mb-2 p-1"><strong>MANTENIMIENTO</strong></div>
    <section class="row d-flex justify-content-around align-items-stretch">
        <!-- Primera Tarjeta Vigencia MANTENIMIENTO-->
        <div class="col-12 col-sm-5 d-flex align-items-stretch mb-3">
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
        <div class="col-12 col-sm-4 d-flex align-items-stretch mb-3">
            <div class="card rounded-4 px-3 py-2 flex-fill">
                <div class="row">
                    <div class="col-4 ">
                        <img class="img-fluid" src="/public/ico/manual.png" alt="Manual" style="width: 100px;">
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
        <div class="col-12 col-sm-3 d-flex align-items-stretch mb-3">
            <div class="card rounded-4 px-3 py-2 flex-fill">
                <div class="row">
                    <div class="col-12 text-center">
                        <span id="codigoQR" class="fw-bold">CÓDIGO QR</span>
                        <?php
                        if (!empty($item['qr_image_path'])) {
                            echo "<img class='img-fluid' style='max-width: 100px;' src='./../../" . htmlspecialchars($item['qr_image_path']) . "' alt='Código QR' >";
                        } else {
                            echo "<p>No hay código QR disponible.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
require './../../layout/footer.htm';
?> 

<script>
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

    var params = new URLSearchParams(window.location.search);
    var selectedValue = params.get("tipo_item");

    if (selectedValue === "herramientas") {
        herramientasFields?.classList.remove("d-none");
    }
});
</script>

</body>

</html>