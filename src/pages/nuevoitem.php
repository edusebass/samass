<?php
require './../layout/head.html';
require './../layout/header.php'; 
require './../db/dbconn.php';
require './../utils/session_check.php';
require_once './../../library/phpqrcode/qrlib.php';
$titlepage = 'nuevoitem';    

$docs = array_diff(scandir('./../../docs/manuales'), array('.', '..'));
function ejecutar_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

function obtener_secciones($conn) {
    $query = "SELECT idsecciones, seccion FROM secciones";
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
    $query = "SELECT idcategorias, categorias FROM categorias";
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
    // $codigo_asamblea = '003';
    // $qrCode = $codigo_asamblea . '-00' . $areaId . '-00' . $elementoId . '-' . sprintf('%03d', $unidadNumero);
    $qrImagePath = './../../docs/qr_codes/item_' . $codigo . '.png';
    
    if (!file_exists('qr_codes')) {
        mkdir('qr_codes', 0777, true);
    }
    
    QRcode::png($codigo, $qrImagePath, QR_ECLEVEL_L, 10);
    
    return ['code' => $codigo, 'path' => $qrImagePath];
}

$qrGeneratedMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_item'])) {
    try {
        $conn->beginTransaction();
        $tipo_item = $_POST['tipo_item']; // Obtener el valor del tipo de item
        if ($tipo_item === 'herramientas') {
            // $cantidad = intval($_POST['cantidad']);
            $grupo_id =  rand(1, 10000);
            
            $qrGeneratedMessage = "<div class='alert alert-success'>
                <p>Item insertado correctamente. QRs generados:</p>
                <div class='qr-container'>";
            
            $stmt = $conn->query("SELECT MAX(iditems) AS max_id FROM items");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastId = $row['max_id'];

            // Logica para insertar en la tabla manuales
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
                    if (!empty($man_tipos[$j]) && $man_tipos[$j] !== 'default') {
                        $query_mantenimiento = "INSERT INTO mantenimiento (id_tipo, id_codigo_man, notas, grupo_id, fecha_creacion) VALUES (?, ?, ?, ?, NOW())";
                        $params_mantenimiento = [
                            $man_tipos[$j], 
                            $man_codigos[$j], 
                            $notas_mantenimientos[$j],
                            $grupo_id
                        ];
                        ejecutar_query($conn, $query_mantenimiento, $params_mantenimiento);
                    }
                }
            }

            // Generación de QR
            $qrData = generateQR(
                $_POST['codigo']
            );

            // Inserción en la tabla items
            $query = "INSERT INTO items (nombre, descripcion, estado_id, cantidad, costo, fecha, vida, observaciones, seccion_id, categoria_id, area_id, elemento_id, codigo, qr_image_path, fabricante, serial, año_fabricacion, id_fuentepoder, valor_residual, modelo, grupo_id) 
            VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ROUND(? * 0.20, 2), ?, ?)";
            $vida_util_en_horas = $_POST['vida'] * 720;
            $costo_mantenimiento = 0; //valor por defecto =D
            $params = [
                $_POST['nombre'], $_POST['descripcion'], $_POST['estado_id'],
                $_POST['cantidad'], $_POST['minimal'], $_POST['costo'], $_POST['fecha'],
                $vida_util_en_horas, 
                $_POST['observaciones'], $_POST['seccion_id'], 
                $_POST['categoria_id'], $_POST['area_id'], $_POST['elemento_id'],
                $qrData['code'], $qrData['path'], $_POST['fabricante'], $_POST['serial'], $_POST['año_fabricacion'], $_POST['fuentepoder_id'], $_POST['costo'], $_POST['modelo'], 
                $grupo_id
            ];
        
            ejecutar_query($conn, $query, $params);
            $itemId = $conn->lastInsertId();
        
            $qrGeneratedMessage .= "
                <div class='qr-item'>
                    <img src='{$qrData['path']}' alt='QR Code'>
                    <p>Código: {$qrData['code']}</p>
                </div>";
        } elseif ($tipo_item === 'materiales') {
                // Validar que los campos necesarios estén presentes
                if (empty($_POST['mat_descripcion']) || 
                !isset($_POST['mat_cantidad']) || 
                !isset($_POST['mat_minimo1']) || 
                !isset($_POST['mat_minimo2']) || 
                $_POST['id_mat_medida'] === 'default') {
                throw new Exception("Por favor, complete todos los campos requeridos");
            }

            // Manejar la subida de la foto si se proporciona
            $foto_path = null;
            if (isset($_FILES['mat_foto']) && $_FILES['mat_foto']['error'] == UPLOAD_ERR_OK) {
                $foto_dir = './../../docs/materiales_fotos/';
                if (!file_exists($foto_dir)) {
                    mkdir($foto_dir, 0777, true);
                }
                $foto_path = $foto_dir . basename($_FILES['mat_foto']['name']);
                if (!move_uploaded_file($_FILES['mat_foto']['tmp_name'], $foto_path)) {
                    throw new Exception("Error al subir la foto del material");
                }
            }

            // Concatenar mat_descripcion con UUID
            

            // Obtener el mayor id_materiales
            // $query_max_id = "SELECT MAX(CAST(SUBSTRING(id_materiales, -3) AS UNSIGNED)) AS max_id FROM materiales";
            // $max_id = ejecutar_query($conn, $query_max_id)->fetchColumn();

            // // Generar el nuevo id_materiales incrementando los tres últimos números
            // if ($max_id) {
            //     $new_id_number = $max_id + 1;
            //     $id_materiales = $id_pre . str_pad($new_id_number, 3, '0', STR_PAD_LEFT);
            // } else {
            //     // Si no hay registros en la tabla, empieza con el primer valor
            //     $id_materiales = $id_pre . '001';
            // }
            $id_materiales = 'M-' .  $_POST['id_materiales'];
            // Generar QR para materiales usando id_materiales como código
            $qrCode = $id_materiales;
            $qrImagePath = './../../docs/qr_codes/' . $id_materiales . '.png';

            if (!file_exists('./../../docs/qr_codes')) {
                mkdir('./../../docs/qr_codes', 0777, true);
            }

            QRcode::png($qrCode, $qrImagePath, QR_ECLEVEL_L, 10);

            // Preparar la consulta para materiales
            $query = "INSERT INTO materiales (id_materiales, mat_nombre, mat_descripcion, mat_cantidad, mat_minimo1, mat_minimo2, id_mat_medida, codigo, qr_image_path, id_estado, id_mat_area, mat_foto) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $id_materiales, $_POST['mat_nombre'], $_POST['mat_descripcion'], $_POST['mat_cantidad'], $_POST['mat_minimo1'], $_POST['mat_minimo2'], $_POST['id_mat_medida'], $qrCode, $qrImagePath, $_POST['id_estado'], $_POST['id_mat_area'], $foto_path
            ];

            ejecutar_query($conn, $query, $params);

            $qrGeneratedMessage .= "
                <div class='qr-item'>
                    <img src='{$qrImagePath}' alt='QR Code'>
                    <p>Código: {$qrCode}</p>
                </div>";

            echo "<div class='alert alert-success'>Material insertado correctamente</div>";
        }

        $qrGeneratedMessage .= "</div></div>";
        
        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $qrGeneratedMessage = "<div class='alert alert-danger'>Error al insertar item: " . $e->getMessage() . "</div>";
    }
}



