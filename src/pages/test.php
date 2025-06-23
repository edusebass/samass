<?php
// test.php

// Configuración de la conexión a la base de datos
$servername = "127.0.0.1";
$username = "root";
$password = "SAM003";
$database = "samass";
$port = 3307;

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Si deseas ver mensaje de conexión exitosa, descomenta la siguiente línea:
    // echo "Conexión exitosa a la base de datos.<br>";
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}

// Función para ejecutar queries de forma parametrizada
function ejecutar_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

// Recibir el parámetro "codigo" por GET
$codigo = isset($_GET['codigo']) ? $_GET['codigo'] : null;

if ($codigo !== null && str_starts_with($codigo, 'M_')) {
    // Remover el prefijo "M_"
    $codigo = substr($codigo, 2);
}

// Función para obtener un item dado su código
function obtener_item($conn, $codigo) {
    $query_obtener_item = "SELECT * FROM items WHERE codigo = ?";
    return ejecutar_query($conn, $query_obtener_item, [$codigo]);
}

$item = obtener_item($conn, $codigo)->fetch(PDO::FETCH_ASSOC);

// Si no se encuentra el item, se muestra un mensaje de error y se detiene la ejecución
if (!$item) {
    die("No se encontró un item para el código: " . htmlspecialchars($codigo));
}

$cantidad = $item['cantidad'];
$costo_mantenimiento = $item['costo_mantenimiento']; // Suponiendo que este campo existe en la tabla

// Función genérica para obtener detalles por ID de otras tablas
function obtener_detalle_por_id($conn, $tabla, $campo_id, $id) {
    $query = "SELECT * FROM $tabla WHERE $campo_id = ?";
    return ejecutar_query($conn, $query, [$id])->fetch(PDO::FETCH_ASSOC);
}

// Obtener detalles basados en los IDs del registro de items
$estado    = obtener_detalle_por_id($conn, 'estado', 'idestado', $item['estado_id']);
$seccion   = obtener_detalle_por_id($conn, 'secciones', 'idsecciones', $item['seccion_id']);
$area      = obtener_detalle_por_id($conn, 'areas', 'idareas', $item['area_id']);
$elemento  = obtener_detalle_por_id($conn, 'elemento_tipo', 'idelementos', $item['elemento_id']);
$categoria = obtener_detalle_por_id($conn, 'categorias', 'idcategorias', $item['categoria_id']);
$fuentePoder = obtener_detalle_por_id($conn, 'man_fuentepoder', 'idfuentepoder', $item['id_fuentepoder']);

// Función para obtener la descripción del código de mantenimiento
function obtener_descripcion_codigo_man($conn, $id_codigo_man) {
    $query = "SELECT descripcion FROM man_codigo WHERE idman_codigo = ?";
    return ejecutar_query($conn, $query, [$id_codigo_man])->fetch(PDO::FETCH_ASSOC)['descripcion'];
}

// Función para renderizar una fila de información
function renderInformationRow($label, $value, $useSpan = false) {
    $element = $useSpan ? 'span' : 'label';
    $isNumber = is_numeric($value);
    $valueClass = $isNumber ? 'number-value' : 'text-value';
    return "
    <div class='row my-2'>
        <$element class='col-6 fw-bold'>$label</$element>
        <div class='col-6'>
            <span class='$valueClass'>" . htmlspecialchars($value) . "</span>
        </div>
    </div>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Prueba de Conexión y Datos</title>
    <!-- Usamos Bootstrap 5 para estilos rápidos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-4">
        <h1 class="mb-4">Prueba de Conexión y Consulta de Item</h1>
        <h3>Datos Generales</h3>
        <?php
            echo renderInformationRow('Código', $codigo);
            echo renderInformationRow('Cantidad', $cantidad);
            echo renderInformationRow('Costo Mantenimiento', $costo_mantenimiento);
            echo renderInformationRow('Estado', $estado['nombre'] ?? 'N/D');
            echo renderInformationRow('Sección', $seccion['nombre'] ?? 'N/D');
            echo renderInformationRow('Área', $area['nombre'] ?? 'N/D');
            echo renderInformationRow('Elemento', $elemento['nombre'] ?? 'N/D');
            echo renderInformationRow('Categoría', $categoria['nombre'] ?? 'N/D');
            echo renderInformationRow('Fuente de Poder', $fuentePoder['descripcion'] ?? 'N/D');
        ?>

        <!-- Si necesitas agregar más pruebas, como la obtención de mantenimiento, manuales, etc., puedes hacerlo aquí -->
        <hr>
        <h3>Prueba de Función de Mantenimiento</h3>
        <?php
        // Aquí puedes incluir tu código para probar la función de mantenimiento y barra de progreso.
        // Por ejemplo, si quieres simular un mantenimiento, puedes asignar una fecha y un tipo.
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
                    $dias_totales = 1; // valor mínimo para evitar división por cero
                    break;
            }

            $dias_transcurridos = $intervalo->days;
            $progreso = ($dias_transcurridos / $dias_totales) * 100;
            $progreso = min(100, max(0, $progreso));
            return $progreso;
        }
        
        // Datos de prueba para mantenimiento (puedes ajustar la fecha o tipo)
        $fecha_prueba = "2025-03-02";
        $tipo_mantenimiento_prueba = "Diario";  // Puedes probar con 'Semanal','Diario', etc.
        $progreso = calcular_progreso_mantenimiento($fecha_prueba, $tipo_mantenimiento_prueba);
        ?>
        <p>Fecha de creación de mantenimiento de prueba: <?= htmlspecialchars($fecha_prueba); ?></p>
        <p>Tipo de mantenimiento: <?= htmlspecialchars($tipo_mantenimiento_prueba); ?></p>
        <div class="progress mb-2" style="height: 25px;">
            <div class="progress-bar" role="progressbar" style="width: <?= $progreso; ?>%;" aria-valuenow="<?= $progreso; ?>" aria-valuemin="0" aria-valuemax="100">
                <?= round($progreso, 2); ?>%
            </div>
        </div>
        <?php
            if ($progreso >= 100) {
                echo "<div class='alert alert-danger'>El mantenimiento debe realizarse ya.</div>";
            } elseif ($progreso >= 80) {
                echo "<div class='alert alert-warning'>El mantenimiento está próximo a vencer.</div>";
            }
        ?>

    </div>
    <!-- Bootstrap JS (opcional, para componentes interactivos) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
