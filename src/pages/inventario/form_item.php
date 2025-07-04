<?php
/**
 * Form Item - Formulario dinámico para crear/editar items de inventario
 *
 * Descripción:
 * Formulario universal que se adapta a cualquier tabla de inventario.
 * Genera campos dinámicamente según el tipo de tabla seleccionada.
 *
 * @author  SAM Assistant Team
 * @version 1.0
 * @since   2025-07-04
 */
require './../../layout/head.html';
require './../../layout/header.php';
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

// Definición de los campos y tipos por tabla
$tablas_campos = [
    'equipo_seguridad' => [
        'codigo' => 'text',
        'descripcion' => 'textarea',
        'unidad_medida' => 'text',
        'cantidad' => 'number',
        'costo_unitario_estimado' => 'decimal',
        'marca' => 'text',
        'modelo' => 'text',
        'estado_actual' => 'text',
        'anio_adquisicion' => 'year',
        'vida_util_sugerida' => 'number',
        'fotografia_url' => 'text',
        'observaciones' => 'textarea'
    ],
    'habitacion_huesped_betel' => [
        'codigo' => 'text',
        'descripcion' => 'textarea',
        'unidad_medida' => 'text',
        'cantidad' => 'number',
        'costo_unitario_estimado' => 'decimal',
        'marca' => 'text',
        'modelo' => 'text',
        'numero_serie' => 'text',
        'anio_adquisicion' => 'year',
        'vida_util_sugerida' => 'number',
        'fotografia_url' => 'text',
        'observaciones' => 'textarea'
    ],
    'herramientas_equipo_jardineria' => [
        'codigo' => 'text',
        'descripcion' => 'textarea',
        'cantidad' => 'number',
        'costo_unitario_estimado' => 'decimal',
        'marca' => 'text',
        'modelo' => 'text',
        'numero_serie' => 'text',
        'estado_actual' => 'text',
        'anio_adquisicion' => 'year',
        'vida_util_sugerida' => 'number',
        'fotografia_url' => 'text',
        'observaciones' => 'textarea'
    ],
    'herramientas_manuales' => [
        'codigo' => 'text',
        'descripcion' => 'textarea',
        'cantidad' => 'number',
        'costo_unitario_estimado' => 'decimal',
        'marca' => 'text',
        'estado_actual' => 'text',
        'anio_adquisicion' => 'year',
        'vida_util_sugerida' => 'number',
        'fotografia_url' => 'text',
        'observaciones' => 'textarea'
    ],
    'maquinas' => [
        'codigo' => 'text',
        'codigo_item' => 'text',
        'descripcion' => 'textarea',
        'unidad_medida' => 'text',
        'cantidad' => 'number',
        'costo_unitario_estimado' => 'decimal',
        'marca' => 'text',
        'modelo' => 'text',
        'numero_serie' => 'text',
        'estado_actual' => 'text',
        'reparado' => 'checkbox',
        'costo_reparacion' => 'decimal',
        'anio_adquisicion' => 'year',
        'vida_util_anios' => 'number',
        'garantia_fabricante' => 'text',
        'fotografia_url' => 'text',
        'observaciones' => 'textarea'
    ],
    'items_generales_por_edificio' => [
        'codigo' => 'text',
        'nombre_elemento' => 'text',
        'cantidad' => 'number',
        'costo_unitario_estimado' => 'decimal',
        'marca' => 'text',
        'modelo' => 'text',
        'numero_serie' => 'text',
        'detalles_adicionales' => 'textarea',
        'estado_actual' => 'text',
        'lugar_almacenamiento' => 'text',
        'anio_adquisicion' => 'year',
        'vida_util_sugerida' => 'number',
        'tiempo_uso' => 'text',
        'costo_mantenimiento_mensual' => 'decimal',
        'observaciones_bas' => 'textarea',
        'observaciones_secretaria_om' => 'textarea'
    ]
];


// Si no se ha seleccionado tabla, mostrar selector y salir
if (!isset($_GET['tabla'])) {
    ?>
    <main class="container mt-4">
        <h4>Nuevo ítem</h4>
        <form method="get" action="form_item.php">
            <div class="mb-3">
                <label class="form-label">Selecciona el tipo de ítem que deseas crear:</label>
                <select name="tabla" class="form-select" required>
                    <option value="">-- Selecciona una opción --</option>
                    <?php foreach ($tablas_campos as $key => $fields): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>">
                            <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Continuar</button>
        </form>
    </main>
    <?php
    require './../../layout/footer.htm';
    exit;
}

// Ahora sí, después de asegurarte que hay tabla:
$tabla = $_GET['tabla'] ?? '';
$id = $_GET['id'] ?? null;

if (!isset($tablas_campos[$tabla])) {
    die('Tabla no válida');
}

$campos = $tablas_campos[$tabla];
$datos = array_fill_keys(array_keys($campos), '');

// Si es edición, carga los datos
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM $tabla WHERE id = ?");
    $stmt->execute([$id]);
    $datos_db = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($datos_db) {
        foreach ($datos as $k => $v) {
            $datos[$k] = $datos_db[$k] ?? '';
        }
    }
}

