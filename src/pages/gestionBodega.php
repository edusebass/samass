<?php
require './../layout/header.php'; 
require './../utils/session_check.php';
require_once './../db/dbconn.php';
require_once './../utils/reinicio_sesion.php';
require_once './../utils/ejecutar_query.php';
require './../layout/head.html';

protegerPagina("gestionBodega.php");

// Inicialización de variables
$error_mensaje = $nombre_voluntario = $descripcion_producto = $cantidad_pedida = $cantidad_sobrante = $check_mensaje = "";
$mostrar_codigo = $mostrar_escaner = $mostrar_descripcion_producto = $mostrar_devolucion_producto = $mostrar_devolucion_material = $isMostrarInputCandtidad = false;
$productos_asignados = [];
$materiales_asignados = [];

// Inicialización de sesión
$_SESSION['qr_content'] = $_SESSION['qr_content'] ?? ''; // Contenido del QR escaneado
if (isset($_POST['verificar_id'])) {
    $_SESSION['id_voluntario'] = $_POST['id'];
}

$conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Función para obtener productos asignados
function obtener_productos_asignados($conn, $id_voluntario) {
    $query = "SELECT o.itemid, 
                     i.nombre,
                     i.codigo, 
                     i.descripccion, 
                     1 AS cantidad,
                     o.fechasalida
              FROM operaciones o 
              JOIN items i ON o.itemid = i.iditems 
              WHERE o.voluntarioid = ? 
              AND o.fechaentrada IS NULL 
              ORDER BY o.fechasalida DESC";
    return ejecutar_query($conn, $query, [$id_voluntario])->fetchAll(PDO::FETCH_ASSOC);
}

