<?php
require './../../layout/head.html';
require './../../layout/header.php';
require './../../db/dbconn.php';
require './../../utils/session_check.php';
require_once './../../../library/phpqrcode/qrlib.php';

$docs = array_diff(scandir('./../../../docs/manuales'), array('.', '..'));
function ejecutar_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

function generateQR($codigo) {
    $qrImagePath = './../../docs/qr_codes/item_' . $codigo . '.png';
    
    if (!file_exists('./../../docs/qr_codes')) {
        mkdir('./../../docs/qr_codes', 0777, true);
    }
    
    QRcode::png($codigo, $qrImagePath, QR_ECLEVEL_L, 10);
    
    return ['code' => $codigo, 'path' => $qrImagePath];
}

$qrGeneratedMessage = '';
$error_message = '';

if (isset($_POST['submit_item'])) {
     // Validar que los campos necesarios estén presentes
    if (empty($_POST['mat_nombre']) || 
            empty($_POST['mat_descripcion']) || 
            $_POST['id_mat_medida'] === '0' || 
            $_POST['id_estado'] === '0' || 
            empty($_POST['mat_cantidad']) || 
            empty($_POST['mat_minimo1'])) {

            $error_message = "Por favor, complete todos los campos requeridos para continuar.";
    } else {
        try {
            $conn->beginTransaction();
            // Manejar la subida de la foto si se proporciona
            $foto_path = './../../public/ico/material.svg'; // Imagen predeterminada
            if (isset($_FILES['mat_foto']) && $_FILES['mat_foto']['error'] == UPLOAD_ERR_OK) {
                $foto_dir = './../../docs/materiales_fotos/';
                if (!file_exists($foto_dir)) {
                    mkdir($foto_dir, 0777, true);
                }
                $foto_path = $foto_dir . basename($_FILES['mat_foto']['name']);
                if (!move_uploaded_file($_FILES['mat_foto']['tmp_name'], $foto_path)) {
                    $error_message = "Error al subir la foto del material. Por favor, inténtelo de nuevo.";
                }
            }

            // Generar QR para materiales usando id_materiales como código
            $id_materiales = 'M-' . $_POST['id_materiales'];
            $qrData = generateQR($id_materiales);

            // Preparar la consulta para materiales
            $query = "INSERT INTO materiales (id_materiales, mat_nombre, mat_descripcion, mat_cantidad, mat_minimo1, mat_minimo2, id_mat_medida, codigo, qr_image_path, id_estado, id_mat_area, mat_foto) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $id_materiales, 
                $_POST['mat_nombre'], 
                $_POST['mat_descripcion'], 
                $_POST['mat_cantidad'], 
                $_POST['mat_minimo1'], 
                $_POST['mat_minimo2'], 
                $_POST['id_mat_medida'], 
                $qrData['code'], 
                $qrData['path'], 
                $_POST['id_estado'], 
                $_POST['id_mat_area'], 
                $foto_path
            ];

            ejecutar_query($conn, $query, $params);

            $qrGeneratedMessage = "<div class='col-12 col-sm-4 alert d-flex justify-content-between alert-success'>Material insertado correctamente
            <button type='button' class='btn-close justify-content-end' data-bs-dismiss='alert' aria-label='Cerrar'></button></div>";
            
            $conn->commit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message = "Hubo un error al insertar el material: " . htmlspecialchars($e->getMessage());
        }
    }
}

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

function obtener_estados ($conn) {
    $query = "SELECT idestado, descripcion FROM estado WHERE idestado LIKE 'B%'";
    return ejecutar_query($conn, $query)->fetchAll(PDO::FETCH_ASSOC);
}

$resultado_estados = obtener_estados($conn);