// Renderiza el input adecuado según el tipo
function renderInput($campo, $tipo, $valor, $tabla = '', $codigo = '') {
    $label = ucwords(str_replace('_', ' ', $campo));
    $valor = htmlspecialchars($valor ?? '');

    // Define aquí los campos opcionales (puedes agregar más si lo deseas)
    $opcionales = ['observaciones', 'observaciones_bas', 'observaciones_secretaria_om', 'fotografia_url', 'detalles_adicionales', 'tiempo_uso'];

    // Todos los demás serán obligatorios
    $required = !in_array($campo, $opcionales) ? 'required' : '';

    if ($campo === 'fotografia_url') {
        $preview = '';
        if ($valor) {
            $preview = "<div class='mb-2'><img src='$valor' alt='Foto actual' style='max-width:120px;max-height:120px;'></div>";
        }
        return "<div class='mb-3'><label class='form-label'>$label</label>
            $preview
            <input type='file' class='form-control' name='fotografia_url' accept='image/*' $required></div>";
    }
    switch ($tipo) {
        case 'textarea':
            return "<div class='mb-3'><label class='form-label'>$label</label>
                <textarea class='form-control' name='$campo' $required>$valor</textarea></div>";
        case 'number':
            return "<div class='mb-3'><label class='form-label'>$label</label>
                <input type='number' class='form-control' name='$campo' value='$valor' $required></div>";
        case 'decimal':
            return "<div class='mb-3'><label class='form-label'>$label</label>
                <input type='number' step='0.01' class='form-control' name='$campo' value='$valor' $required></div>";
        case 'year':
            return "<div class='mb-3'><label class='form-label'>$label</label>
                <input type='number' min='1900' max='2100' class='form-control' name='$campo' value='$valor' $required></div>";
        case 'checkbox':
            $checked = $valor ? 'checked' : '';
            // Los checkbox normalmente no son required, pero puedes agregarlo si lo deseas
            return "<div class='mb-3 form-check'><input type='checkbox' class='form-check-input' name='$campo' value='1' $checked>
                <label class='form-check-label'>$label</label></div>";
        default:
            return "<div class='mb-3'><label class='form-label'>$label</label>
                <input type='text' class='form-control' name='$campo' value='$valor' $required></div>";
    }
}

// Genera el prefijo según la tabla
function obtenerPrefijoTabla($tabla) {
    $prefijos = [
        'equipo_seguridad' => '001',
        'habitacion_huesped_betel' => '002',
        'herramientas_equipo_jardineria' => '003',
        'herramientas_manuales' => '004',
        'maquinas' => '005',
        'items_generales_por_edificio' => '006'
    ];
    return $prefijos[$tabla] ?? '999';
}

// Genera el siguiente código disponible para la tabla (solo para referencia, no se usa aquí)
function generarCodigo($conn, $tabla) {
    $prefijo = obtenerPrefijoTabla($tabla);
    $stmt = $conn->prepare("SELECT codigo FROM $tabla WHERE codigo IS NOT NULL AND codigo != ''");
    $stmt->execute();
    $codigos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $max = 0;
    foreach ($codigos as $codigo) {
        if (preg_match('/^003-' . $prefijo . '-(\d{3})$/', $codigo, $m)) {
            $num = intval($m[1]);
            if ($num > $max) $max = $num;
        }
    }
    $nuevo_num = str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    return "003-$prefijo-$nuevo_num";
}


$campos = $tablas_campos[$tabla];
$datos = array_fill_keys(array_keys($campos), '');

// Si es edición, carga los datos
if ($id) {
    $stmt = $conn->prepare("SELECT * FROM $tabla WHERE id = ?");
    $stmt->execute([$id]);
    $datos_db = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($datos_db) {
        foreach ($datos as $k => $v) {
            $datos[$k] = $datos_db[$k] ?? '';
        }
    }
}
?>
<main class="container mt-4">
    <h4><?php echo $id ? 'Editar' : 'Crear'; ?> registro en <?php echo ucwords(str_replace('_', ' ', $tabla)); ?></h4>
    <form action="guardar_item.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="tabla" value="<?php echo htmlspecialchars($tabla); ?>">
        <?php if ($id): ?>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            <!-- Mostrar el código solo como texto, no editable -->
            <?php if (!empty($datos['codigo'])): ?>
                <div class="mb-3">
                    <label class="form-label">Código</label>
                    <div class="form-control-plaintext" style="font-weight:bold;"><?php echo htmlspecialchars($datos['codigo']); ?></div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php foreach ($campos as $campo => $tipo): ?>
            <?php
            if ($campo === 'codigo') continue; // No mostrar el input de código nunca
            if ($id && $campo === 'codigo_item') continue; // No mostrar codigo_item en edición
            // Pasar tabla y código para renderInput solo si es fotografia_url
            if ($campo === 'fotografia_url') {
                echo renderInput($campo, $tipo, $datos[$campo] ?? '', $tabla, $datos['codigo'] ?? '');
            } else {
                echo renderInput($campo, $tipo, $datos[$campo] ?? '');
            }
            ?>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-success">Guardar</button>
        <a href="inventario.php?tabla=<?php echo htmlspecialchars($tabla); ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</main>
<?php require './../../layout/footer.htm'; ?>