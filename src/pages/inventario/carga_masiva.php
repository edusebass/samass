<?php
/**
 * Carga Masiva - Importación desde Excel
 * 
 * Permite cargar datos masivamente desde archivos Excel al inventario.
 * Procesa múltiples hojas y valida los datos antes de insertarlos.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

require './../../layout/head.html';
require './../../layout/header.php';
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';
require_once './../../../vendor/autoload.php'; // Ruta corregida al vendor

use PhpOffice\PhpSpreadsheet\IOFactory;

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

// Definición de los campos por tabla (igual que en tu sistema)
$mapeo_cabeceras = [
    'equipo_seguridad' => [
        'Descripción del artículo' => 'descripcion',
        'UOM' => 'unidad_medida',
        'Cantidad' => 'cantidad',
        'Costo unitario estimado' => 'costo_unitario_estimado',
        'Marca' => 'marca',
        'Modelo' => 'modelo',
        'Estado actual' => 'estado_actual',
        'Año de adquisición' => 'anio_adquisicion',
        'Tiempo de vida útil sugerido' => 'vida_util_sugerida',
        'Fotografía' => 'fotografia_url',
        'Observaciones' => 'observaciones',
        // ...agrega más si es necesario...
    ],
    'habitacion_huesped_betel' => [
        'Descripción del artículo' => 'descripcion',
        'UOM' => 'unidad_medida',
        'Cantidad' => 'cantidad',
        'Costo unitario estimado' => 'costo_unitario_estimado',
        'Marca' => 'marca',
        'Modelo' => 'modelo',
        'Serie' => 'numero_serie',
        'Año de adquisición' => 'anio_adquisicion',
        'Tiempo de vida útil sugerido' => 'vida_util_sugerida',
        'Fotografía' => 'fotografia_url',
        'Observaciones' => 'observaciones',
    ],
    'herramientas_equipo_jardineria' => [
        'Descripción del artículo' => 'descripcion',
        'Cantidad' => 'cantidad',
        'Costo unitario estimado' => 'costo_unitario_estimado',
        'Marca' => 'marca',
        'Modelo' => 'modelo',
        'Serie' => 'numero_serie',
        'Estado actual' => 'estado_actual',
        'Año de adquisición' => 'anio_adquisicion',
        'Tiempo de vida útil sugerido' => 'vida_util_sugerida',
        'Fotografía' => 'fotografia_url',
        'Observaciones' => 'observaciones',
    ],
    'herramientas_manuales' => [
        'Descripción del artículo' => 'descripcion',
        'Cantidad' => 'cantidad',
        'Costo unitario estimado' => 'costo_unitario_estimado',
        'Marca' => 'marca',
        'Estado actual' => 'estado_actual',
        'Año de adquisición' => 'anio_adquisicion',
        'Tiempo de vida útil sugerido' => 'vida_util_sugerida',
        'Fotografía' => 'fotografia_url',
        'Observaciones' => 'observaciones',
    ],
    'maquinas' => [
        'Descripción del artículo' => 'descripcion',
        'UOM' => 'unidad_medida',
        'Cantidad' => 'cantidad',
        'Costo unitario estimado' => 'costo_unitario_estimado',
        'Marca' => 'marca',
        'Modelo' => 'modelo',
        'Serie' => 'numero_serie',
        'Estado actual' => 'estado_actual',
        '¿Se ha tenido que reparar? Si/no' => 'reparado',
        'Costo de reparación' => 'costo_reparacion',
        'Año de adquisición' => 'anio_adquisicion',
        'Tiempo de vida útil (grupo de construcción)' => 'vida_util_anios',
        'Garantia del fabricante' => 'garantia_fabricante',
        'Fotografía' => 'fotografia_url',
        'Observaciones' => 'observaciones',
    ],
    'items_generales_por_edificio' => [
        'Código del elemento **' => 'codigo',
        'Nombre del elemento' => 'nombre_elemento',
        'Cantidad' => 'cantidad',
        'Costo unitario estimado' => 'costo_unitario_estimado',
        'Marca' => 'marca',
        'Modelo' => 'modelo',
        'Serie' => 'numero_serie',
        'Detalles adicionales (color, tamaño, etc)' => 'detalles_adicionales',
        'Estado actual' => 'estado_actual',
        'Lugar donde se almacena' => 'lugar_almacenamiento',
        'Año de adquisición' => 'anio_adquisicion',
        'Tiempo de vida útil sugerido' => 'vida_util_sugerida',
        'Tiempo de uso' => 'tiempo_uso',
        'Costo de Mantenimiento mensual*' => 'costo_mantenimiento_mensual',
        'Observaciones BAS' => 'observaciones_bas',
        'Observaciones Secretaria OM' => 'observaciones_secretaria_om',
    ]
];

// Funciones para generar código
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

// Procesamiento del archivo Excel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
    $archivoTmp = $_FILES['archivo_excel']['tmp_name'];
    $spreadsheet = IOFactory::load($archivoTmp);

    foreach ($tablas_campos as $tabla => $campos) {
        if (!$spreadsheet->sheetNameExists($tabla)) continue;
        $sheet = $spreadsheet->getSheetByName($tabla);
        $rows = $sheet->toArray(null, true, true, true);

        // Saltar las dos primeras filas (títulos), la tercera es encabezado
        $titulo1 = array_shift($rows);
        $titulo2 = array_shift($rows);
        $headerRow = array_shift($rows);

        // Mapeo de cabeceras a campos
        $map = $mapeo_cabeceras[$tabla];

        // Construir mapeo: letra columna ('A', 'B', ...) => campo tabla
        $colToCampo = [];
        foreach ($headerRow as $colKey => $headerName) {
            $headerName = trim($headerName);
            if (isset($map[$headerName])) {
                $colToCampo[$colKey] = $map[$headerName];
            }
        }

        foreach ($rows as $row) {
            // Ignorar filas completamente vacías
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;

            $valores = [];
            // Llenar los valores según el mapeo columna => campo
            foreach ($colToCampo as $colKey => $campo) {
                if ($campo === 'codigo' && $tabla !== 'items_generales_por_edificio') continue;
                $valores[$campo] = isset($row[$colKey]) ? $row[$colKey] : null;
            }
            // Completar los campos que no vinieron en el Excel con null
            foreach ($campos as $campo => $tipo) {
                if (!array_key_exists($campo, $valores) && !($campo === 'codigo' && $tabla !== 'items_generales_por_edificio')) {
                    $valores[$campo] = null;
                }
            }
            // Limpiar valores decimales
            foreach ($campos as $campo => $tipo) {
                if ($tipo === 'decimal' && isset($valores[$campo])) {
                    $valores[$campo] = str_replace(['$', ','], '', $valores[$campo]);
                    $valores[$campo] = is_numeric($valores[$campo]) ? $valores[$campo] : null;
                }
            }
            // Limpiar valores decimales
            foreach ($campos as $campo => $tipo) {
                if ($tipo === 'decimal' && isset($valores[$campo])) {
                    $valores[$campo] = str_replace(['$', ','], '', $valores[$campo]);
                    $valores[$campo] = is_numeric($valores[$campo]) ? $valores[$campo] : null;
                }
                // Limpiar valores numéricos y años
                if (($tipo === 'number' || $tipo === 'year') && isset($valores[$campo])) {
                    if (trim($valores[$campo]) === '-' || trim($valores[$campo]) === '') {
                        $valores[$campo] = null;
                    } elseif (!is_numeric($valores[$campo])) {
                        $valores[$campo] = null;
                    }
                }
            }
            // Generar código si no es items_generales_por_edificio
            if ($tabla !== 'items_generales_por_edificio') {
                $valores = array_merge(['codigo' => generarCodigo($conn, $tabla)], $valores);
            }
            // Insertar en la base de datos
            $cols = implode(', ', array_keys($valores));
            $placeholders = implode(', ', array_fill(0, count($valores), '?'));
            $sql = "INSERT INTO $tabla ($cols) VALUES ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->execute(array_values($valores));
        }
    }
    echo "<div class='alert alert-success'>Carga masiva completada correctamente.</div>";
}
?>

<main class="container mt-4">
    <h4>Carga masiva de inventario desde Excel</h4>
    <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Selecciona el archivo Excel:</label>
            <input type="file" name="archivo_excel" class="form-control" accept=".xlsx,.xls" required>
        </div>
        <button type="submit" class="btn btn-success">Cargar</button>
    </form>
    <div class="alert alert-info mt-3">
        El archivo debe tener una hoja por cada tabla, con los nombres de las pestañas exactamente igual a:<br>
    b><?php echo implode(', ', array_keys($mapeo_cabeceras)); ?></b><br>
        En <b>items_generales_por_edificio</b> el campo <b>codigo</b> debe venir en el Excel, en las demás se generará automáticamente.
    </div>
</main>
<?php require './../../layout/footer.htm'; ?>