function renderInput($name, $details) {
    $label = $details['label'];
    $type = $details['type'];
    $required = isset($details['required']) && $details['required'] ? 'required' : '';
    
    $html = "<div class='row py-2'>
                <div class='col-6 d-flex align-items-center'>
                    <label for='$name' class='form-label'>$label:</label>
                </div>
                <div class='col-6 d-flex align-items-end'>";
    
    if ($type == 'select') {
        $options = $details['options'];
        $html .= "<select class='form-select' id='$name' name='$name' $required>
                    <option value='0' selected>Elegir una opción</option>";
        foreach ($options as $option) {
            $value = $option[array_keys($option)[0]];
            $text = $option[array_keys($option)[1]];
            $html .= "<option value='$value'>$text</option>";
        }
        $html .= "</select>";
    } else if ($type == 'textarea') {
        $html .= "<textarea id='$name' class='form-control' name='$name' minlength='5' maxlength='150' $required></textarea>";
    } else {
        $html .= "<input type='$type' class='form-control border-2 rounded-3' id='$name' name='$name' minlength='5' maxlength='200' $required>";
    }
    
    $html .= "  </div>
            </div>";
    return $html;
}

$campos = [
    'id_materiales' => ['label' => 'CÓDIGO DEL MATERIAL', 'type' => 'text', 'required' => true],
    'mat_nombre' => ['label' => 'NOMBRE DEL MATERIAL', 'type' => 'text', 'required' => true],
    'mat_descripcion' => ['label' => 'DESCRIPCIÓN DEL MATERIAL', 'type' => 'textarea', 'required' => true],
    'id_estado' => ['label' => 'ESTADO', 'type' => 'select', 'options' => $resultado_estados, 'required' => false],
    'id_mat_area' => ['label' => 'ÁREA DE DESTINO', 'type' => 'select', 'options' => $resultado_mat_areas, 'required' => false],
    'id_mat_medida' => ['label' => 'UNIDAD DE MEDIDA', 'type' => 'select', 'options' => $resultado_mat_medida, 'required' => false],
    'mat_cantidad' => ['label' => 'CANTIDAD', 'type' => 'number', 'required' => true],
    'mat_minimo1' => ['label' => 'CANTIDAD MÍNIMA 1 (Retención)', 'type' => 'number', 'required' => true],
    // 'mat_minimo2' => ['label' => 'CANTIDAD MÍNIMA 2', 'type' => 'number', 'required' => true],
];
?>

