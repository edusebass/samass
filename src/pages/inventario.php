<?php
require './../layout/head.html';
require './../layout/header.php';
require './../utils/session_check.php';
require_once './../db/dbconn.php';

/**
 * Devuelve los datos de una tabla como array asociativo.
 */
function obtener_datos_tabla($conn, $tabla, $campos) {
    $query = "SELECT $campos FROM $tabla";
    $stmt = $conn->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Definición de las tablas y sus columnas a mostrar
$tablas = [
    'equipo_seguridad' => [
        'label' => 'Equipo de Seguridad',
        'campos' => 'id, descripcion, unidad_medida, cantidad, costo_unitario_estimado, marca, modelo, estado_actual, anio_adquisicion, vida_util_sugerida, fotografia_url, observaciones'
    ],
    'habitacion_huesped_betel' => [
        'label' => 'Habitación Huésped Betel',
        'campos' => 'id, descripcion, unidad_medida, cantidad, costo_unitario_estimado, marca, modelo, numero_serie, anio_adquisicion, vida_util_sugerida, fotografia_url, observaciones'
    ],
    'herramientas_equipo_jardineria' => [
        'label' => 'Herr. y Equipo Jardinería',
        'campos' => 'id, descripcion, cantidad, costo_unitario_estimado, marca, modelo, numero_serie, estado_actual, anio_adquisicion, vida_util_sugerida, fotografia_url, observaciones'
    ],
    'herramientas_manuales' => [
        'label' => 'Herramientas Manuales',
        'campos' => 'id, descripcion, cantidad, costo_unitario_estimado, marca, estado_actual, anio_adquisicion, vida_util_sugerida, fotografia_url, observaciones'
    ],
    'maquinas' => [
        'label' => 'Máquinas',
        'campos' => 'id, descripcion, unidad_medida, cantidad, costo_unitario_estimado, marca, modelo, numero_serie, estado_actual, reparado, costo_reparacion, anio_adquisicion, vida_util_anios, garantia_fabricante, fotografia_url, observaciones'
    ],
    'items_generales_por_edificio' => [
        'label' => 'Items generales por edificio',
        'campos' => 'id, nombre_elemento, cantidad, costo_unitario_estimado, marca, modelo, numero_serie, detalles_adicionales, estado_actual, lugar_almacenamiento, anio_adquisicion, vida_util_sugerida, tiempo_uso, costo_mantenimiento_mensual, observaciones_bas, observaciones_secretaria_om'
    ]
];

// Determina la tabla activa (por defecto la primera)
$tabla_activa = isset($_GET['tabla']) && array_key_exists($_GET['tabla'], $tablas)
    ? $_GET['tabla']
    : array_key_first($tablas);

// Obtiene los datos de la tabla activa
$datos = obtener_datos_tabla($conn, $tabla_activa, $tablas[$tabla_activa]['campos']);

// Obtiene los nombres de las columnas para la cabecera
$columnas = array_keys($datos[0] ?? []);

?>

<main class="container-fluid mt-3">
    <?php require_once './../utils/breadcrumbs.php';
    $breadcrumbs = [
        ['label' => 'Inicio', 'url' => '/inicio.php'],
        ['label' => 'Inventario', 'url' => null]
    ];
    render_breadcrumbs($breadcrumbs, '/');
    ?>

    <header>
        <div class="w-100 mb-2 p-1 bg-plomo h5">INVENTARIO <b><?php echo strtoupper($tablas[$tabla_activa]['label']); ?></b></div>
        <!-- Navegación entre tablas -->
        <ul class="nav nav-tabs mb-3" id="inventarioTabs" role="tablist">
            <?php foreach ($tablas as $key => $info): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $key === $tabla_activa ? 'active' : ''; ?>"
                       href="?tabla=<?php echo $key; ?>"
                       role="tab">
                        <?php echo $info['label']; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </header>

    <!-- Botón para crear nuevo registro -->
    <a href="form_item.php?tabla=<?php echo $tabla_activa; ?>" class="btn btn-dark text-white mb-2">
        Nuevo registro
    </a>

    <!-- Tabla de inventario -->
    <div class="table-responsive">
        <table class="table w-100 roundedTable table-bordered rounded-corners" id="tabla-inventario">
            <thead>
                <tr>
                    <?php foreach ($columnas as $col): ?>
                        <th><?php echo ucwords(str_replace('_', ' ', $col)); ?></th>
                    <?php endforeach; ?>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datos as $fila): ?>
                    <tr>
                        <?php foreach ($columnas as $col): ?>
                            <?php if (strpos($col, 'fotografia') !== false && !empty($fila[$col])): ?>
                                <td>
                                    <img src="<?php echo htmlspecialchars($fila[$col]); ?>" alt="Foto" style="max-width:60px;max-height:60px;">
                                </td>
                            <?php else: ?>
                                <td><?php echo htmlspecialchars($fila[$col]); ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <td>
                            <!-- Botón Editar -->
                            <a href="form_item.php?tabla=<?php echo $tabla_activa; ?>&id=<?php echo urlencode($fila['id']); ?>" class="btn btn-sm btn-warning" title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <!-- Botón Eliminar -->
                            <a href="eliminar_item.php?tabla=<?php echo $tabla_activa; ?>&id=<?php echo urlencode($fila['id']); ?>"
                               class="btn btn-sm btn-danger"
                               title="Eliminar"
                               onclick="return confirm('¿Seguro que deseas eliminar este registro?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<div class="container-fluid m-3 pl-4">
    <form action="./../utils/export_excel.php" method="post">
        <button type="submit" class="btn btn-primary">Exportar tabla a Excel</button>
    </form>
</div>

<!-- JS y Footer -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tabla-inventario').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es_es.json"
            },
            responsive: true,
            searching: true,
            paging: true,
            "dom": '<"top"lf>rt<"bottom"ip><"clear">'
        });
    });
</script>
<?php require './../layout/footer.htm'; ?>
</body>
</html>