//LOGICA SOLO PARA MATERIALES *****************************************************************
// Function to get mat_medida options
function obtener_mat_medida($conn) {
    $query = "SELECT id_mat_medida, mat_med_descripcion FROM mat_medida";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_mat_medida = obtener_mat_medida($conn);

function obtener_mat_areas($conn) {
    $query = "SELECT id_mat_area, mat_area_descripcion FROM mat_area";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_mat_areas = obtener_mat_areas($conn);



?>
<title>SAM Assistant</title>
</head>
<body>
<div class="container-fluid mt-3 mb-4 ">
    <h5 class="w-100 mb-2 bg-plomo p-2"><B>INSERTAR NUEVO ITEM</B></h5>
    <div class="card rounded-4 px-3 mb-3">
        <form method="POST" class="px-3" enctype="multipart/form-data">
            <div class="row py-1">
                <div class="col-12">
                    <div class="mb-3 pt-3">
                        <label for="tipo_item" class="form-label">Tipo de Item:</label>
                        <select class="form-select" id="tipo_item" name="tipo_item" onchange="toggleItemFields()">
                            <option value="default" selected>Elegir una opción</option>
                            <option value="herramientas">Herramientas</option>
                            <option value="materiales">Materiales</option>
                        </select>
                    </div>
                </div>
            </div>

                <!-- Materiales Section ********************************-->
           <!-- Sección de Materiales -->
            <div id="materialesFields" style="display:none;">
                <div class="row py-1">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="id_materiales" class="form-label">Id del material:</label>
                            <input type="text" placeholder="003-004-004" class="form-control border-2 rounded-3" id="id_materiales" name="id_materiales" required>
                        </div>
                    </div>
                </div>
                <div class="row py-1">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="mat_descripcion" class="form-label">Nombre del material:</label>
                            <input type="text" class="form-control border-2 rounded-3" id="mat_descripcion" name="mat_nombre" required>
                        </div>
                    </div>
                </div>
                <div class="row py-1">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="mat_descripcion" class="form-label">Descripción del Material:</label>
                            <input type="text" class="form-control border-2 rounded-3" id="mat_descripcion" name="mat_descripcion" required>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="id_estado" class="form-label">Estado:</label>
                        <select class="form-select" aria-label="Default select example" id="id_estado" name="id_estado">
                            <option value="default" selected>Elegir una opcion</option>
                        <?php foreach ($resultado_estados as $fila): ?>
                            <option value="<?php echo $fila['idestado']; ?>">
                                <?php echo $fila['descripcion']; ?>
                            </option>
                        <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="id_mat_area" class="form-label">Área donde se utilizara:</label>
                        <select class="form-select" aria-label="Default select example" id="id_mat_area" name="id_mat_area">
                            <option value="default" selected>Elegir una opcion</option>
                        <?php foreach ($resultado_mat_areas as $fila): ?>
                            <option value="<?php echo $fila['id_mat_area']; ?>">
                                <?php echo $fila['mat_area_descripcion']; ?>
                            </option>
                        <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="row py-1">
                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label for="mat_cantidad" class="form-label">Cantidad:</label>
                            <input type="number" class="form-control border-2 rounded-3" id="mat_cantidad" name="mat_cantidad" required>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label for="mat_minimo1" class="form-label">Mínimo 1:</label>
                            <input type="number" class="form-control border-2 rounded-3" id="mat_minimo1" name="mat_minimo1" required>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label for="mat_minimo2" class="form-label">Mínimo 2:</label>
                            <input type="number" class="form-control border-2 rounded-3" id="mat_minimo2" name="mat_minimo2" required>
                        </div>
                    </div>
                </div>
                <div class="row py-1">
                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label for="id_mat_medida" class="form-label">Unidad de Medida:</label>
                            <select class="form-select" id="id_mat_medida" name="id_mat_medida" required>
                                <option value="default" selected>Elegir una opción</option>
                                <?php foreach ($resultado_mat_medida as $fila): ?>
                                    <option value="<?php echo $fila['id_mat_medida']; ?>">
                                        <?php echo $fila['mat_med_descripcion']; ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row py-1">
                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label for="mat_foto" class="form-label">Foto del Material:</label>
                            <input type="file" class="form-control border-2 rounded-3" id="mat_foto" name="mat_foto" accept="image/*" >
                        </div>
                    </div>
                </div>
                <p>------------</p>

                <button class="mb-3 btn btn-primary" type="submit" name="submit_item">Insertar Item</button>
            </div>
            <!-- HERAMIENTAS SECTION FORMULARIO -->
            <section id="herramientasFields" style="display:none;">
                <div class="row py-1">
                    <div class="col-12">
                        <div class="mb-3 pt-3">
                            <label for="codigo" class="form-label">Codigo:</label>
                            <input type="text" class="form-control border-2 rounded-3" id="codigo" name="codigo" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3 pt-3">
                            <label for="nombre" class="form-label">Nombre:</label>
                            <input type="text" class="form-control border-2 rounded-3" id="nombre" name="nombre" required>
                        </div>
                    </div>
                    </div>
                    <div class="row py-1">
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción:</label>
                            <textarea id="descripcion" class="form-control border-2 rounded-3" name="descripcion"></textarea>
                        </div>
                    </div>
                </div>
                <div class="row py-1">
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="estado_id" class="form-label">Estado:</label>
                        <select class="form-select" aria-label="Default select example" id="estado_id" name="estado_id">
                            <option value="default" selected>Elegir una opcion</option>
                        <?php foreach ($resultado_estados as $fila): ?>
                            <option value="<?php echo $fila['idestado']; ?>">
                                <?php echo $fila['descripcion']; ?>
                            </option>
                        <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <!-- <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad:</label>
                        <input type="number" class="form-control border-2 rounded-3" id="cantidad" name="cantidad">
                    </div>
                </div> -->
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="costo" class="form-label">Costo:</label>
                        <input type="number" class="form-control border-2 rounded-3" id="costo" name="costo"> 
                    </div>
                </div>
                </div>
                <div class="row py-1">
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="elemento_id" class="form-label">Tipo de elementos:</label>
                        <select class="form-select" aria-label="Default select example" id="elemento_id" name="elemento_id">
                            <option value="default" selected>Elegir una opcion</option>
                        <?php foreach ($resultado_elementos as $fila): ?>
                            <option value="<?php echo $fila['idelementos']; ?>">
                                <?php echo $fila['tipo']; ?>
                            </option>
                        <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="area_id" class="form-label">Lugar donde se almacena:</label>
                        <select class="form-select" aria-label="Default select example" id="area_id" name="area_id">
                            <option value="default" selected>Elegir una opcion</option>
                        <?php foreach ($resultado_areas as $fila): ?>
                            <option value="<?php echo $fila['idareas']; ?>">
                                <?php echo $fila['descripcion']; ?>
                            </option>
                        <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="fecha" class="form-label">Fecha:</label>
                        <input type="date" class="form-control border-2 rounded-3" id="fecha" name="fecha">
                    </div>
                </div>
                </div>
                <div class="row py-1">

                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="seccion_id" class="form-label">Sección:</label>
                        <select class="form-select" aria-label="Default select example" id="seccion_id" name="seccion_id">
                            <option value="default" selected>Elegir una opcion</option>
                        <?php foreach ($resultados_secciones as $fila): ?>
                            <option value="<?php echo $fila['idsecciones']; ?>">
                                <?php echo $fila['seccion']; ?>
                            </option>
                        <?php endforeach ?>
                        </select>
                    </div>
            </div>
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="categoria_id" class="form-label">Categoria:</label>
                        <select class="form-select" aria-label="Default select example" id="categoria_id" name="categoria_id">
                            <option value="default" selected>Elegir una opcion</option>
                        <?php foreach ($resultado_categorias as $fila): ?>
                            <option value="<?php echo $fila['idcategorias']; ?>">
                                <?php echo $fila['categorias']; ?>
                            </option>
                        <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="vida" class="form-label">Vida Util:</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" min="0" class="form-control border-2 rounded-3" id="vida" name="vida">
                            <span>mes(es)</span>
                        </div>
                    </div>
                </div>
                </div>
                <div class="row py-1">
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones:</label>
                        <input type="text" class="form-control border-2 rounded-3" id="observaciones" name="observaciones">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="fuentepoder_id" class="form-label">Fuente de Poder:</label>
                        <select class="form-select" aria-label="Default select example" id="fuentepoder_id" name="fuentepoder_id">
                            <option value="default" selected>Elegir una opcion</option>
                        <?php foreach ($resultados_fuente as $fila): ?>
                            <option value="<?php echo $fila['idfuentepoder']; ?>">
                                <?php echo $fila['descripcion']; ?>
                            </option>
                        <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="mb-3">
                        <label for="fabricante" class="form-label">Fabricante:</label>
                        <input type="text" class="form-control border-2 rounded-3" id="fabricante" name="fabricante">
                    </div>
                </div>
                </div>
                    <div class="row py-1">
                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label for="modelo" class="form-label">Modelo:</label>
                            <input type="text" class="form-control border-2 rounded-3" id="modelo" name="modelo">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label for="serial" class="form-label">Serial:</label>
                            <input type="text" class="form-control border-2 rounded-3" id="serial" name="serial">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="mb-3">
                            <label for="año_fabricacion" class="form-label">Año de fabricación:</label>
                            <input type="number" class="form-control border-2 rounded-3" id="año_fabricacion" name="año_fabricacion" required>
                        </div>
                    </div>
                </div>

                <!-- Mantenimiento Section -->
                <section class="border mb-4 rounded-3 p-2">
                    <div class="row p-1" id="mantenimientoContainer">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="mantenimiento" class="form-label ">¿Necesita Mantenimiento?:</label>
                                <select class="form-select" id="mantenimiento" name="mantenimiento[]" onchange="toggleMantenimientoFields()">
                                    <option value="No" selected>No</option>
                                    <option value="Si">Sí</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="mantenimientoFieldsWrapper">
                        <div id="mantenimientoFields" style="display:none;" class="mantenimiento-fields">
                            <h5 class="mt-3 mb-2 px-2">Mantenimiento número 1</h5>
                            <div class="row p-1">
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="man_tipo" class="form-label">Tipo mantenimiento:</label>
                                        <select class="form-select" aria-label="Default select example" id="man_tipo" name="man_tipo[]">
                                            <option value="default" selected>Elegir una opción</option>
                                            <?php foreach ($resultado_man_tipo as $fila): ?>
                                                <option value="<?php echo $fila['idman_tipo']; ?>">
                                                    <?php echo $fila['descripcion']; ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="man_codigo" class="form-label">Tiempo de mantenimiento:</label>
                                        <select class="form-select" aria-label="Default select example" id="man_codigo" name="man_codigo[]">
                                            <option value="default" selected>Elegir una opción</option>
                                            <?php foreach ($resultado_man_codigo as $fila): ?>
                                                <option value="<?php echo $fila['idman_codigo']; ?>">
                                                    <?php echo $fila['descripcion']; ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="notas_mantenimiento" class="form-label">Notas del mantenimiento:</label>
                                        <textarea id="notas_mantenimiento" class="form-control border-2 rounded-3" name="notas_mantenimiento[]"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botón para agregar más mantenimiento -->
                    <button type="button" id="addMantenimiento" style="display:none;" class="btn btn-secondary">Agregar más mantenimiento</button>
                </section>

               
                <!-- Manuales Section -->
                <section class="border mb-4 rounded-3 p-2">
                    <div class="row py-1" id="manualContainer">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="manual" class="form-label">¿Tiene Manual de funcionamiento?:</label>
                                <select class="form-select" id="manual" name="manual[]" onchange="toggleManualFields()">
                                    <option value="No" selected>No</option>
                                    <option value="Si">Sí</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="manualFieldsWrapper">
                        <div id="manualFields" style="display:none;" class="manual-fields">
                        <h5 class="mt-3 mb-2 px-2">Manual número 1</h5>
                            <div class="row py-1">
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="id_manuales_tipo" class="form-label">Tipo de Manual:</label>
                                        <select id="id_manuales_tipo" class="form-select border-2 rounded-3" name="id_manuales_tipo[]">
                                            <option value="default" selected>Elegir una opción</option>
                                            <?php foreach ($resultado_manuales_tipo as $fila): ?>
                                                <option value="<?php echo $fila['id_manuales_tipo']; ?>">
                                                    <?php echo $fila['manuales_tipo_descripcion']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <label for="manual_file" class="form-label">Subir archivo del manual:</label>
                                        <input type="file" class="form-control border-2 rounded-3" id="manual_file" name="manual_file[]" accept=".pdf,.doc,.docx">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="addManual" style="display:none;" class="btn btn-secondary">Agregar más manual</button>
                </section>


            

            <button class="mb-3 btn btn-primary" type="submit" name="submit_item">Insertar Item</button>
        </form>
        <?php echo $qrGeneratedMessage; ?>
    </div>
</div>

<?php require './../layout/footer.htm'; ?>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
<!-- SCRIPTS PARA MATERIALES -->
<script>
    function toggleItemFields() {
        const tipoItem = document.getElementById('tipo_item').value;
        const herramientasFields = document.getElementById('herramientasFields');
        const materialesFields = document.getElementById('materialesFields');
        
        // Mostrar/ocultar secciones
        herramientasFields.style.display = tipoItem === 'herramientas' ? 'block' : 'none';
        materialesFields.style.display = tipoItem === 'materiales' ? 'block' : 'none';
        
        // Obtener todos los campos required de herramientas
        const herramientasInputs = herramientasFields.querySelectorAll('[required]');
        // Obtener todos los campos required de materiales
        const materialesInputs = materialesFields.querySelectorAll('[required]');
        
        // Manejar required para herramientas
        herramientasInputs.forEach(input => {
            input.required = tipoItem === 'herramientas';
        });
        
        // Manejar required para materiales
        materialesInputs.forEach(input => {
            input.required = tipoItem === 'materiales';
        });
    }
    // Ejecutar la función al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        toggleItemFields();
    });