<!-- SECCIÓN de MATERIALES -->
<title>Nuevo material | SAM Assistant</title>
</head>
<body>
    <section class="container-fluid mt-3 ">
        <h5 class="w-100 mb-2 bg-plomo p-2"><B>INSERTAR NUEVO MATERIAL</B></h5>
        <!-- <form method="POST" class="px-3" enctype="multipart/form-data onsubmit="return validateForm()"> -->
        <form id="materialesForm" method="POST" class="px-3" enctype="multipart/form-data">
    
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
        <!-- Mostrar mensajes de error/éxito en la interfaz -->
        <div class="row my-1 ms-1 d-flex ">
            <?php if (!empty($error_message)): ?>
                <div class="col-12 col-lg-6 d-flex alert alert-danger alert-dismissible fade show" role="alert">
                    <strong class="pe-1">Error:</strong> <?php echo $error_message; ?>
                    <button type="button" class="btn-close " data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($qrGeneratedMessage)): ?>
                <?php echo $qrGeneratedMessage; ?>
            <?php endif; ?>
        </div>
            <section id="materialesFields" >
            <!-- Sección INFORMACIÓN -->
                <section class="row">
                    <div class="col-12">
                        <div class="w-100 bg-plomo mb-2 p-1"><b>INFORMACIÓN</b></div>
                        <div class="card rounded-4 px-1 mb-3">
                            <div class="row m-1">
                                <!-- Columna 1 -->
                                <div class="col-12 col-sm-6 d-flex flex-column position-relative">
                                    <?php
                                    echo renderInput('id_materiales', $campos['id_materiales']);
                                    echo renderInput('mat_nombre', $campos['mat_nombre']);
                                    echo renderInput('mat_descripcion', $campos['mat_descripcion']);
                                    ?>
                                </div>
                                <!-- Columna 2 -->
                                <div class="col-12 col-sm-6 d-flex flex-column position-relative">
                                    <?php
                                    echo renderInput('id_estado', $campos['id_estado']);
                                    ?>
                                <div class="row py-2 d-flex">
                                    <!-- Campo de selección de imagen -->
                                    <div class="col-6 d-flex align-items-center detail-row">
                                        <label for="mat_foto" class="form-label">IMAGEN DEL MATERIAL:</label>
                                    </div>
                                    <div class="col-6 d-flex align-items-end">
                                        <input type="file" class="form-control border-2 rounded-3" id="mat_foto" name="mat_foto" accept="image/*">
                                    </div>
                                    <!-- Imagen de previsualización -->
                                    <div class="col-5  align-items-center">
                                            <img id="preview" class="img-thumbnail d-none mt-3 rounded"  alt="Vista previa">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            <!-- Sección DETALLES -->
                <section class="row">
                    <div class="col-12">
                        <div class="w-100 bg-plomo mb-2 p-1"><b>DETALLES</b></div>
                        <div class="card rounded-4 px-1 mb-3">
                            <div class="row m-1">
                                <!-- Columna 1 -->
                                <div class="col-12 col-sm-6 d-flex flex-column position-relative">
                                    <?php
                                    echo renderInput('id_mat_area', $campos['id_mat_area']);
                                    echo renderInput('id_mat_medida', $campos['id_mat_medida']);
                                    ?>
                                </div>
                                <!-- Columna 2 -->
                                <div class="col-12 col-sm-6 d-flex flex-column position-relative">
                                    <?php
                                    echo renderInput('mat_cantidad', $campos['mat_cantidad']);
                                    echo renderInput('mat_minimo1', $campos['mat_minimo1']);
                                    // echo renderInput('mat_minimo2', $campos['mat_minimo2']);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
            </section>
            <div id="alertaError" class="alert alert-danger d-none" role="alert">
            </div>
            <button class="btn btn-primary my-3" type="submit" name="submit_item">Insertar material </button>
        </form>
    </section>
    <?php require './../../layout/footer.htm'; ?>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <!-- SCRIPTS PARA MATERIALES -->
    <script>
