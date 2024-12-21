<?php
require './../layout/head.html';
require './../layout/header.php'; 
require './../utils/session_check.php';
require_once './../db/dbconn.php';
require './../utils/ejecutar_query.php';

$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : null;

function obtener_item($conn, $codigo) {
    $query_obtener_item = "SELECT * FROM items WHERE codigo = ?";
    return ejecutar_query($conn, $query_obtener_item, [$codigo]);
}

$item = obtener_item($conn, $codigo)->fetch(PDO::FETCH_ASSOC);

// Función para calcular el progreso del mantenimiento
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
    <title>SAM Assistant</title>
    <link rel="stylesheet" type="text/css" href="css/gestionBodega.css">
</head>
<body>
<DIV style="text-align:left; background-color: #e8ecf2; color:#5C6872;">INFORMACIONES</DIV>    
    <section class="d-flex flex-row card rounded-4 px-3 mb-3 gap-3 border-default">
        <div class="d-flex flex-column custom-label">
            <label for="" >CODIGO: <?php echo htmlspecialchars($item['codigo']); ?></label>
            <label for="">NOMBRE: <?php echo htmlspecialchars($item['nombre']); ?></label>
            <label for="">DESCRIPCION: <?php echo htmlspecialchars($item['descripccion']); ?></label>
        </div>
        <div class="d-flex flex-column custom-label">
        <label for="">TIPO ELEMENTO: <?php echo htmlspecialchars($elemento['tipo']); ?></label>
            <label for="">ESTADO: <?php echo htmlspecialchars($estado['descripcion']); ?></label>
            <label for="">CANTIDAD: <?php echo htmlspecialchars($item['cantidad']); ?></label>
            <!-- <label for="">LUGAR: <?php echo htmlspecialchars($item['lugar']); ?> </label> -->
        </div>
    </section>
    <h2>DETALLES</h2>    
    <section class="d-flex flex-row card rounded-4 px-3 mb-3 gap-3 border-default">
        <div class="d-flex flex-column custom-label">
            <label for="">COSTO UNITARIO: <?php echo htmlspecialchars($item['costo']); ?> </label>
            <label for="">VALOR RESIDUAL:<?php echo htmlspecialchars($item['valor_residual']); ?></label>
            <label for="">COSTO MANTEN: <?php echo htmlspecialchars($item['costo_mantenimiento']); ?></label>
        </div>
        <div class="d-flex flex-column  custom-label">
            <label for="">FECHA ADQUISION: <?php echo htmlspecialchars($item['fecha']); ?></label>
            <label for="">TIEMPO UTILIZACION: <?php echo htmlspecialchars($item['uso']); ?></label>
            <label for="">TIEMPO VIDA UTIL <?php echo htmlspecialchars($item['vida']); ?></label>
        </div>
        <div class="d-flex flex-column  custom-label">
            <label for="">FABRICANTE: <?php echo htmlspecialchars($item['fabricante']); ?></label>
            <label for="">S/N <?php echo htmlspecialchars($item['serial']); ?></label>
            <label for="">MODELO: <?php echo htmlspecialchars($item['modelo']); ?></label>
        </div>
        <div class="d-flex flex-column  custom-label">
            <label for="">AÑO FABRICACION: <?php echo htmlspecialchars($item['año_fabricacion']); ?></label>
            <label for="">FUENTE PODER: <?php echo htmlspecialchars($fuentePoder['descripcion']); ?></label>
        </div>
    </section>
    <h2>MANTENIMIENTO</h2>    
<section class="d-flex flex-row justify-content-between align-items-stretch w-100">
    <div class="d-flex flex-column card flex-grow-1 rounded-4 px-3 mb-3 border-default mx-2">
        <label for="">VIGENTE SI</label>
        <?php
            if ($mantenimientos ) {
                foreach ($mantenimientos as $mantenimiento) {
                    $descripcion_codigo_man = obtener_descripcion_codigo_man($conn, $mantenimiento['id_codigo_man']);
                    $progreso = calcular_progreso_mantenimiento($mantenimiento['fecha_creacion'], $descripcion_codigo_man);
                    echo "<p>Notas: " . htmlspecialchars($mantenimiento['notas']) . "</p>";
                    echo "<p>Descripción del Código de Mantenimiento: " . htmlspecialchars($descripcion_codigo_man) . "</p>";
                    echo "<div class='progress'>
                            <div class='progress-bar' role='progressbar' style='width: " . $progreso . "%;' aria-valuenow='" . $progreso . "' aria-valuemin='0' aria-valuemax='100'></div>
                          </div>";
                    if ($progreso >= 100) {
                        echo "<script>alert('El mantenimiento debe realizarse ya.');</script>";
                    } elseif ($progreso >= 80) {
                        echo "<script>alert('El mantenimiento está próximo a vencer.');</script>";
                    }
                }
                
            }
        ?>
    </div>
    
    <div class="d-flex flex-row flex-grow-1 gap-3 card rounded-4 px-3 mb-3 border-default mx-2">
        <div class="d-flex flex-column custom-label">
            <label for="">MANUALES</label>
            <img src="/public/ico/manual.png" alt="" style="width: 70px;">
        </div>
        <div class="d-flex flex-column custom-label">
            <?php
            if ($manuales) {
                foreach ($manuales as $manual) {
                    echo "<p>Titulo: " . htmlspecialchars($manual['titulo']) . "</p>";
                    echo "<p>Enlace: <a href='./../../" . htmlspecialchars($manual['enlace']) . "' download>" . htmlspecialchars($manual['enlace']) . "</a></p>";
                }
            }
            ?>
        </div>
    </div>
    <div class="d-flex flex-column card flex-grow-1 rounded-4 px-3 mb-3 border-default mx-2">
        <label for="" class="custom-label" >CODIGO QR</label>
        <img width="250" src="./../../<?php echo htmlspecialchars($item['qr_image_path']); ?>" alt="Código QR">
    </div>

</section>

    
</body>

</html>