</script>
<!-- SCRIPTS PARA HERRMAMIENTAS -->
<script>
    // Función para agregar más campos de mantenimiento
    document.getElementById('addMantenimiento').addEventListener('click', function() {
    var mantenimientoFieldsWrapper = document.getElementById('mantenimientoFieldsWrapper');
    var mantenimientoFields = document.querySelector('.mantenimiento-fields');
    var newMantenimientoFields = mantenimientoFields.cloneNode(true);
    
    // Calcular el número de mantenimiento
    var mantenimientoCount = mantenimientoFieldsWrapper.querySelectorAll('.mantenimiento-fields').length + 1;
    
    // Añadir título numerado
    var titleElement = newMantenimientoFields.querySelector('h5');
    titleElement.textContent = 'Mantenimiento número ' + mantenimientoCount;
    
    // Limpiar los valores de los inputs
    var inputs = newMantenimientoFields.querySelectorAll('select, textarea');
    inputs.forEach(function(input) {
        input.selectedIndex = 0; // Resetear selecciones
        if (input.tagName === 'TEXTAREA') {
            input.value = ''; // Resetear textarea
        }
    });
    
    // Mostrar los nuevos campos
    newMantenimientoFields.style.display = 'block';
    
    // Añadir los nuevos campos al final del contenedor
    mantenimientoFieldsWrapper.appendChild(newMantenimientoFields);
});

    // Función para agregar más campos de manuales
    // Función para agregar más campos de manuales
