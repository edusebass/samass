<?php
require_once './../db/dbconn.php';

// Definir los campos igual que en form_item.php (asociativo: campo => tipo)
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

$tabla = $_POST['tabla'] ?? '';
$id = $_POST['id'] ?? null;

if (!isset($tablas_campos[$tabla])) {
    die('Tabla no válida');
}

$campos = $tablas_campos[$tabla];
$valores = [];
foreach ($campos as $campo => $tipo) {
    if ($campo === 'codigo' || $campo === 'fotografia_url') continue;
    if ($tipo === 'checkbox') {
        $valores[$campo] = isset($_POST[$campo]) ? 1 : 0;
    } else {
        $valores[$campo] = $_POST[$campo] ?? null;
    }
}

// Funciones para código
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

// --- GUARDADO ---
if ($id) {
    // UPDATE
    // Obtener el código actual
    $stmt = $conn->prepare("SELECT codigo, fotografia_url FROM $tabla WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $codigo = $row['codigo'];
    $foto_actual = $row['fotografia_url'];

    // Manejo de imagen
    $ruta_imagen = $foto_actual;
    if (isset($_FILES['fotografia_url']) && $_FILES['fotografia_url']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['fotografia_url']['name'], PATHINFO_EXTENSION);
        $dir = "/public/$tabla";
        $ruta_relativa = "$dir/$codigo.$ext";
        $ruta_absoluta = $_SERVER['DOCUMENT_ROOT'] . $ruta_relativa;
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $dir)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . $dir, 0777, true);
        }
        move_uploaded_file($_FILES['fotografia_url']['tmp_name'], $ruta_absoluta);
        $ruta_imagen = $ruta_relativa;
    }

    $valores['fotografia_url'] = $ruta_imagen;
    $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($valores) + ['fotografia_url']));
    $sql = "UPDATE $tabla SET $set WHERE id = ?";
    $params = array_values($valores);
    $params[] = $ruta_imagen;
    $params[] = $id;
    $stmt = $conn->prepare("UPDATE $tabla SET " . implode(', ', array_map(fn($c) => "$c = ?", array_keys($valores))) . ", fotografia_url = ? WHERE id = ?");
    $stmt->execute([...array_values($valores), $ruta_imagen, $id]);
} else {
    // INSERT
    $codigo_auto = generarCodigo($conn, $tabla);

    // Manejo de imagen
    $ruta_imagen = null;
    if (isset($_FILES['fotografia_url']) && $_FILES['fotografia_url']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['fotografia_url']['name'], PATHINFO_EXTENSION);
        $dir = "/public/$tabla";
        $ruta_relativa = "$dir/$codigo_auto.$ext";
        $ruta_absoluta = $_SERVER['DOCUMENT_ROOT'] . $ruta_relativa;
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $dir)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . $dir, 0777, true);
        }
        move_uploaded_file($_FILES['fotografia_url']['tmp_name'], $ruta_absoluta);
        $ruta_imagen = $ruta_relativa;
    }

    foreach ($campos as $campo => $tipo) {
    if ($tipo === 'decimal') {
        // Si está vacío o no numérico, pon null
        if (!isset($valores[$campo]) || trim($valores[$campo]) === '' || $valores[$campo] === '-' || !is_numeric(str_replace(['$', ','], '', $valores[$campo]))) {
            $valores[$campo] = null;
        } else {
            // Limpiar $ y comas
            $valores[$campo] = str_replace(['$', ','], '', $valores[$campo]);
        }
    }
}
    $valores = array_merge(['codigo' => $codigo_auto], $valores, ['fotografia_url' => $ruta_imagen]);
    $cols = implode(', ', array_keys($valores));
    $placeholders = implode(', ', array_fill(0, count($valores), '?'));
    $sql = "INSERT INTO $tabla ($cols) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute(array_values($valores));
}

header("Location: inventario.php?tabla=" . urlencode($tabla));
exit;