<?php
require './../layout/head.html';
?>
    <title>SAM Assistant</title>
    </head>
    <body>
<?php
require './../layout/header.php';
require './../utils/session_check.php';
require_once './../db/dbconn.php';

$productos_asignados = [];

function ejecutar_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

function obtener_productos_asignados($conn, $id_voluntario) {
    $query = "SELECT o.itemid, i.nombre, i.descripcion, o.cantidad, o.fechasalida 
              FROM operaciones o 
              JOIN items i ON o.itemid = i.iditems 
              WHERE o.voluntarioid = ? AND o.fechaentrada IS NULL
              ORDER BY o.fechasalida DESC";
    return ejecutar_query($conn, $query, [$id_voluntario])->fetchAll(PDO::FETCH_ASSOC);
}

$productos_asignados =  obtener_productos_asignados($conn, $_SESSION["user_id"]);

?>   
        <section class="container-fluid">
            <h3>HERRAMIENTAS O MATERIALES ASIGNADOS</h3>
                    <div class="row">
                        <div class="col-12">
                            <div class="card p-3 rounded-4 w-100 d-block" style="border: solid 2px #E4640D;">
                                <div class="table-responsive">
                                    <table class="table table-borderless align-middle">
                                        <thead>
                                            <th>Producto</th>
                                            <th style="text-align:center;">Cantidad</th>
                                            <th style="text-align:center;">Fecha de Salida</th>
                                            <th style="text-align:center;">LImpieza</th>
                                        </thead>
                                        <?php foreach ($productos_asignados as $producto): ?>
                                            <tr>
                                                <td ><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                                <td style="text-align:center;"><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                                                <td style="text-align:center;"><?php echo htmlspecialchars($producto['fechasalida']); ?></td>
                                                <td style="text-align:center;"><input type="checkbox"></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
            </table>
        </section>
    </body>
</html>

<?php require './../layout/footer.htm'; ?>  