document.getElementById('addManual').addEventListener('click', function() {
    var manualFieldsWrapper = document.getElementById('manualFieldsWrapper');
    var manualFields = document.querySelector('.manual-fields');
    var newManualFields = manualFields.cloneNode(true);
    
    // Calcular el número de manual
    var manualCount = manualFieldsWrapper.querySelectorAll('.manual-fields').length + 1;
    
    // Eliminar cualquier título antiguo
    var oldTitleElement = newManualFields.querySelector('h5');
    if (oldTitleElement) {
        oldTitleElement.remove();
    }
    
    // Añadir título numerado
    var titleElement = document.createElement('h5');
    titleElement.textContent = 'Manual número ' + manualCount;
    titleElement.classList.add('mt-3', 'mb-2');
    
    // Limpiar los valores de los inputs
    var inputs = newManualFields.querySelectorAll('input');
    inputs.forEach(function(input) {
        input.value = ''; // Resetear valores de los inputs
    });
    
    // Mostrar los nuevos campos
    newManualFields.style.display = 'block';
    
    // Añadir el título antes de los campos
    newManualFields.insertBefore(titleElement, newManualFields.firstChild);
    
    // Añadir los nuevos campos al final del contenedor
    manualFieldsWrapper.appendChild(newManualFields);
});

</script>
<script>
function toggleMantenimientoFields() {
    const mantenimiento = document.getElementById('mantenimiento').value;
    const fields = document.getElementById('mantenimientoFields');
    fields.style.display = mantenimiento === 'Si' ? 'block' : 'none';
    const boton_añadir_mantenimiento = document.getElementById('addMantenimiento');
    boton_añadir_mantenimiento.style.display = mantenimiento === 'Si' ? 'block' : 'none';
}
function toggleManualFields() {
    const manual = document.getElementById('manual').value;
    const fields = document.getElementById('manualFields');
    fields.style.display = manual === 'Si' ? 'block' : 'none';
    const boton_añadir_manual = document.getElementById('addManual');
    boton_añadir_manual.style.display = manual === 'Si' ? 'block' : 'none';
}
</script>
</body> 
</html>
