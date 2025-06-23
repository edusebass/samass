<?php
require './../../layout/head.html';
require './../../layout/header.php';
require './../../db/dbconn.php';
require './../../utils/session_check.php';
require_once './../../../library/phpqrcode/qrlib.php';
$titlepage = 'nuevoitem';

$docs = array_diff(scandir('./../../../docs/manuales'), array('.', '..'));
function ejecutar_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

function obtener_secciones($conn) {
    $query = "SELECT idsecciones, seccion FROM secciones order by seccion ASC";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultados_secciones = obtener_secciones($conn);

function obtener_fuente($conn) {
    $query = "SELECT idfuentepoder, descripcion FROM `man_fuentepoder`";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultados_fuente = obtener_fuente($conn);

function obtener_areas($conn) {
    $query = "SELECT idareas, descripcion FROM areas order by descripcion ASC";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_areas = obtener_areas($conn);

function obtener_categorias($conn) {
    $query = "SELECT idcategorias, categorias FROM categorias order by categorias ASC";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_categorias = obtener_categorias($conn);

function obtener_estados ($conn) {
    $query = "SELECT idestado, descripcion FROM estado WHERE idestado LIKE 'B%'";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_estados = obtener_estados($conn);

function obtener_elementos ($conn) {
    $query = "SELECT idelementos, tipo FROM elemento_tipo";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_elementos = obtener_elementos($conn);

function obtener_man_tipo ($conn) {
    $query = "SELECT idman_tipo, descripcion FROM man_tipo";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_man_tipo = obtener_man_tipo($conn);

function obtener_man_codigo ($conn) {
    $query = "SELECT idman_codigo, descripcion FROM man_codigo";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_man_codigo = obtener_man_codigo($conn);

function obtener_man_estado ($conn) {
    $query = "SELECT idestado, descripcion FROM estado WHERE idestado LIKE 'M%'";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_man_estado = obtener_man_estado($conn);

function obtener_manuales_tipo($conn) {
    $query = "SELECT id_manuales_tipo, manuales_tipo_descripcion FROM manuales_tipo";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_manuales_tipo = obtener_manuales_tipo($conn);

function generateQR($codigo) {
    $qrImagePath = './../../../docs/qr_codes/item_' . $codigo . '.png';
    
    if (!file_exists('qr_codes')) {
        mkdir('qr_codes', 0777, true);
    }
    
    QRcode::png($codigo, $qrImagePath, QR_ECLEVEL_L, 10);
    
    return ['code' => $codigo, 'path' => $qrImagePath];
}

$error_message = '';
$qrGeneratedMessage = '';



if (isset($_POST['submit_item'])) {
    // Validar que los campos necesarios estén presentes para herramientas.
    if (empty($_POST['codigo']) || 
        empty($_POST['nombre']) ||
        empty($_POST['descripcion']) ||
        $_POST['elemento_id'] === '0'||
        $_POST['estado_id'] === '0' ||
        empty($_POST['cantidad']) ||
        empty($_POST['costo']) ||
        $_POST['area_id'] === '0' ||
        empty($_POST['fecha']) ||
        $_POST['seccion_id'] === '0' ||
        $_POST['categoria_id'] === '0' ||
        empty($_POST['vida']) ||
        empty($_POST['observaciones']) ||
        empty($_POST['fabricante']) ||
        empty($_POST['serial'])||
        empty($_POST['modelo']) ||
        empty($_POST['año_fabricacion'])
        ) {
        
        $error_message = "Por favor, complete todos los campos requeridos para continuar.";
    } else {
        try {
            $grupo_id =  rand(1, 10000);// Generación de grupo
            $conn->beginTransaction();
            error_log(print_r($_POST, true));
            $stmt = $conn->query("SELECT MAX(iditems) AS max_id FROM items");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastId = $row['max_id'];
            $itemId = $conn->lastInsertId(); 

            // Lógica para insertar en la tabla manuales
            if (isset($_POST['manual'])) {     
                $id_manuales_tipo = $_POST['id_manuales_tipo'];
                $manual_files = $_FILES['manual_file'];
                for ($j = 0; $j < count($id_manuales_tipo); $j++) {
                    if (!empty($id_manuales_tipo[$j]) && $manual_files['error'][$j] == UPLOAD_ERR_OK) {
                        $manual_dir = './../../docs/manuales/';
                        if (!file_exists($manual_dir)) {
                            mkdir($manual_dir, 0777, true);
                        }
                        $manual_path = $manual_dir . basename($manual_files['name'][$j]);
                        if (!move_uploaded_file($manual_files['tmp_name'][$j], $manual_path)) {
                            throw new Exception("Error al subir el manual");
                        }
                    $query_manuales = "INSERT INTO manuales (id_manuales_tipo, manual_file, grupo_id) VALUES (?, ?, ?)";
                    $params_manual = [
                        $id_manuales_tipo[$j], $manual_path, $grupo_id
                    ];
                    ejecutar_query($conn, $query_manuales, $params_manual);
                    }
                }
            }

            // Logica para insertar en la tabla mantenimiento
            if (isset($_POST['mantenimiento']) && $_POST['mantenimiento'][0] === 'Si') {
                $man_tipos = $_POST['man_tipo'];
                $man_codigos = $_POST['man_codigo'];
                $notas_mantenimientos = $_POST['notas_mantenimiento'];

                for ($j = 0; $j < count($man_tipos); $j++) {
                    if (!empty($man_tipos[$j]) && $man_tipos[$j] !== '0') {
                        $query_mantenimiento = "INSERT INTO mantenimiento (id_tipo, id_codigo_man, notas, grupo_id, fecha_creacion) VALUES (?, ?, ?, ?, NOW())";
                        $params_mantenimiento = [
                            $man_tipos[$j], $man_codigos[$j], $notas_mantenimientos[$j], $grupo_id
                        ];
                        ejecutar_query($conn, $query_mantenimiento, $params_mantenimiento);
                    }
                }
            }

            // Manejar la subida de la foto si se proporciona
            $foto_path = null;
            if (isset($_FILES['foto_path']) && $_FILES['foto_path']['error'] == UPLOAD_ERR_OK) {
                $foto_dir = './../../docs/herramientas_fotos/';
                if (!file_exists($foto_dir)) {
                    mkdir($foto_dir, 0777, true);
                }
                $foto_path = $foto_dir . basename($_FILES['foto_path']['name']);
                if (!move_uploaded_file($_FILES['foto_path']['tmp_name'], $foto_path)) {
                    throw new Exception("Error al subir la foto de la herramienta");
                }
            } else {
                // Usar una imagen predeterminada si no se proporciona una
                $foto_path = './../../public/ico/herramienta.svg';
            }
            // Generación de QR
            $qrData = generateQR($_POST['codigo']);  

            // Inserción en la tabla items
            $query = "INSERT INTO items (nombre, descripcion, estado_id, costo, fecha, vida, observaciones, seccion_id, categoria_id, area_id, elemento_id, codigo, qr_image_path, fabricante, serial, año_fabricacion, id_fuentepoder, modelo, valor_residual, grupo_id, foto_path, cantidad) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            
            // Convertir vida a horas, calcular valor residual y validar campos numéricos, etc.
            $vida = intval($_POST['vida']);
            $vida_util_en_horas = $vida * 720;
            $cantidad = intval($_POST['cantidad']);
            $costo = floatval($_POST['costo']);
            $valor_residual = round($costo * 0.20, 2); // Calcula el valor residual
            
                
            $params = [
                $_POST['nombre'], $_POST['descripcion'], $_POST['estado_id'],
                $costo, $_POST['fecha'],
                $vida_util_en_horas, 
                $_POST['observaciones'], $_POST['seccion_id'], 
                $_POST['categoria_id'], $_POST['area_id'], $_POST['elemento_id'],
                $qrData['code'], $qrData['path'], $_POST['fabricante'], $_POST['serial'], 
                $_POST['año_fabricacion'], $_POST['id_fuentepoder'], $_POST['modelo'], 
                $valor_residual, $grupo_id, $foto_path
            ];

            // error_log($_POST['estado_id']);
            // return;

            ejecutar_query($conn, $query, $params);

            $conn->commit();
            

            $qrGeneratedMessage = "<div class='col-12 col-md-4 alert alert-success d-flex flex-column align-items-start'>
                                    <div class='d-flex justify-content-between w-100'>
                                        <p>Item insertado correctamente.<br> QR generado:</p>
                                        <button type='button' class='btn-close ' data-bs-dismiss='alert' aria-label='Cerrar'></button>
                                    </div>    
                                    <div class='qr-container'>
                                        <div class='qr-item'>
                                            <img src='{$qrData['path']}' alt='Código QR' class='img-fluid' style='max-width: 100px;'>
                                            <p class='mt-1'>Código: {$qrData['code']}</p>
                                        </div>
                                    </div>
                                </div>";
        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message ="Hubo un error al insertar el item: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Definición de los campos
$campos = [
    'codigo' => ['label' => 'CÓDIGO DE LA HERRAMIENTA', 'type' => 'text'],
    'nombre' => ['label' => 'NOMBRE', 'type' => 'text'],
    'descripcion' => ['label' => 'DESCRIPCIÓN', 'type' => 'textarea'],
    'elemento_id' => ['label' => 'TIPO DE ELEMENTO', 'type' => 'select', 'options' => $resultado_elementos],
    'estado_id' => ['label' => 'ESTADO', 'type' => 'select', 'options' => $resultado_estados],
    'cantidad' => ['label' => 'CANTIDAD', 'type' => 'number'],
    'costo' => ['label' => 'COSTO', 'type' => 'number', 'required' => false, 'step' => '0.01'],
    'area_id' => ['label' => 'ÁREA DE DESTINO', 'type' => 'select', 'options' => $resultado_areas],
    'fecha' => ['label' => 'FECHA DE ADQUISICIÓN', 'type' => 'date'],
    'seccion_id' => ['label' => 'SECCIÓN', 'type' => 'select', 'options' => $resultados_secciones],
    'categoria_id' => ['label' => 'CATEGORÍA', 'type' => 'select', 'options' => $resultado_categorias],
    'vida' => ['label' => 'VIDA ÚTIL (meses)', 'type' => 'number'],
    'observaciones' => ['label' => 'OBSERVACIONES', 'type' => 'text'],
    'id_fuentepoder' => ['label' => 'TIPO DE FUENTE DE PODER', 'type' => 'select', 'options' => $resultados_fuente],
    'fabricante' => ['label' => 'FABRICANTE', 'type' => 'text',],
    'serial' => ['label' => 'SERIAL', 'type' => 'text'],
    'modelo' => ['label' => 'MODELO', 'type' => 'text'],
    'año_fabricacion' => ['label' => 'AÑO DE FABRICACIÓN', 'type' => 'number'],
];
// Definir longitudes máximas para los campos
$longitudesMaximas = [
    'codigo' => 45,
    'nombre' => 45,
    'descripcion' => 255,
    'vida' => 11,
    'observaciones' =>45,
    'fabricante' => 45,   
    'serial' => 45,
    'modelo' => 120,
    'año_fabricacion' => 4,
];
 $errores = []; // Arreglo para acumular errores

// Validar longitudes máximas
foreach ($longitudesMaximas as $campo => $longitudMaxima) {
    $valor = $_POST[$campo] ?? '';
    if (strlen($valor) > $longitudMaxima) {
        $mensaje = isset($campos[$campo]['label']) ? $campos[$campo]['label'] : $campo;
        $errores[$campo] = "El campo \"{$mensaje}\" excede el límite de {$longitudMaxima} caracteres.";
    }
}

if (!empty($errores)) {
    $mensajeError = '<div class="alert alert-danger"><ul>';
    foreach ($errores as $error) {
        $mensajeError .= "<li>{$error}</li>";
    }
    $mensajeError .= '</ul></div>';
}

// Función para renderizar los inputs de forma homogénea
function renderInput($name, $details) {
    $label = $details['label'];
    $type = $details['type'];
    // $required = isset($details['required']) && $details['required'] ? 'required' : '';
    $step = isset($details['step']) ? "step='{$details['step']}'" : ''; // Para manejar decimales

    $html = "<div class='row pb-2'>
                <div class='col-5 d-flex align-items-start detail-row'>
                    <label for='$name' class='form-label'>$label:</label>
                </div>
                <div class='col-7 d-flex align-items-start'>";

    if ($type == 'select') {
        $options = $details['options'] ?? [];
        $html .= "<select class='form-select' id='$name' name='$name' required>
                    <option value='0' selected>Elegir una opción</option>";
        foreach ($options as $option) {
            $keys = array_keys($option);
            $value = $option[$keys[0]];
            $text = $option[$keys[1]];
            $html .= "<option value='$value'>$text</option>";
        }
        $html .= "</select>";
    } elseif ($type == 'textarea') {
        $html .= "<textarea id='$name' class='form-control' maxlength='255' name='$name' required></textarea>";
    } else {
        $html .= "<input type='$type' min='1' $step class='form-control border-2 rounded-3' maxlength='45' id='$name' name='$name' required>";
    }

    $html .= "  </div>
            </div>";
    return $html;
}
?>
    <div class="container-fluid mt-3 mb-4 ">
        <h5 class="w-100 mb-2 bg-plomo p-2"><B>INSERTAR NUEVO ITEM</B></h5>
        <form method="POST" class="px-3" enctype="multipart/form-data" onsubmit="return validateForm()">
        <!-- Selección de OPCIÓN -->
            <div class="row py-1">
                <div class="col-12">
                    <div class="mb-3 pt-3">
                        <label for="tipo_item" class="form-label">Tipo de Item:</label>
                        <select class="form-select border" id="tipo_item" name="tipo_item" onchange="redirectToPage()">
                            <option value="default" selected>Elegir una opción</option>
                            <option value="herramientas">Herramientas</option>
                            <option value="materiales">Materiales</option>
                        </select>
                    </div>
                </div>
            </div>
                <?php if (!empty($error_message)): ?>
                    <div class="col-12 col-lg-6 alert alert-danger alert-dismissible fade show d-flex justify-content-between" role="alert">
                        <strong>Error:</strong> <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($qrGeneratedMessage)): ?>
                    <?php echo $qrGeneratedMessage; ?>
                <?php endif; ?>
                
            <!-- SECCIÓN de HERRAMIENTAS-->
            <section id="herramientasFields" class="d-none"  >
                <!-- Sección de Información -->
                <section class="row">
                    <div class="col-12">
                        <div class="w-100 bg-plomo mb-2 p-1"><b>INFORMACIÓN</b></div>
                        <div class="card rounded-4 px-1 mb-3">
                            <div class="row m-1">
                                <!-- Primera columna: CÓDIGO, NOMBRE, DESCRIPCIÓN -->
                                <div class="col-12 col-sm-6 d-flex flex-column position-relative pt-2">
                                    <?php
                                    echo renderInput('codigo', $campos['codigo']);
                                    echo renderInput('nombre', $campos['nombre']);
                                    echo renderInput('descripcion', $campos['descripcion']);
                                    echo renderInput('area_id', $campos['area_id']);
                                    ?>
                                </div>
                                <!-- Segunda columna: TIPO ELEMENTO, ESTADO, CANTIDAD y la imagen -->
                                <div class="col-12 col-sm-6 d-flex flex-column position-relative pt-2 pb-1">
                                    <?php
                                    echo renderInput('elemento_id', $campos['elemento_id']);
                                    echo renderInput('estado_id', $campos['estado_id']);
                                    echo renderInput('cantidad', $campos['cantidad']);
                                    ?>
                                    <div class="row py-2 d-flex ">
                                        <!-- Campo de selección de imagen -->
                                        <div class="d-flex justify-content-between align-items-center col-12 ">
                                            <label for="foto_path" class="col-5 form-label detail-row">IMAGEN DE LA HERRAMIENTA:</label>
                                            <input type="file" class="form-control border-2 rounded-3 ms-1" id="foto_path" name="foto_path" accept="image/*">
                                        </div>
                                        <!-- Imagen de previsualización -->
                                        <div class="col-5  align-items-center">
                                            <img id="preview" class="img-thumbnail d-none mt-3 rounded"  alt="Vista previa">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <!-- Sección de Detalles -->
                <section class="row">
                    <div class="col-12">
                        <div class="w-100 bg-plomo mb-2 p-1"><b>DETALLES</b></div>
                        <div class="card rounded-4 px-1 mb-3">
                            <!--2 columnas en pantallas pequeñas y 4 en medianas y superiores -->
                            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 m-1">
                                <!-- Columna 1 -->
                                <div class="col detail-row separation-edge pt-2">
                                    <?php
                                    echo renderInput('costo', $campos['costo']);
                                    echo renderInput('fecha', $campos['fecha']);
                                    echo renderInput('vida', $campos['vida']);
                                    ?>
                                </div>
                            <!-- Columna 2 -->
                                <div class="col detail-row separation-edge pt-2">
                                    <?php
                                    echo renderInput('seccion_id', $campos['seccion_id']);
                                    echo renderInput('categoria_id', $campos['categoria_id']);
                                    echo renderInput('observaciones', $campos['observaciones']);
                                    ?>
                                </div>
                                <!-- Columna 3 -->
                                <div class="col detail-row separation-edge pt-2">
                                    <?php
                                    echo renderInput('fabricante', $campos['fabricante']);
                                    echo renderInput('serial', $campos['serial']);
                                    echo renderInput('modelo', $campos['modelo']);
                                    ?>
                                </div>
                                <!-- Columna 4 -->
                                <div class="col detail-row pt-2">
                                    <?php
                                    echo renderInput('año_fabricacion', $campos['año_fabricacion']);
                                    echo renderInput('id_fuentepoder', $campos['id_fuentepoder']);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- Mantenimiento Sección -->
                    <div class="w-100 bg-plomo mb-2 p-1"><b>MANTENIMIENTO</b></div>
                    <section class="row d-flex flex-row">
                        <!-- Primeta tarjeta MANTENIMIENTO -->
                            <div class="col-12 col-sm-6 mb-2 d-flex align-items-stretch ">
                                <div class="card rounded-4 px-3 py-2 flex-fill"> 
                                    <div class="row p-1" id="mantenimientoContainer">
                                        <div class="col-12">
                                            <label for="mantenimiento" class="form-label ">¿Necesita mantenimiento?:</label>
                                            <select class="form-select" id="mantenimiento" name="mantenimiento[]" onchange="toggleFields('mantenimiento', 'mantenimientoFields', 'addMantenimiento', ['man_tipo', 'man_codigo'])">
                                                <option value='0' selected>Elegir una opción</option>
                                                <option value="No">No</option>
                                                <option value="Si">Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="mantenimientoFieldsWrapper">
                                        <div id="mantenimientoFields" class="mantenimiento-fields d-none" >
                                            <h5 class="mt-3 mb-2 px-2">Mantenimiento número 1</h5>
                                            <div class="row p-1">
                                                <div class="col-12 col-sm-6">
                                                    <label for="man_tipo" class="form-label">Tipo mantenimiento:</label>
                                                    <select class="form-select" aria-label="Tipo de mantenimiento" id="man_tipo" name="man_tipo[]">
                                                        <option value="0" selected>Elegir una opción</option>
                                                        <?php foreach ($resultado_man_tipo as $fila): ?>
                                                            <option value="<?php echo $fila['idman_tipo']; ?>">
                                                                <?php echo $fila['descripcion']; ?>
                                                            </option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-sm-6">
                                                    <label for="man_codigo" class="form-label">Frecuencia:</label>
                                                    <select class="form-select" aria-label="Frecuencia de mantenimiento" id="man_codigo" name="man_codigo[]">
                                                        <option value="0" selected>Elegir una opción</option>
                                                        <?php foreach ($resultado_man_codigo as $fila): ?>
                                                            <option value="<?php echo $fila['idman_codigo']; ?>">
                                                                <?php echo $fila['descripcion']; ?>
                                                            </option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row p-1">
                                                <div class="col-12">
                                                    <label for="notas_mantenimiento" class="form-label">Notas del mantenimiento:</label>
                                                    <textarea id="notas_mantenimiento" class="form-control border-2 rounded-3" name="notas_mantenimiento[]" maxlength="255"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <!-- Botón para agregar más mantenimiento -->
                                    <button type="button" id="addMantenimiento" class="btn btn-secondary d-none my-2">Agregar más mantenimiento</button>
                                </div>            
                            </div>
                            <!--Segunda tarjeta MANUALES -->
                            <div class="col-12 col-sm-6 mb-2 d-flex align-items-stretch">
                                <div class="card rounded-4 px-3 py-2 flex-fill"> 
                                    <div class="row py-1" id="manualContainer">
                                        <div class="col-12">
                                            <label for="manual" class="form-label" aria-label="Tiene Manual?">¿Tiene manual de funcionamiento?:</label>
                                            <select class="form-select" id="manual" name="manual[]" >
                                                <option value='0' selected>Elegir una opción</option>
                                                <option value="No">No</option>
                                                <option value="Si">Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="manualFieldsWrapper">
                                        <div id="manualFields" class="manual-fields d-none" >
                                            <h5 class="mt-3 mb-2 px-2">Manual número 1</h5>
                                            <div class="row py-1">
                                                    <div class="col-12 col-sm-6">
                                                        <label for="id_manuales_tipo" class="form-label" aria-label="Tipo de manual">Tipo de Manual:</label>
                                                        <select id="id_manuales_tipo" class="form-select border-2 rounded-3" name="id_manuales_tipo[]">
                                                            <option value="0" selected>Elegir una opción</option>
                                                            <?php foreach ($resultado_manuales_tipo as $fila): ?>
                                                                <option value="<?php echo $fila['id_manuales_tipo']; ?>">
                                                                    <?php echo $fila['manuales_tipo_descripcion']; ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-sm-6">
                                                        <label for="manual_file" class="form-label">Subir archivo del manual:</label>
                                                        <input type="file" class="form-control border-2 rounded-3" id="manual_file" name="manual_file[]" accept=".pdf,.doc,.docx">
                                                    </div>
                                            </div>
                                        </div>
                                    </div>
                                <!-- Botón para agregar más manual -->
                                    <button type="button" id="addManual" class="btn btn-secondary d-none mt-2">Agregar más manual</button>
                                </div>
                            </div>
                    </section>
            </section>
            <div id="alertaError" class="alert alert-danger d-none" role="alert">
            </div>
            <button class="my-3 btn btn-primary" type="submit" name="submit_item">Insertar Item</button>
        </form>
    </div>
    <?php require './../../layout/footer.htm'; ?>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- SCRIPTS PARA HERRMAMIENTAS -->
    <script>

document.addEventListener("DOMContentLoaded", function() {
    console.log("JavaScript cargado correctamente"); // Verifica que el script se está ejecutando

    // ----------------- VALIDACIÓN DEL FORMULARIO -----------------
    function validateForm() {
        let campos = [
            "codigo", "nombre", "descripcion", "elemento_id", "estado_id", "cantidad",
            "costo", "area_id", "fecha", "seccion_id", "categoria_id", "vida",
            "observaciones", "id_fuentepoder", "fabricante", "serial", "modelo", "año_fabricacion", "manual", "mantenimiento"
        ];
        // Diccionario con nombres legibles para el user
        let nombresCampos = {
            "elemento_id": "Tipo de Elemento",
            "estado_id": "Estado",
            "area_id": "Área",
            "seccion_id": "Sección",
            "categoria_id": "Categoría",
            "id_fuentepoder": "Fuente de Poder",
            "manual": "Manual",
            "mantenimiento": "Mantenimiento"
        };

        let alerta = document.getElementById("alertaError");
        let errores = [];

        campos.forEach(function(campo) {
            let elemento = document.getElementById(campo);
            if (!elemento) return;

            let valor = elemento.value.trim();
            if (valor === "" || valor === "0") {
                errores.push(`El campo <b>${nombresCampos[campo]}</b> es obligatorio.`);
                elemento.classList.add("is-invalid");
            } else {
                elemento.classList.remove("is-invalid");
            }
        });

        if (errores.length > 0) {
            alerta.innerHTML = `<b>⚠️ Error.</b> Existen campos vacíos:<br><ul>${errores.map(err => `<li>${err}</li>`).join("")}</ul>`;
            alerta.classList.remove("d-none");
            return false;
        }

        alerta.classList.add("d-none");
        return true;
    }

    document.querySelector('form').addEventListener('submit', function(event) {
        if (!validateForm()) {
            event.preventDefault();
        }
    });

    // ----------------- VISTA PREVIA DE IMAGEN -----------------
    function previewImage(event) {
        var input = event.target;
        var reader = new FileReader();

        reader.onload = function() {
            var preview = document.getElementById("preview");
            preview.src = reader.result;
            preview.classList.remove("d-none");
        };

        if (input.files && input.files[0]) {
            reader.readAsDataURL(input.files[0]);
        }
    }

    document.getElementById("foto_path")?.addEventListener("change", previewImage);
    // ----------------- AGREGAR CAMPOS Mantenimiento/Manuales ----------------

    function addMantenimiento() {
        var mantenimientoFieldsWrapper = document.getElementById("mantenimientoFieldsWrapper");
        var mantenimientoFields = document.querySelector(".mantenimiento-fields");

        if (!mantenimientoFields || !mantenimientoFieldsWrapper) return; // Verifica que los elementos existen

        var newMantenimientoFields = mantenimientoFields.cloneNode(true);
        var mantenimientoCount = mantenimientoFieldsWrapper.querySelectorAll(".mantenimiento-fields").length + 1;

        var titleElement = newMantenimientoFields.querySelector("h5");
        titleElement.textContent = "Mantenimiento número " + mantenimientoCount;

        var inputs = newMantenimientoFields.querySelectorAll("select, textarea");
        inputs.forEach(function(input) {
            input.selectedIndex = 0;
            if (input.tagName === "TEXTAREA") {
                input.value = "";
            }
        });

        newMantenimientoFields.classList.remove("d-none");
        mantenimientoFieldsWrapper.appendChild(newMantenimientoFields);
    }

    function addManual() {
        var manualFieldsWrapper = document.getElementById("manualFieldsWrapper");
        var manualFields = document.querySelector(".manual-fields");

        if (!manualFields || !manualFieldsWrapper) return; // Verifica que los elementos existen

        var newManualFields = manualFields.cloneNode(true);
        var manualCount = manualFieldsWrapper.querySelectorAll(".manual-fields").length + 1;

        var oldTitleElement = newManualFields.querySelector("h5");
        if (oldTitleElement) oldTitleElement.remove();

        var titleElement = document.createElement("h5");
        titleElement.textContent = "Manual número " + manualCount;
        titleElement.classList.add("mt-3", "mb-2");

        var inputs = newManualFields.querySelectorAll("input");
        inputs.forEach(function(input) {
            input.value = "";
        });

        newManualFields.classList.remove("d-none");
        newManualFields.insertBefore(titleElement, newManualFields.firstChild);
        manualFieldsWrapper.appendChild(newManualFields);
    }

    document.getElementById("addMantenimiento")?.addEventListener("click", addMantenimiento);
    document.getElementById("addManual")?.addEventListener("click", addManual);

    // ----------------- MOSTRAR/OCULTAR CAMPOS -----------------
    function toggleFields(idSelect, idFields, idBoton, requiredFields = []) {
    let selectElement = document.getElementById(idSelect);
    let fields = document.getElementById(idFields);
    let boton = document.getElementById(idBoton);

    if (!selectElement || !fields || !boton) return; // Evita errores si los elementos no existen

    let valor = selectElement.value;

    // Mostrar u ocultar los campos según la opción elegida
    fields.classList.toggle("d-none", valor !== "Si");
    boton.classList.toggle("d-none", valor !== "Si");

    // Validación: Si la opción es "Sí", los campos internos deben ser obligatorios
    requiredFields.forEach(id => {
        let field = document.getElementById(id);
        if (field) {
            field.required = valor === "Si"; // Agrega o quita la obligatoriedad
        }
    });
    }

    // Aplicar la función a mantenimiento y manuales con validación
    document.getElementById("mantenimiento")?.addEventListener("change", function() {
        toggleFields("mantenimiento", "mantenimientoFields", "addMantenimiento", ["man_tipo", "man_codigo"]);
    });

    document.getElementById("manual")?.addEventListener("change", function() {
        toggleFields("manual", "manualFields", "addManual", ["id_manuales_tipo", "manual_file"]);
    });
    // ----------------- REDIRECCIÓN -----------------
    var select = document.getElementById("tipo_item");
    var herramientasFields = document.getElementById("herramientasFields");
    var params = new URLSearchParams(window.location.search);
    var selectedValue = params.get("tipo_item");

    if (selectedValue === "herramientas") {
        herramientasFields?.classList.remove("d-none");
    }

    if (selectedValue) {
        select.value = selectedValue;
    }

    function redirectToPage() {
        var value = select.value;
        if (value === "herramientas") {
            window.location.href = "./herramientas.php?tipo_item=herramientas";
        } else if (value === "materiales") {
            window.location.href = "./materiales.php?tipo_item=materiales";
        }
    }

    select?.addEventListener("change", redirectToPage);
});

    </script>
    
</body>

</html>