function obtener_materiales_asignados($conn, $id_voluntario) {
    $query = "SELECT o.itemid, 
                     m.mat_descripcion,
                     m.codigo,
                     o.fechasalida
              FROM operaciones o 
              JOIN materiales m ON o.itemid = m.id_materiales
              WHERE o.voluntarioid = ? 
              AND o.fechaentrada IS NULL 
              ORDER BY o.fechasalida DESC";
    return ejecutar_query($conn, $query, [$id_voluntario])->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener nombre del voluntario
function obtener_nombre_voluntario($conn, $id_voluntario) {
    return ejecutar_query($conn, "SELECT nome FROM user WHERE voluntario = ?", [$id_voluntario])->fetchColumn();
}

// Función para devolver producto
function devolver_producto($conn, $item_codigo_qr, $voluntario_id, $cantidad_material_devolucion) {
    $conn->beginTransaction();
    try {
        if (strpos($_SESSION['qr_content'], 'mat') === 0) {
            // Obtener el id del item en base al codigo qr
            $query_obtener_id = "SELECT id_materiales FROM materiales WHERE codigo = ?";
            $id_material = ejecutar_query($conn, $query_obtener_id,[$item_codigo_qr])->fetch(PDO::FETCH_ASSOC);

            // Marcar el producto como devuelto en la tabla operaciones
            $query_update_operaciones = "UPDATE operaciones 
                                        SET fechaentrada = NOW(), limpio = 'si'
                                        WHERE itemid = ? AND voluntarioid = ? AND fechaentrada IS NULL ";
            ejecutar_query($conn, $query_update_operaciones, [ $id_material['id_materiales'], $voluntario_id]);

            // Actualizar la cantidad en la tabla materiales peidr la cantidad de materiales a devolver
            $query_update_material = "UPDATE materiales SET mat_cantidad = mat_cantidad + ? WHERE id_materiales = ?";
            ejecutar_query($conn, $query_update_material, [ $cantidad_material_devolucion,  $id_material['id_materiales']]);

            // // Calcular el tiempo de uso **
            // $query_calculo_uso =  "SELECT TIMESTAMPDIFF(MINUTE, fechasalida, fechaentrada) AS minutos_diferencia
            //                         FROM operaciones
            //                         WHERE itemid = ? AND voluntarioid = ? ";
            // $resultado_uso = ejecutar_query($conn, $query_calculo_uso, [ $id_material['id_materiales'], $voluntario_id,]);
            // $minutos_uso = $resultado_uso->fetchColumn();
            // if ($minutos_uso !== false) {
            //     $query_actualizar_uso = "UPDATE items SET uso = uso + ? WHERE iditems = ?";
            //     ejecutar_query($conn, $query_actualizar_uso, [$minutos_uso,  $id_material['id_materiales']]);
            // }

        } else {
            // Obtener el id del item en base al codigo qr
            $query_obtener_id = "SELECT iditems FROM items WHERE codigo = ?";
            $iditem = ejecutar_query($conn, $query_obtener_id,[$item_codigo_qr])->fetch(PDO::FETCH_ASSOC);

            // Marcar el producto como devuelto en la tabla operaciones
            $query_update_operaciones = "UPDATE operaciones 
                                        SET fechaentrada = NOW(), limpio = 'si'
                                        WHERE itemid = ? AND voluntarioid = ? AND fechaentrada IS NULL ";
            ejecutar_query($conn, $query_update_operaciones, [ $iditem['iditems'], $voluntario_id]);

            // Actualizar la cantidad en la tabla items
            $query_update_items = "UPDATE items SET cantidad = cantidad + 1 WHERE iditems = ?";
            ejecutar_query($conn, $query_update_items, [  $iditem['iditems']]);

            // Calcular el tiempo de uso
            $query_calculo_uso =  "SELECT TIMESTAMPDIFF(MINUTE, fechasalida, fechaentrada) AS minutos_diferencia
                                    FROM operaciones
                                    WHERE itemid = ? AND voluntarioid = ? ";
            $resultado_uso = ejecutar_query($conn, $query_calculo_uso, [ $iditem['iditems'], $voluntario_id,]);
            $minutos_uso = $resultado_uso->fetchColumn();
            if ($minutos_uso !== false) {
                $query_actualizar_uso = "UPDATE items SET uso = uso + ? WHERE iditems = ?";
                ejecutar_query($conn, $query_actualizar_uso, [$minutos_uso,  $iditem['iditems']]);
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollBack();
        return false;
    }
}

// Lógica principal
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verificar_id'])) { // Verificar el nombre del voluntario
        $nombre_voluntario = obtener_nombre_voluntario($conn, $_POST['id']);
        if ($nombre_voluntario) {
            $_SESSION['id_voluntario'] = $_POST['id'];
            $productos_asignados = obtener_productos_asignados($conn, $_POST['id']);
            $materiales_asignados = obtener_materiales_asignados($conn, $_SESSION['id_voluntario']);
            $mostrar_codigo = true;
            $mostrar_escaner = true;
        } else {
            $error_mensaje = "ID de voluntario no encontrado.";
        }
    } 
    elseif (isset($_POST['confirmar_devolucion'])) { // Confirmar la devolución del producto
        devolver_producto($conn, $_SESSION['codigo_item'], $_SESSION['id_voluntario'], $_POST['cantidad_material']);
        $check_mensaje = "Producto devuelto correctamente";     
        unset($_SESSION['qr_content']);
        $productos_asignados = obtener_productos_asignados($conn, $_SESSION['id_voluntario']);
        $materiales_asignados = obtener_materiales_asignados($conn, $_SESSION['id_voluntario']);
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['asignacion_mat_cantidad'])){
        guardar_material($conn, $_SESSION['qr_content'], $_SESSION['id_voluntario'], $_POST['cantidad_material_asignacion']);
    }
}

