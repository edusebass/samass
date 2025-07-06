<?php
/**
 * Inventario - Vista principal de inventario con pestañas por tabla
 *
 * Descripción:
 * Muestra el inventario de diferentes categorías (tablas) en pestañas. Permite crear, editar y eliminar registros,
 * así como exportar a Excel y realizar carga masiva desde un archivo Excel.
 *
 * Funcionalidades:
 * - Navegación por pestañas para cada tabla de inventario.
 * - Visualización de registros con paginación y búsqueda (DataTables).
 * - Botón para crear nuevo registro (redirige a formulario dinámico).
 * - Botones para editar y eliminar cada registro.
 * - Exportación de la tabla activa a Excel.
 * - Carga masiva de datos desde Excel (una hoja por tabla).
 *
 * Variables principales:
 * - $tablas: array de configuración de cada tabla (nombre, campos, label).
 * - $tabla_activa: string, tabla actualmente seleccionada.
 * - $datos: array, registros de la tabla activa.
 * - $columnas: array, nombres de columnas de la tabla activa.
 *
 * Dependencias:
 * - DataTables (JS y CSS)
 * - Bootstrap (JS y CSS)
 * - PhpSpreadsheet (para carga masiva)
 * - Otros archivos: form_item.php, eliminar_item.php, export_excel.php, carga_masiva.php
 *
 * Seguridad:
<?php
/**
 * Inventario - Vista principal de inventario con pestañas por tabla
 *
 * Descripción:
 * Muestra el inventario de diferentes categorías (tablas) en pestañas. Permite crear, editar y eliminar registros,
 * así como exportar a Excel y realizar carga masiva desde un archivo Excel.
 *
 * @author  SAM Assistant Team
 * @version 1.0
 * @since   2025-07-04
 */