document.addEventListener("DOMContentLoaded", function() {
    // ----------------- VALIDACIÓN DEL FORMULARIO -----------------
    /**
     * Valida los campos obligatorios antes de enviar el formulario.
     * Resalta los campos vacíos y muestra un mensaje de error.
     */
    function validateForm() {
        let campos = [
            "id_materiales", "mat_nombre", "mat_descripcion", "id_estado",
            "id_mat_area", "id_mat_medida", "mat_cantidad", "mat_minimo1"
        ];
        let nombresCampos = {
            "id_materiales": "Código del Material",
            "mat_nombre": "Nombre del Material",
            "mat_descripcion": "Descripción",
            "id_estado": "Estado",
            "id_mat_area": "Área de Destino",
            "id_mat_medida": "Unidad de Medida",
            "mat_cantidad": "Cantidad",
            "mat_minimo1": "Cantidad Mínima 1"
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

    // ✅ Asigna la validación del formulario antes de enviarlo
    document.getElementById("materialesForm").addEventListener("submit", function(event) {
        if (!validateForm()) {
            event.preventDefault();
        }
    });

    // ----------------- VISTA PREVIA DE IMAGEN -----------------
    /**
     * Muestra la imagen seleccionada en el input antes de enviarla.
     */
    function previewImage(event) {
        var input = event.target;
        var reader = new FileReader();

        reader.onload = function() {
            var preview = document.getElementById("preview");
            preview.src = reader.result;
            preview.classList.remove("d-none"); // ✅ Hace visible la imagen
        };

        if (input.files && input.files[0]) {
            reader.readAsDataURL(input.files[0]); // ✅ Convierte la imagen en una URL temporal
        }
    }

    // ✅ Asigna la función de previsualización a `mat_foto`
    document.getElementById("mat_foto")?.addEventListener("change", previewImage);

    // ----------------- MOSTRAR/OCULTAR CAMPOS -----------------
    /**
     * Alterna la visibilidad de materiales según selección.
     */
    function togglematerialesFields() {
        const tipoItem = document.getElementById("tipo_item").value;
        const herramientasFields = document.getElementById("herramientasFields");
        const materialesFields = document.getElementById("materialesFields");

        if (!herramientasFields || !materialesFields) return; // ✅ Evita errores si los elementos no existen

        herramientasFields.classList.toggle("d-none", tipoItem !== "herramientas");
        materialesFields.classList.toggle("d-none", tipoItem !== "materiales");

        // ✅ Manejo de `required` para materiales
        const materialesInputs = materialesFields.querySelectorAll("[required]");
        materialesInputs.forEach(input => {
            input.required = tipoItem === "materiales";
        });
    }

    // ✅ Ejecutar la función al cargar la página
    document.addEventListener("DOMContentLoaded", function() {
        togglematerialesFields(); // ✅ Funciona correctamente en `materiales.php`
    });

    // ----------------- REDIRECCIÓN -----------------
    /**
     * Mantiene la opción seleccionada después de cambiar de página.
     */
    var select = document.getElementById("tipo_item");
    var params = new URLSearchParams(window.location.search);
    var selectedValue = params.get("tipo_item");

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
    //     function togglematerialesFields() {
    //         const tipoItem = document.getElementById('tipo_item').value;
    //         const herramientasFields = document.getElementById('herramientasFields');
    //         const materialesFields = document.getElementById('materialesFields');

    //         // Mostrar/ocultar secciones
    //         herramientasFields.classList.toggle('d-none', tipoItem !== 'herramientas');
    //         materialesFields.classList.toggle('d-none', tipoItem !== 'materiales');
    //        // Obtener todos los campos required de materiales
    //         const materialesInputs = materialesFields.querySelectorAll('[required]');
    //         // Manejar required para materiales
    //         materialesInputs.forEach(input => {
    //             input.required = tipoItem === 'materiales';
    //         });
    //     }
    //     // Ejecutar la función al cargar la página
    //     document.addEventListener('DOMContentLoaded', function() {
    //         togglematerialesFields();
    //     });
    // document.addEventListener("DOMContentLoaded", function() {
    //     var select = document.getElementById("tipo_item");
    //     var params = new URLSearchParams(window.location.search);
    //     var selectedValue = params.get("tipo_item"); // Obtener la opción desde la URL

    //     // Mantener la opción seleccionada en el dropdown
    //     if (selectedValue) {
    //         select.value = selectedValue;
    //     }
    // });  
    // function redirectToPage() {
    //     var select = document.getElementById("tipo_item");
    //     var value = select.value;

    //     if (value === "herramientas") {
    //         window.location.href = "./herramientas.php?tipo_item=herramientas";
    //     } else if (value === "materiales") {
    //         window.location.href = "./materiales.php?tipo_item=materiales";
    //     }
    // }
    // // ----------------- VISTA PREVIA DE IMAGEN -----------------
    // function previewImage(event) {
    //     var input = event.target;
    //     var reader = new FileReader();

    //     reader.onload = function() {
    //         var preview = document.getElementById("preview");
    //         preview.src = reader.result;
    //         preview.classList.remove("d-none");
    //     };

    //     if (input.files && input.files[0]) {
    //         reader.readAsDataURL(input.files[0]);
    //     }
    // }

    // document.getElementById("mat_foto")?.addEventListener("change", previewImage);
    </script>
</body>
</html>