function guardar_material($conn, $codigo_material, $voluntario_id, $cantidad_material_asignacion) {
    $error_mensaje = "Código QR materiallll";
    $query_obtener_producto = "SELECT m.mat_descripcion, m.mat_cantidad 
    FROM materiales m
    WHERE m.codigo = ?";

    $stmt = ejecutar_query($conn, $query_obtener_producto, [$_SESSION['qr_content']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $_SESSION['codigo_item'] = $_SESSION['qr_content'];

        // Verificar asignación previa
        $query_asignado = "SELECT COUNT(*) FROM operaciones 
        WHERE itemid = (SELECT id_materiales FROM materiales WHERE codigo = ?)
        AND fechaentrada IS NULL";
        $material_asignado = ejecutar_query($conn, $query_asignado, [$_SESSION['qr_content']])->fetchColumn();
        if ($material_asignado == 0) {

            //insertar item logica
            $query_obtener_material_id = "SELECT id_materiales FROM materiales WHERE codigo = ?";
            $id_material = ejecutar_query($conn, $query_obtener_material_id, [$_SESSION['qr_content']])->fetchColumn();

            $query_insert = "INSERT INTO operaciones (voluntarioid, itemid, cantidad, fechasalida) VALUES (?, ?, ?, NOW())";
            ejecutar_query($conn, $query_insert, [$_SESSION['id_voluntario'], $id_material, $cantidad_material_asignacion]);

            $query_restar_stock = "UPDATE materiales SET mat_cantidad = mat_cantidad - ? WHERE id_materiales = ?";
            ejecutar_query($conn, $query_restar_stock, [$cantidad_material_asignacion, $id_material]);

            $check_mensaje = "Asignaciones insertadas correctamente y stock actualizado.";

            // Resetea el contenido del QR después de procesar el ítem
            unset($_SESSION['qr_content']);
            $mostrar_escaner = false;

        } elseif ($material_asignado == 1) {
            $query_asignado_previo = "SELECT COUNT(*) FROM operaciones 
                WHERE itemid = (SELECT id_materiales FROM materiales WHERE codigo = ?)
                AND fechaentrada IS NULL 
                AND voluntarioid = ?";
            $item_asignado_a_voluntario = ejecutar_query($conn, $query_asignado_previo, 
            [$_SESSION['qr_content'], $_SESSION['id_voluntario']])->fetchColumn();

            $query_voluntario_del_producto = "SELECT voluntarioid  FROM operaciones 
                WHERE itemid = (SELECT id_materiales FROM materiales WHERE codigo = ?)
                AND fechaentrada IS NULL ";

            $voluntarioid_del_material = ejecutar_query($conn, $query_voluntario_del_producto,[$_SESSION['qr_content']])->fetchColumn();

            if ($item_asignado_a_voluntario == 1) {
                $error_mensaje = "Este item ya ha sido asignado a este voluntario ." . $voluntarioid_del_material . " tiene la opcion de devolverlo";
                $mostrar_devolucion_material = true;
            } else {
                $error_mensaje = "Este item le pertenece a " . $voluntarioid_del_material;
            }
        }
    } else {
        $error_mensaje = "Código QR no encontrado.";
    }

}

if (isset($_SESSION['qr_content']) && !empty($_SESSION['qr_content'])) {
    if (strpos($_SESSION['qr_content'], 'mat') === 0) { //LOGICA CUANDO ES MATERIAL

        $query = "SELECT mat_cantidad FROM materiales WHERE id_materiales = ?";
        $cantidad_sobrante = ejecutar_query($conn, $query, [$_SESSION['qr_content']])->fetchColumn();

        $query_cantidapedida = "SELECT cantidad FROM operaciones WHERE itemid = ? AND voluntarioid = ? AND fechaentrada IS NULL";
        $cantidad_pedida = ejecutar_query($conn, $query_cantidapedida, [$_SESSION['qr_content'], $_SESSION['id_voluntario']])->fetchColumn();

        $query_obtener_producto = "SELECT m.mat_descripcion, m.mat_cantidad 
        FROM materiales m
        WHERE m.codigo = ?";
    
        $stmt = ejecutar_query($conn, $query_obtener_producto, [$_SESSION['qr_content']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $descripcion_producto = "{$row['mat_descripcion']}";
        $query_asignado_previo = "SELECT COUNT(*) FROM operaciones 
                WHERE itemid = (SELECT id_materiales FROM materiales WHERE codigo = ?)
                AND fechaentrada IS NULL 
                AND voluntarioid = ?";
            $item_asignado_a_voluntario = ejecutar_query($conn, $query_asignado_previo, 
            [$_SESSION['qr_content'], $_SESSION['id_voluntario']])->fetchColumn();

        $query_voluntario_del_producto = "SELECT voluntarioid  FROM operaciones 
        WHERE itemid = (SELECT id_materiales FROM materiales WHERE codigo = ?)
        AND fechaentrada IS NULL ";

        $voluntarioid_del_material = ejecutar_query($conn, $query_voluntario_del_producto,[$_SESSION['qr_content']])->fetchColumn();

        if ($item_asignado_a_voluntario == 1) {
            $error_mensaje = "Este item ya ha sido asignado a este voluntario ." . $voluntarioid_del_material . " tiene la opcion de devolverlo";
            $mostrar_devolucion_material = true;
        } else if ($voluntarioid_del_material) {
            $error_mensaje = "Este item le pertenece a " . $voluntarioid_del_material;
        } else {
            $isMostrarInputCandtidad = true;
        }

    } else { //LOGICA CUANDO ES UNA HERRAMIENTA
        // Solo procesar el código QR cuando se haya escaneado
        $query_obtener_producto = "SELECT i.nombre, i.descripccion, i.cantidad 
        FROM items i
        WHERE i.codigo = ?";

        $stmt = ejecutar_query($conn, $query_obtener_producto, [$_SESSION['qr_content']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $_SESSION['codigo_item'] = $_SESSION['qr_content'];
            $descripcion_producto = "{$row['nombre']}: {$row['descripccion']}";
            $cantidad_disponible = $row['cantidad'];

            // Verificar asignación previa
            $query_asignado = "SELECT COUNT(*) FROM operaciones 
            WHERE itemid = (SELECT iditems FROM items WHERE codigo = ?)
            AND fechaentrada IS NULL";
            $item_asignado = ejecutar_query($conn, $query_asignado, [$_SESSION['qr_content']])->fetchColumn();

            if ($item_asignado == 0) {
                $mostrar_descripcion_producto = true;
                $descripcion_producto .= " (Disponibles: $cantidad_disponible)";

                //insertar item logica
                $query_obtener_item_id = "SELECT iditems FROM items WHERE codigo = ?";
                $id_item = ejecutar_query($conn, $query_obtener_item_id, [$_SESSION['qr_content']])->fetchColumn();

                $query_insert = "INSERT INTO operaciones (voluntarioid, itemid, cantidad, fechasalida) VALUES (?, ?, 1, NOW())";
                ejecutar_query($conn, $query_insert, [$_SESSION['id_voluntario'], $id_item]);

                $check_mensaje = "Asignaciones insertadas correctamente y stock actualizado.";

                // Resetea el contenido del QR después de procesar el ítem
                unset($_SESSION['qr_content']);
                $mostrar_escaner = false;

            } elseif ($item_asignado == 1) {
                $query_asignado_previo = "SELECT COUNT(*) FROM operaciones 
                    WHERE itemid = (SELECT iditems FROM items WHERE codigo = ?)
                    AND fechaentrada IS NULL 
                    AND voluntarioid = ?";
                $item_asignado_a_voluntario = ejecutar_query($conn, $query_asignado_previo, 
                [$_SESSION['qr_content'], $_SESSION['id_voluntario']])->fetchColumn();

                $query_voluntario_del_producto = "SELECT voluntarioid  FROM operaciones 
                    WHERE itemid = (SELECT iditems FROM items WHERE codigo = ?)
                    AND fechaentrada IS NULL ";
                $voluntarioid_del_item = ejecutar_query($conn, $query_voluntario_del_producto,[$_SESSION['qr_content']])->fetchColumn();
                if ($item_asignado_a_voluntario == 1) {
                    $error_mensaje = "Este item ya ha sido asignado a este voluntario ." . $nombre_voluntario . "tiene la opcion de devolverlo";
                    $mostrar_devolucion_producto = true;
                } else {
                    $error_mensaje = "Este item le pertenece a " . $voluntarioid_del_item;
                }
            }
        } else {
            $error_mensaje = "Código QR no encontrado.";
        }
    }
    
}
// Recuperar información de la sesión
if (isset($_SESSION['id_voluntario'])) {
    $nombre_voluntario = obtener_nombre_voluntario($conn, $_SESSION['id_voluntario']);
    $mostrar_codigo = true;
    $productos_asignados = obtener_productos_asignados($conn, $_SESSION['id_voluntario']);
    $materiales_asignados = obtener_materiales_asignados($conn, $_SESSION['id_voluntario']);
}

// Si viene una petición AJAX para guardar el contenido del QR
// Manejo de la petición AJAX
if (isset($_GET['save_qr']) && isset($_GET['content'])) {
    $_SESSION['qr_content'] = $_GET['content'];
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'content' => $_GET['content']
    ]);
    exit;
}

// Lógica de reinicio
if (isset($_POST['reiniciar']) && $_POST['reiniciar'] == 'true') {
    // Resetear todas las variables de control
    $error_mensaje = '';
    $nombre_voluntario = '';
    $descripcion_producto = '';
    $cantidad_pedida = ''; 
    $cantidad_sobrante = '';
    $check_mensaje = '';
    $mostrar_codigo = false;
    $mostrar_escaner = false;
    $mostrar_descripcion_producto = false;
    $mostrar_devolucion_producto = false;
    $mostrar_devolucion_material = false;
    $isMostrarInputCandtidad = false;
    $productos_asignados = [];
    $materiales_asignados = [];

    // Limpiar variables de sesión específicas
    unset($_SESSION['id_voluntario']);
    unset($_SESSION['qr_content']);
    unset($_SESSION['codigo_item']);
}

// Add this near the top of your PHP script, before any output
if (isset($_GET['reset']) && $_GET['reset'] == 'true') {
    // Reset specific session variables
    unset($_SESSION['id_voluntario']);
    unset($_SESSION['qr_content']);
    unset($_SESSION['codigo_item']);
    
    // You might want to reset some of your control variables
    $mostrar_codigo = false;
    $mostrar_escaner = false;
    $productos_asignados = [];
    $materiales_asignados = [];
}



?>
    <title>SAM Assistant</title>
    <link rel="stylesheet" type="text/css" href="gestionBodega.css">
</head>
<body>
    <?php if ($error_mensaje): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow position-fixed" style="top:50px; right:15px;" role="alert">
            <strong>Error</strong> <?php echo htmlspecialchars($error_mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <br>
    <?php if ($check_mensaje): ?>
        <div class="alert alert-success alert-dismissible fade show shadow position-fixed" style="top:50px; right:15px;" role="alert">
            <?php echo htmlspecialchars($check_mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <br>

    <!--Operaciones -->
    <section class="container-fluid">
        <div class="row">

            <!-- sección izquierda -->
            <div class="col-sm-12 col-md-5 col-lg-6">
                <div class="row">
                    <div class="col-12">
                        <div class="bg-plomo w-100 mb-2 p-1" >
                            <b>OPERACIONES</b>
                            <button id="reiniciarTodo" class="btn btn-secondary btn-sm">
                                <i class="fas fa-sync-alt"></i> Reiniciar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <form method="post" class="card p-3 rounded-4 w-100 d-block" style="border: solid 2px #E4640D;">
                            <div class="row gy-2 gx-3 align-items-center">
                                <div class="col-auto">
                                    <label class="form-label" for="idInput" style="color:#5C6872;">ID de Voluntario:</label>
                                </div>
                                <div class="col-auto">
                                    <input class="form-control border-2 rounded-3" type="text" id="idInput" name="id" value="<?php echo htmlspecialchars($_SESSION['id_voluntario'] ?? ''); ?>" required>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-primary" type="submit" name="verificar_id">Verificar</button>    
                                </div>
                                <div class="col-auto">
                                    <?php if ($nombre_voluntario): ?>
                                        <span style="color:#5C6872; font-weight:bold;">Voluntario: <?php echo htmlspecialchars($nombre_voluntario); ?></span>
                                    <?php endif; ?>    
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if ($mostrar_codigo): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card p-3 rounded-4 w-100 d-block">
                                <?php if ($isMostrarInputCandtidad): ?>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="bg-plomo w-100 mb-2 p-1" style="text-align:left; ">
                                                <b>ASGINACION DE MATERIAL (ingreso de cantidad)</b>
                                            </div>
                                        </div>
                                    </div>
                                    <form method="post" >
                                        <div class="row gy-2 gx-3 align-items-center">
                                            <p style="color:#5C6872; font-weight:bold;">Material: <?php echo htmlspecialchars($descripcion_producto); ?></p>
                                            <p style="color:#5C6872; font-weight:bold;">Cantidad disponible: <?php echo htmlspecialchars($cantidad_sobrante); ?></p>
                                            <p style="color:#5C6872; font-weight:bold;">Codigo: <?php echo htmlspecialchars($_SESSION['qr_content']); ?></p>

                                            <div class="mb-3">
                                                <label for="cantidad_material_asignacion" class="form-label">Cantidad requeridad:</label>
                                                <input type="number" class="form-control" id="cantidad_material_asignacion" name="cantidad_material_asignacion" min="1" required>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary" name="asignacion_mat_cantidad">Confirmar</button>
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <?php if ($mostrar_devolucion_producto): ?>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="bg-plomo w-100 mb-2 p-1" style="text-align:left; ">
                                                <b>HERRAMIENTAS O MATERIAL (seccion de devolucion)</b>
                                            </div>
                                        </div>
                                    </div>
                                    <form method="post" >
                                        <div class="row gy-2 gx-3 align-items-center">
                                            <div class="col-auto">  
                                                <h2>
                                                    Devolver ítem
                                                </h2>
                                            </div>
                                            <p style="color:#5C6872; font-weight:bold;">Herramienta: <?php echo htmlspecialchars($descripcion_producto); ?></p>
                                            <p style="color:#5C6872; font-weight:bold;">Codigo: <?php echo htmlspecialchars($_SESSION['qr_content']); ?></p>
                                            <div class="modal-body">
                                                <p>Por favor, indique si el ítem está limpio o no. Si lo esta de en CONFIRMAR</p>
                                            
                                            <button type="submit" class="btn btn-primary" name="confirmar_devolucion">Confirmar</button>km
                                        </div>
                                    </form>
                                <?php endif; ?>

                                <?php if ($mostrar_devolucion_material): ?>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="bg-plomo w-100 mb-2 p-1" style="text-align:left; ">
                                                <b>HERRAMIENTAS O MATERIAL (seccion de devolucion)</b>
                                            </div>
                                        </div>
                                    </div>
                                    <form method="post">
                                        <div class="row gy-2 gx-3 align-items-center">
                                            <div class="col-auto">  
                                                <h2>Devolver material</h2>
                                            </div>
                                            
                                            <p style="color:#5C6872; font-weight:bold;">Material: <?php echo htmlspecialchars($descripcion_producto); ?></p>
                                            <p style="color:#5C6872; font-weight:bold;">Cantidad pedida: <?php echo htmlspecialchars($cantidad_pedida); ?></p>

                                            <p style="color:#5C6872; font-weight:bold;">Codigo: <?php echo htmlspecialchars($_SESSION['qr_content']); ?></p>

                                            <div class="modal-body">
                                                <p>Por favor, indique si el ítem está limpio o no. Si lo está, dé en CONFIRMAR</p>
                                                <div class="mb-3">
                                                    <label for="cantidad_material" class="form-label">Cantidad a devolver:</label>
                                                    <input type="number" class="form-control" id="cantidad_material" name="cantidad_material" min="1" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary" name="confirmar_devolucion">Confirmar</button>
                                            </div>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- sección derecha -->
<div class="col-sm-12 col-md-7 col-lg-6">
    
    <?php if (isset($nombre_voluntario) && !empty($nombre_voluntario)): ?>
        <?php if (!empty($productos_asignados)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="w-100 mb-2 p-1" style="text-align:left; background-color: #e8ecf2; color:#5C6872; text-transform:uppercase;">
                        <b>Items (herramientas) no devueltos todavía</b>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card p-3 rounded-4 w-100 d-block" style="border: solid 2px #E4640D;">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle">
                                <thead>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Fecha de Salida</th>
                                    <th>Codigo </th>
                                </thead>
                                <?php foreach ($productos_asignados as $producto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td style="text-align:center;"><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['fechasalida']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p>No hay productos asignados.</p>
        <?php endif; ?>
        <?php if (!empty($materiales_asignados)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="w-100 mb-2 p-1" style="text-align:left; background-color: #e8ecf2; color:#5C6872; text-transform:uppercase;">
                        <b>Materiales no devueltos todavía</b>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card p-3 rounded-4 w-100 d-block" style="border: solid 2px #E4640D;">
                        <div class="table-responsive">
                            <table class="table table-borderless align-middle">
                                <thead>
                                    <th>Descripccion del material</th>
                                    <th>Fecha de Salida</th>
                                    <th>Codigo </th>
                                </thead>
                                <?php foreach ($materiales_asignados as $material): ?>
                                    <tr>
                                        <td style="text-align:center;"><?php echo htmlspecialchars($material['mat_descripcion']); ?></td>
                                        <td><?php echo htmlspecialchars($material['fechasalida']); ?></td>
                                        <td><?php echo htmlspecialchars($material['codigo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p>No hay productos asignados.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Este voluntario no tiene sesión activa.</p>
    <?php endif; ?>
</div>
        </div>
        <?php if ($mostrar_escaner): ?>
            <div class="row gy-2 gx-3 align-items-center">
                <!-- qr logica -->
                <div >
                    <!-- Scanner siempre visible -->
                    <div id="qrScannerContainer" class="mb-3">
                        <p>ESCANE EL ITEM AUTOMATICAMENTE</p>
                        <iframe id="qrScannerFrame" src="./../utils/escaner_qr.php" style="width: 100%; height: 400px; border: none; border-radius: 8px;"></iframe>
                        <?php if ($nombre_voluntario): ?>
                            <span style="color:#5C6872; font-weight:bold;">Voluntario: <?php echo htmlspecialchars($nombre_voluntario); ?></span>
                        <?php endif; ?>   
                        <button id="closeQRScanner" class="btn btn-primary ml-10">CERRAR ESCANER</button> 
                    </div>

                 
                    

                    <!-- Contenedor para el resultado del QR -->
                    <div id="qrContentDisplay" class="mt-2">
                        <?php if (!empty($_SESSION['qr_content'])): ?>
                            <p>Código escaneado: <span id="qrContentValue"><?php echo htmlspecialchars($_SESSION['qr_content']); ?></span></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const qrScannerFrame = document.getElementById('qrScannerFrame');
            const qrContentDisplay = document.getElementById('qrContentDisplay');
            const qrScannerContainer = document.getElementById('qrScannerContainer');
            const closeQRScannerBtn = document.getElementById('closeQRScanner');

            closeQRScannerBtn.addEventListener('click', function() {
                qrScannerContainer.style.display = 'none';
            });

            qrScannerFrame.onload = function() {
                if (qrScannerFrame.contentWindow.setQRCallback) {
                    qrScannerFrame.contentWindow.setQRCallback(handleQRScan);
                }
            };

            function handleQRScan(qrData) {
                // Update the interface immediately
                updateQRDisplay(qrData);
                
                // Send data to the server
                saveQRToServer(qrData);
            }

            function updateQRDisplay(qrData) {
                qrContentDisplay.innerHTML = `
                    <p>Scanned code: <span id="qrContentValue">${qrData}</span></p>
                `;
                
                // Visual update effect 
                qrContentDisplay.style.backgroundColor = '#e8f5e9';
                setTimeout(() => {
                    qrContentDisplay.style.backgroundColor = 'transparent';
                }, 500);
            }

            function saveQRToServer(qrData) {
                fetch(`gestionbodega.php?save_qr=1&content=${encodeURIComponent(qrData)}`, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache'
            }
            })
            .then(response => response.text())
            .then(data => {
                setTimeout(() => {
                    window.location.href = 'https://192.168.0.120/src/pages/gestionbodega.php';
                }, 2000); // Recargar después de 2 segundos (2000 ms)
            })
            .catch(error => console.error('Error:', error));
            }
        });

        // Botón de reinicio SOLO borra datos
        reiniciarTodo.addEventListener('click', function() {
            // Redirigir con parámetro de reset para borrar todo
            window.location.href = 'https://192.168.0.120/src/pages/gestionbodega.php?reset=true';
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Eliminar parámetros de la URL si existen
            if (window.history.replaceState) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        });
    </script>
    <script src="js/bootstrap.bundle.min.js"></script>
<?php require './../layout/footer.htm'; ?> 
</body>
</html>