require './../../layout/head.html';
require './../../layout/header.php';
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

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
    'maquinas' => [
        'label' => 'Maquinas',
        'campos' => 'id, codigo, descripcion, unidad_medida, cantidad, costo_unitario_estimado, marca, modelo, numero_serie, estado_actual, reparado, costo_reparacion, anio_adquisicion, vida_util_anios, garantia_fabricante, fotografia_url, observaciones'
    ],
    'herramientas_manuales' => [
        'label' => 'Herramientas Manuales',
        'campos' => 'id, codigo, descripcion, cantidad, costo_unitario_estimado, marca, estado_actual, anio_adquisicion, vida_util_sugerida, fotografia_url, observaciones'
    ],
    'herramientas_equipo_jardineria' => [
        'label' => 'Herr. y Equipo Jardineria',
        'campos' => 'id, codigo, descripcion, cantidad, costo_unitario_estimado, marca, modelo, numero_serie, estado_actual, anio_adquisicion, vida_util_sugerida, fotografia_url, observaciones'
    ],
    'equipo_seguridad' => [
        'label' => 'Equipo de Seguridad',
        'campos' => 'id, codigo, descripcion, unidad_medida, cantidad, costo_unitario_estimado, marca, modelo, estado_actual, anio_adquisicion, vida_util_sugerida, fotografia_url, observaciones'
    ],
    'habitacion_huesped_betel' => [
        'label' => 'Habitacion Huesped Betel',
        'campos' => 'id, codigo, descripcion, unidad_medida, cantidad, costo_unitario_estimado, marca, modelo, numero_serie, anio_adquisicion, vida_util_sugerida, fotografia_url, observaciones'
    ],
    'items_generales_por_edificio' => [
        'label' => 'Items generales por edificio',
        'campos' => 'id, codigo, nombre_elemento, cantidad, costo_unitario_estimado, marca, modelo, numero_serie, detalles_adicionales, estado_actual, lugar_almacenamiento, anio_adquisicion, vida_util_sugerida, tiempo_uso, costo_mantenimiento_mensual, observaciones_bas, observaciones_secretaria_om'
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
    <?php require_once './../../utils/breadcrumbs.php';
    $breadcrumbs = [
        ['label' => 'Inicio', 'url' => '/src/pages/dashboard/index.php'],
        ['label' => 'Inventario', 'url' => null]
    ];
    ?>

    <header>
        <div class="w-100 mb-2 p-3 bg-plomo h5 d-flex justify-content-between align-items-center">
            <span>INVENTARIO <b><?php echo strtoupper($tablas[$tabla_activa]['label']); ?></b></span>
            <a href="carga_masiva.php" class="btn btn-success text-white ms-2">
                <i class="bi bi-upload"></i> Carga masiva del inventario total desde Excel
            </a>
        </div>
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
    <div class="d-flex justify-content-between align-items-center mb-2">
        <a href="form_item.php?tabla=<?php echo $tabla_activa; ?>" class="btn btn-dark text-white">
            <i class="bi bi-plus-circle"></i> Nuevo registro de <?php echo strtoupper($tablas[$tabla_activa]['label']); ?>
        </a>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminarTodo">
            <i class="bi bi-trash3"></i> Eliminar TODO el inventario
        </button>
    </div>

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
                            <!-- Botón Ver -->
                            <button type="button" class="btn btn-sm btn-info" 
                                    title="Ver detalles" 
                                    onclick="verDetalles(<?php echo htmlspecialchars(json_encode($fila)); ?>, '<?php echo $tablas[$tabla_activa]['label']; ?>')">
                                <i class="bi bi-eye"></i>
                            </button>
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
    
    <!-- Modal para eliminar todos los registros -->
    <div class="modal fade" id="modalEliminarTodo" tabindex="-1" aria-labelledby="modalEliminarTodoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalEliminarTodoLabel">
                        <i class="bi bi-exclamation-triangle"></i> ¡ADVERTENCIA CRÍTICA!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>¡Esta acción ELIMINARÁ TODO EL INVENTARIO!</strong><br>
                        <strong>Se eliminarán TODOS los registros de TODAS las siguientes tablas:</strong>
                        <ul class="mt-2 mb-0">
                            <li>✗ Máquinas</li>
                            <li>✗ Herramientas Manuales</li>
                            <li>✗ Herramientas y Equipo de Jardinería</li>
                            <li>✗ Equipo de Seguridad</li>
                            <li>✗ Habitación Huésped Betel</li>
                            <li>✗ Items Generales por Edificio</li>
                        </ul>
                        <strong class="text-danger">¡ESTA ACCIÓN NO SE PUEDE DESHACER!</strong>
                    </div>
                    <form id="formEliminarTodo">
                        <div class="mb-3">
                            <label for="passwordConfirmacion" class="form-label">
                                <i class="bi bi-key"></i> Ingrese la contraseña de confirmación:
                            </label>
                            <input type="password" class="form-control" id="passwordConfirmacion" required
                                   placeholder="Contraseña requerida">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmarEliminacion" required>
                                <label class="form-check-label text-danger fw-bold" for="confirmarEliminacion">
                                    Confirmo que deseo eliminar TODO EL INVENTARIO COMPLETO
                                </label>
                            </div>
                        </div>
                        <input type="hidden" id="tablaEliminar" value="TODAS">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" onclick="eliminarTodosRegistros()">
                        <i class="bi bi-trash3"></i> ELIMINAR TODO EL INVENTARIO
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para ver detalles del registro -->
    <div class="modal fade" id="modalVerDetalles" tabindex="-1" aria-labelledby="modalVerDetallesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalVerDetallesLabel">
                        <i class="bi bi-eye"></i> Detalles del Registro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">
                                <i class="bi bi-info-circle"></i> Información General
                            </h6>
                            <div id="detallesGenerales"></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">
                                <i class="bi bi-gear"></i> Información Técnica
                            </h6>
                            <div id="detallesTecnicos"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-warning">
                                <i class="bi bi-camera"></i> Fotografía
                            </h6>
                            <div id="detallesFotografia"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-secondary">
                                <i class="bi bi-chat-square-text"></i> Observaciones
                            </h6>
                            <div id="detallesObservaciones"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-warning" id="btnEditarDesdeModal">
                        <i class="bi bi-pencil-square"></i> Editar Registro
                    </button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- <div class="container-fluid m-3 pl-4">
    <form action="./../../utils/export_excel.php" method="post">
        <button type="submit" class="btn btn-primary">Exportar tabla a Excel</button>
    </form>
</div> -->



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

    // Función para ver detalles del registro
    function verDetalles(fila, tipoTabla) {
        // Limpiar contenido anterior
        document.getElementById('detallesGenerales').innerHTML = '';
        document.getElementById('detallesTecnicos').innerHTML = '';
        document.getElementById('detallesFotografia').innerHTML = '';
        document.getElementById('detallesObservaciones').innerHTML = '';
        
        // Actualizar título del modal
        document.getElementById('modalVerDetallesLabel').innerHTML = 
            `<i class="bi bi-eye"></i> Detalles del Registro - ${tipoTabla}`;

        // Campos generales
        const camposGenerales = ['id', 'codigo', 'descripcion', 'nombre_elemento', 'cantidad', 'unidad_medida', 'costo_unitario_estimado'];
        let htmlGenerales = '<div class="list-group list-group-flush">';
        
        camposGenerales.forEach(campo => {
            if (fila[campo] !== undefined && fila[campo] !== null && fila[campo] !== '') {
                let valor = fila[campo];
                let label = campo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                
                // Formatear valores especiales
                if (campo === 'codigo') {
                    valor = `<span class="badge bg-primary">${valor}</span>`;
                } else if (campo === 'costo_unitario_estimado') {
                    valor = `<span class="text-success fw-bold">$${parseFloat(valor).toFixed(2)}</span>`;
                } else if (campo === 'cantidad') {
                    valor = `<span class="badge bg-info">${valor}</span>`;
                }
                
                htmlGenerales += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>${label}:</strong>
                        <span>${valor}</span>
                    </div>`;
            }
        });
        htmlGenerales += '</div>';
        document.getElementById('detallesGenerales').innerHTML = htmlGenerales;

        // Campos técnicos
        const camposTecnicos = ['marca', 'modelo', 'numero_serie', 'estado_actual', 'anio_adquisicion', 'vida_util_sugerida', 'vida_util_anios', 'garantia_fabricante', 'reparado', 'costo_reparacion'];
        let htmlTecnicos = '<div class="list-group list-group-flush">';
        
        camposTecnicos.forEach(campo => {
            if (fila[campo] !== undefined && fila[campo] !== null && fila[campo] !== '') {
                let valor = fila[campo];
                let label = campo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                
                // Formatear valores especiales
                if (campo === 'reparado') {
                    valor = valor == 1 ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>';
                } else if (campo === 'costo_reparacion') {
                    valor = `<span class="text-danger fw-bold">$${parseFloat(valor).toFixed(2)}</span>`;
                } else if (campo === 'anio_adquisicion') {
                    valor = `<span class="badge bg-warning text-dark">${valor}</span>`;
                }
                
                htmlTecnicos += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>${label}:</strong>
                        <span>${valor}</span>
                    </div>`;
            }
        });
        htmlTecnicos += '</div>';
        document.getElementById('detallesTecnicos').innerHTML = htmlTecnicos;

        // Fotografía
        let htmlFotografia = '<div class="text-center">';
        if (fila.fotografia_url && fila.fotografia_url.trim() !== '') {
            htmlFotografia += `
                <img src="${fila.fotografia_url}" 
                     alt="Fotografía del registro" 
                     class="img-fluid rounded shadow" 
                     style="max-width: 300px; max-height: 200px;">`;
        } else {
            htmlFotografia += `
                <div class="alert alert-light text-center">
                    <i class="bi bi-camera-slash fs-1 text-muted"></i><br>
                    <span class="text-muted">Sin fotografía disponible</span>
                </div>`;
        }
        htmlFotografia += '</div>';
        document.getElementById('detallesFotografia').innerHTML = htmlFotografia;

        // Observaciones
        const camposObservaciones = ['observaciones', 'observaciones_bas', 'observaciones_secretaria_om', 'detalles_adicionales', 'lugar_almacenamiento', 'tiempo_uso'];
        let htmlObservaciones = '';
        
        camposObservaciones.forEach(campo => {
            if (fila[campo] !== undefined && fila[campo] !== null && fila[campo].trim() !== '') {
                let label = campo.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                htmlObservaciones += `
                    <div class="card mb-2">
                        <div class="card-body">
                            <h6 class="card-title text-primary">${label}</h6>
                            <p class="card-text">${fila[campo]}</p>
                        </div>
                    </div>`;
            }
        });
        
        if (htmlObservaciones === '') {
            htmlObservaciones = '<div class="alert alert-light">No hay observaciones registradas.</div>';
        }
        
        document.getElementById('detallesObservaciones').innerHTML = htmlObservaciones;

        // Configurar botón de editar
        document.getElementById('btnEditarDesdeModal').onclick = function() {
            const tabla = '<?php echo $tabla_activa; ?>';
            window.location.href = `form_item.php?tabla=${tabla}&id=${fila.id}`;
        };

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalVerDetalles'));
        modal.show();
    }

    // Función para eliminar todos los registros
    function eliminarTodosRegistros() {
        const password = document.getElementById('passwordConfirmacion').value;
        const tabla = document.getElementById('tablaEliminar').value;
        const confirmar = document.getElementById('confirmarEliminacion').checked;

        // Validar contraseña
        if (password !== '12345678') {
            alert('❌ Contraseña incorrecta. Acceso denegado.');
            return;
        }

        // Validar confirmación
        if (!confirmar) {
            alert('❌ Debe confirmar la eliminación marcando la casilla.');
            return;
        }

        // Confirmación final adicional
        if (!confirm('⚠️ ÚLTIMA CONFIRMACIÓN: ¿Está COMPLETAMENTE SEGURO de que desea eliminar TODO EL INVENTARIO? Esta acción eliminará TODOS los registros de TODAS las tablas y NO se puede deshacer.')) {
            return;
        }

        // Realizar la eliminación
        fetch('eliminar_todos_registros.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tabla: tabla,
                password: password
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje detallado
                let mensaje = data.message;
                if (data.detalles) {
                    mensaje += '\n\nDetalles por tabla:\n';
                    for (let tabla in data.detalles) {
                        mensaje += `• ${tabla}: ${data.detalles[tabla]} registros eliminados\n`;
                    }
                }
                alert('✅ ' + mensaje);
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarTodo'));
                modal.hide();
                
                // Recargar la página
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error al procesar la solicitud.');
        });
    }

    // Limpiar el formulario cuando se cierre el modal
    document.getElementById('modalEliminarTodo').addEventListener('hidden.bs.modal', function () {
        document.getElementById('formEliminarTodo').reset();
    });
</script>
<?php require './../../layout/footer.htm'; ?>
</body>
</html>