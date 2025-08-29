<?php
/**
 * Gestion de Voluntarios desde Bodega
 * Panel para el personal de bodega para gestionar las herramientas asignadas a cvoluntarios específicos
 */

require './../../layout/head.html';
require './../../layout/header.php';
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

// Verificar permisos de bodega
// Inicialización de sesión
$_SESSION['qr_content'] = $_SESSION['qr_content'] ?? ''; // Contenido del QR escaneado
if (isset($_POST['verificar_id'])) {
    $_SESSION['id_voluntario'] = $_POST['id'];
}

$voluntario_id = $_GET['voluntario_id'] ?? '';
$nombre_voluntario = '';

// Obtener nombre del voluntario
if (!empty($voluntario_id)) {
    try {
        $stmt = $conn->prepare("SELECT nome FROM user WHERE voluntario = ?");
        $stmt->execute([$voluntario_id]);
        $nombre_voluntario = $stmt->fetchColumn();
    } catch(PDOException $e) {
        die("Error al obtener datos del voluntario: " . $e->getMessage());
    }
}

// Obtener todas las solicitudes del voluntario
$todas_solicitudes = [];
if (!empty($voluntario_id)) {
    try {
        $stmt = $conn->prepare("SELECT sh.*, 
                               u.nome as nombre_voluntario,
                               DATE_FORMAT(sh.fecha_solicitud, '%d/%m %H:%i') as fecha_solicitud_formateada, 
                               DATE_FORMAT(sh.fecha_entregado, '%d/%m %H:%i') as fecha_entregado_formateada
                               FROM solicitudes_herramientas sh
                               LEFT JOIN user u ON sh.voluntarioid = u.voluntario
                               WHERE sh.voluntarioid = ? 
                               ORDER BY sh.fecha_solicitud DESC");
        $stmt->execute([$voluntario_id]);
        $todas_solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error al obtener solicitudes: " . $e->getMessage());
    }
}
?>

<main class="container mt-4">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4>Gestión de Voluntario</h4>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <label for="voluntario_id" class="form-label">ID del Voluntario</label>
                    <input type="text" class="form-control" id="voluntario_id" name="voluntario_id" 
                           value="<?php echo htmlspecialchars($voluntario_id); ?>" required>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Consultar</button>
                </div>
            </form>
            
            <?php if (!empty($nombre_voluntario)): ?>
                <div class="mt-3">
                    <h5>Voluntario: <?php echo htmlspecialchars($nombre_voluntario); ?></h5>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($voluntario_id)): ?>
        <!-- Formulario oculto para manejar cambios -->
        <form id="form-estado" method="post" style="display: none;">
            <input type="hidden" name="item_id" id="input-item-id">
            <input type="hidden" name="accion" id="input-accion">
        </form>

        <!-- Lista de entrega de herramientas -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4>Lista de entrega de herramientas</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla-prestados" class="table table-borderless align-middle">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="text-align:center;">Cantidad</th>
                                <th style="text-align:center;">Fecha</th>
                                <th style="text-align:center;">Estado</th>
                                <th style="text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todas_solicitudes as $item): ?>
                                <?php if ($item['estado_entrega'] === 'Entregado' && 
                                         (empty($item['estado_devolucion']) || 
                                          !in_array($item['estado_devolucion'], ['No devuelto', 'Perdido']))): ?>
                                    
                                    <tr id="prestado-<?php echo $item['idsolicitud']; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="form-check me-2">
                                                    <input type="checkbox" 
                                                           class="form-check-input visto-item-prestado" 
                                                           id="visto-prestado-<?php echo $item['idsolicitud']; ?>"
                                                           data-id="<?php echo $item['idsolicitud']; ?>"
                                                           checked
                                                           onchange="cambiarEstado(this, false)">
                                                </div>
                                                <label for="visto-prestado-<?php echo $item['idsolicitud']; ?>" class="form-check-label mb-0">
                                                    <?php echo htmlspecialchars($item['nombreitem']); ?>
                                                </label>
                                            </div>
                                        </td>
                                        <td style="text-align:center;"><?php echo htmlspecialchars($item['cantidad']); ?></td>
                                        <td style="text-align:center;"><?php echo !empty($item['fecha_entregado']) ? htmlspecialchars($item['fecha_entregado_formateada']) : htmlspecialchars($item['fecha_solicitud_formateada']); ?></td>
                                        <td style="text-align:center;" class="<?php 
                                            echo ($item['estado_devolucion'] === 'Devuelto') ? 'text-success' : 
                                                 (($item['estado_devolucion'] === 'No devuelto' || $item['estado_devolucion'] === 'Perdido') ? 'text-danger' : 'text-warning');
                                        ?> estado-devolucion no-tachar">
                                            <?php echo htmlspecialchars($item['estado_devolucion'] ?? 'Entregado'); ?>
                                        </td>
                                        <td style="text-align:center; white-space: nowrap;">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <button class="btn btn-sm btn-warning btn-editar-prestado" 
                                                        data-id="<?php echo $item['idsolicitud']; ?>"
                                                        title="Editar solicitud">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if (!empty($item['observaciones'])): ?>
                                                    <button class="btn btn-sm btn-info btn-ver-observaciones" 
                                                            data-id="<?php echo $item['idsolicitud']; ?>"
                                                            data-observaciones="<?php echo htmlspecialchars($item['observaciones']); ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-secondary btn-tachar-prestado" 
                                                        data-id="<?php echo $item['idsolicitud']; ?>"
                                                        data-tipo="prestado">
                                                    <i class="bi bi-check2-square"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-cancelar-prestado" 
                                                        data-id="<?php echo $item['idsolicitud']; ?>">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-end mt-3">
                    <button id="btn-entrega-total" class="btn btn-success me-2">
                        <i class="bi bi-check-all"></i> Entrega Total
                    </button>
                    <button id="btn-entrega-aprobado" class="btn btn-danger">
                        <i class="bi bi-check-circle"></i> Entrega Aprobado
                    </button>
                </div>
            </div>
        </div>

        <!-- Herramientas no entregadas -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Herramientas No Entregadas</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabla-no-entregadas" class="table table-bordered">
                        <thead class="">
                            <tr>
                                <th style="text-align: center;">Fecha</th>
                                <th>Herramienta no entregada</th>
                                <th style="text-align: center;">¿A quién se entregó?</th>
                                <th style="text-align: center;">Devuelto</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todas_solicitudes as $item): ?>
                                <?php if (!empty($item['estado_devolucion']) && in_array($item['estado_devolucion'], ['No devuelto', 'Perdido'])): ?>
                                    <tr id="no-entregado-<?php echo $item['idsolicitud']; ?>">
                                        <td style="text-align: center;">
                                            <?php echo !empty($item['fecha_entregado']) ? htmlspecialchars($item['fecha_entregado_formateada']) : htmlspecialchars($item['fecha_solicitud_formateada']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['nombreitem']); ?></td>
                                        <td style="text-align: center;"><?php echo htmlspecialchars($item['nombre_voluntario'] ?? $item['voluntarioid']); ?></td>
                                        <td style="text-align: center;" class="<?php 
                                            echo $item['estado_devolucion'] === 'Perdido' ? 'text-danger' : 'text-warning';
                                        ?>">
                                            <?php echo htmlspecialchars($item['estado_devolucion']); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <button class="btn btn-sm btn-info btn-ver-detalles" 
                                                    data-id="<?php echo $item['idsolicitud']; ?>"
                                                    title="Ver detalles">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning btn-editar-devolucion" 
                                                    data-id="<?php echo $item['idsolicitud']; ?>"
                                                    title="Cambiar estado">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Función para mover item a prestados
    function marcarComoPrestado(id) {
        $.ajax({
            url: 'marcar_prestado.php',
            type: 'POST',
            data: { id: id, estado: 'Entregado' },
            success: function(response) {
                // Obtener la fila completa
                var fila = $('#row-'+id).clone();
                fila.attr('id', 'row-prestado-'+id);
                
                // Cambiar el checkbox
                fila.find('.visto-item')
                    .removeClass('visto-item')
                    .addClass('visto-item-prestado')
                    .attr('id', 'visto-prestado-'+id)
                    .prop('checked', true);
                
                fila.find('label').attr('for', 'visto-prestado-'+id);
                
                // Cambiar formato de fecha
                var horaCell = fila.find('td:eq(2)');
                var fechaOriginal = horaCell.text().trim();
                var fechaActual = new Date();
                horaCell.text(fechaActual.getDate() + '/' + (fechaActual.getMonth()+1) + ' ' + fechaOriginal);
                
                // Cambiar estado
                fila.find('td:eq(3)').text('Entregado');
                
                // Cambiar botones
                fila.find('.btn-cancelar-solicitud').remove();
                
                // Agregar a tabla de prestados
                $('#tabla-prestados tbody').prepend(fila);
                
                // Eliminar de tabla original
                $('#row-'+id).remove();
                
                // Si no quedan items, mostrar mensaje
                if ($('#tabla-solicitudes tbody tr').length === 0) {
                    $('#tabla-solicitudes tbody').html('<tr><td colspan="5" class="text-muted">No hay solicitudes registradas hoy.</td></tr>');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'No se pudo marcar como prestado', 'error');
                $('#visto-'+id).prop('checked', false);
            }
        });
    }

    // Función para quitar de prestados
    function quitarDePrestados(id) {
        $.ajax({
            url: 'marcar_prestado.php',
            type: 'POST',
            data: { id: id, estado: 'Pendiente' },
            success: function(response) {
                // Obtener la fila completa
                var fila = $('#row-prestado-'+id).clone();
                fila.attr('id', 'row-'+id);
                
                // Cambiar el checkbox
                fila.find('.visto-item-prestado')
                    .removeClass('visto-item-prestado')
                    .addClass('visto-item')
                    .attr('id', 'visto-'+id)
                    .prop('checked', false);
                
                fila.find('label').attr('for', 'visto-'+id);
                
                // Cambiar formato de fecha (solo hora)
                var fechaCell = fila.find('td:eq(2)');
                var fechaTexto = fechaCell.text().trim();
                var soloHora = fechaTexto.split(' ')[1] || '00:00';
                fechaCell.text(soloHora);
                
                // Cambiar estado
                fila.find('td:eq(3)').text('Pendiente');
                
                // Agregar botón de cancelar
                var accionesCell = fila.find('td:eq(4)');
                accionesCell.append('<button class="btn btn-sm btn-danger btn-cancelar-solicitud" data-id="'+id+'"><i class="bi bi-x-circle"></i></button>');
                
                // Agregar a tabla original
                $('#tabla-solicitudes tbody').prepend(fila);
                
                // Eliminar de tabla de prestados
                $('#row-prestado-'+id).remove();
                
                // Si no quedan items, mostrar mensaje
                if ($('#tabla-prestados tbody tr').length === 0) {
                    $('#tabla-prestados tbody').html('<tr><td colspan="5" class="text-muted">No hay items prestados actualmente.</td></tr>');
                }
            },
            error: function(xhr) {
                Swal.fire('Error', 'No se pudo actualizar el estado', 'error');
                $('#visto-prestado-'+id).prop('checked', true);
            }
        });
    }

    // Manejar checkbox de visto en solicitudes
    $('body').on('change', '.visto-item', function() {
        var id = $(this).data('id');
        var isChecked = $(this).is(':checked');
        
        if (isChecked) {
            marcarComoPrestado(id);
        }
    });

    // Manejar checkbox de visto en items prestados
    $('body').on('change', '.visto-item-prestado', function() {
        var id = $(this).data('id');
        var isChecked = $(this).is(':checked');
        
        if (!isChecked) {
            quitarDePrestados(id);
        } else {
            $(this).prop('checked', true); // No permitir desmarcar directamente
        }
    });
    // Inicializar botones de tachado según el estado de las cookies
    $('.btn-tachar-item').each(function() {
        const id = $(this).data('id');
        if (document.cookie.includes('tachado_'+id)) {
            $(this).addClass('active').attr('title', 'Des-tachar');
        }
    });

    // Manejar checkbox de visto
    $('.visto-item').change(function() {
        const id = $(this).data('id');
        const isChecked = $(this).is(':checked');
        
        if (isChecked) {
            document.cookie = `visto_${id}=1; expires=${new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString()}; path=/;`;
        } else {
            document.cookie = `visto_${id}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        }
        
        // Aquí puedes agregar cualquier función adicional que necesites ejecutar
        // cuando el estado del checkbox cambie
        console.log(`Checkbox para item ${id} ${isChecked ? 'marcado' : 'desmarcado'}`);
    });

    // Manejar tachado/des-tachado de items
    $('.btn-tachar-item').click(function() {
        const id = $(this).data('id');
        const row = $('#row-'+id);
        const isTachado = row.hasClass('tachado');
        
        if (isTachado) {
            // Des-tachar
            row.removeClass('tachado');
            $(this).removeClass('active').attr('title', 'Tachar');
            document.cookie = `tachado_${id}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        } else {
            // Tachar
            row.addClass('tachado');
            $(this).addClass('active').attr('title', 'Des-tachar');
            document.cookie = `tachado_${id}=1; expires=${new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString()}; path=/;`;
        }
    });


$('#btn-entrega-total').click(function() {
    Swal.fire({
        title: '¿Marcar todos como devueltos?',
        text: 'Esta acción actualizará el estado de todos los items a "Devuelto" y los tachará',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'marcar_todos_devueltos.php',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // 1. Actualizar visualmente TODAS las filas (tachado + estado)
                        $('#tabla-prestados tbody tr').each(function() {
                            const id = $(this).attr('id').replace('prestado-', '');
                            $(this).addClass('tachado');
                            $(this).find('.estado-devolucion')
                                .text('Devuelto')
                                .removeClass('text-warning text-danger')
                                .addClass('text-success');
                            // Guardar en cookies (opcional)
                            document.cookie = `tachado_prestado_${id}=1; max-age=2592000; path=/`;
                        });

                        // 2. Mostrar confirmación y recargar
                        Swal.fire({
                            icon: 'success',
                            title: '¡Listo!',
                            text: 'Todos los items fueron marcados como devueltos',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // Recargar para sincronizar con la base de datos
                        });
                    } else {
                        Swal.fire('Error', response.error || 'Error al actualizar', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', 'Error de conexión', 'error');
                }
            });
        }
    });
});

    // Al cargar la página, aplicar tachado según cookies
    $('#tabla-prestados tbody tr').each(function() {
        const id = $(this).attr('id').replace('prestado-', '');
        if (document.cookie.includes(`tachado_prestado_${id}`)) {
            $(this).addClass('tachado');
            $(this).find('.btn-tachar-prestado').addClass('active').attr('title', 'Des-tachar');
        }
    });

    // Manejar tachado en tabla de prestados
    $('#tabla-prestados').on('click', '.btn-tachar-prestado', function() {
        const id = $(this).data('id');
        const row = $('#prestado-'+id);
        const isTachado = row.hasClass('tachado');
        const btnTachar = $(this);
        
        if (isTachado) {
            // Si ya está tachado, permitir desmarcar
            $.ajax({
                url: 'actualizar_devolucion.php',
                type: 'POST',
                data: { 
                    id: id,
                    estado: 'Entregado'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        row.removeClass('tachado');
                        btnTachar.removeClass('active').attr('title', 'Tachar');
                        document.cookie = `tachado_prestado_${id}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
                        row.find('.estado-devolucion').text('Entregado').removeClass('text-success').addClass('text-warning');
                    }
                }
            });
        } else {
            // Si no está tachado, marcar como devuelto
            $.ajax({
                url: 'actualizar_devolucion.php',
                type: 'POST',
                data: { 
                    id: id,
                    estado: 'Devuelto'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        row.addClass('tachado');
                        btnTachar.addClass('active').attr('title', 'Des-tachar');
                        document.cookie = `tachado_prestado_${id}=1; expires=${new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString()}; path=/;`;
                        row.find('.estado-devolucion').text('Devuelto').removeClass('text-warning').addClass('text-success');
                    }
                }
            });
        }
    });



    // Manejar cancelación de solicitud
    $('.btn-cancelar-solicitud').click(function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: '¿Cancelar solicitud?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `cancelar_solicitud.php?id=${id}`;
            }
        });
    });

 // Manejar el botón de Entrega Aprobado
$('#btn-entrega-aprobado').click(function() {
    // Verificar si todos los items están tachados
    const todosTachados = $('#tabla-prestados tbody tr').length > 0 && 
                         $('#tabla-prestados tbody tr').toArray().every(tr => $(tr).hasClass('tachado'));
    
    if (!todosTachados) {
        Swal.fire({
            icon: 'error',
            title: 'Verificación requerida',
            html: 'Debes tachar <strong>TODOS</strong> los items como entregados antes de aprobar la entrega.<br><br>' +
                  'Por favor, verifica que todos los items en la lista estén marcados como entregados.',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    Swal.fire({
        title: '¿Confirmar entrega aprobada?',
        text: 'Esta acción eliminará TODOS los items prestados de tu lista. ¿Estás seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, aprobar entrega',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'eliminar_todos_prestados.php',
                type: 'POST',
                data: { confirmar: true },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Entrega aprobada',
                        text: 'Todos los items prestados han sido eliminados',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseText || 'Ocurrió un error al eliminar los items'
                    });
                }
            });
        }
    });
});
// Manejar devolución no entregada en tabla de prestados
$('#tabla-prestados').on('click', '.btn-cancelar-prestado', function() {
    const id = $(this).data('id');
    
    Swal.fire({
        title: '¿Marcar como no devuelto?',
        html: 'Esta acción moverá este item a la lista de <strong>herramientas no entregadas</strong> y lo quitará de todas las demás listas.<br><br>¿Confirmas?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, marcar como no devuelto',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'marcar_no_devuelto.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Item marcado',
                            text: 'El item se ha movido a no entregados',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error || 'No se pudo actualizar el estado'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.error || 'Error al comunicarse con el servidor'
                    });
                }
            });
        }
    });
});
// Manejar edición de items prestados
$('#tabla-prestados').on('click', '.btn-editar-prestado', function() {
    const id = $(this).data('id');
    
    // Obtener los datos actuales de la solicitud
    $.ajax({
        url: 'obtener_datos_solicitud.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Mostrar modal de edición con los datos actuales
                Swal.fire({
                    title: 'Editar Solicitud',
                    html: `
                        <form id="form-editar-solicitud">
                            <input type="hidden" name="idsolicitud" value="${id}">
                            <div class="mb-3">
                                <label class="form-label">Nombre del Item</label>
                                <input type="text" class="form-control" name="nombreitem" value="${response.data.nombreitem}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cantidad</label>
                                <input type="number" class="form-control" name="cantidad" min="1" value="${response.data.cantidad}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones">${response.data.observaciones || ''}</textarea>
                            </div>
                        </form>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar Cambios',
                    cancelButtonText: 'Cancelar',
                    focusConfirm: false,
                    preConfirm: () => {
                        return {
                            nombreitem: $('.swal2-modal input[name="nombreitem"]').val(),
                            cantidad: $('.swal2-modal input[name="cantidad"]').val(),
                            observaciones: $('.swal2-modal textarea[name="observaciones"]').val()
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Enviar los cambios al servidor
                        const formData = $('#form-editar-solicitud').serialize();
                        
                        $.ajax({
                            url: 'actualizar_solicitud.php',
                            type: 'POST',
                            data: formData,
                            success: function(updateResponse) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Solicitud actualizada',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseText || 'Ocurrió un error al actualizar'
                                });
                            }
                        });
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.error || 'No se pudieron obtener los datos de la solicitud'
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: xhr.responseJSON?.error || 'Error al comunicarse con el servidor'
            });
        }
    });
});

// Manejar edición de estado de devolución
$('#tabla-no-entregadas').on('click', '.btn-editar-devolucion', function() {
    const id = $(this).data('id');
    const row = $('#no-entregado-'+id);
    const btn = $(this); // Guardar referencia al botón
    
    Swal.fire({
        title: 'Cambiar estado de devolución',
        text: 'Selecciona el nuevo estado para este item',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Actualizar',
        cancelButtonText: 'Cancelar',
        input: 'select',
        inputOptions: {
            'No devuelto': 'No devuelto',
            'Devuelto': 'Devuelto',
            'Perdido': 'Perdido'
        },
        inputValue: row.find('td:eq(3)').text().trim(), // Mostrar el estado actual
        inputPlaceholder: 'Selecciona un estado',
        inputValidator: (value) => {
            if (!value) {
                return 'Debes seleccionar un estado';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            btn.prop('disabled', true); // Deshabilitar botón durante la solicitud
            
            $.ajax({
                url: 'actualizar_devolucion.php',
                type: 'POST',
                data: {
                    id: id,
                    estado: result.value
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Actualizar visualmente sin recargar
                        const celdaEstado = row.find('td:eq(3)');
                        celdaEstado.text(result.value);
                        
                        // Actualizar clases de color según el nuevo estado
                        celdaEstado.removeClass('text-danger text-warning text-success');
                        
                        if (result.value === 'Devuelto') {
                            celdaEstado.addClass('text-success');
                            // Si cambió a Devuelto, recargamos para que aparezca en las tablas superiores
                            location.reload();
                        } else if (result.value === 'Perdido') {
                            celdaEstado.addClass('text-danger');
                        } else {
                            celdaEstado.addClass('text-warning');
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Estado actualizado',
                            text: 'El estado de devolución ha sido cambiado',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error || 'No se pudo actualizar el estado'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.error || 'Error al comunicarse con el servidor'
                    });
                },
                complete: function() {
                    btn.prop('disabled', false); // Rehabilitar botón al finalizar
                }
            });
        }
    });
});

// Manejar visualización de observaciones
$('body').on('click', '.btn-ver-observaciones', function() {
    const observaciones = $(this).data('observaciones');
    
    Swal.fire({
        title: 'Observaciones',
        html: `<div class="text-start p-3" style="background-color: #f8f9fa; border-radius: 5px;">
                  <p>${observaciones.replace(/\n/g, '<br>')}</p>
               </div>`,
        confirmButtonText: 'Cerrar',
        width: '600px'
    });
});
    // Manejar envío del formulario con AJAX
    $('#form-solicitud').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: $(this).attr('method'),
            data: $(this).serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Solicitud registrada',
                    text: 'El item ha sido añadido a tu solicitud',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseText || 'Ocurrió un error al guardar'
                });
            }
        });
    });
});

// Agregar este código en la sección de scripts de voluntario.php
$('#tabla-no-entregadas').on('click', '.btn-ver-detalles', function() {
    const id = $(this).data('id');
    
    $.ajax({
        url: 'obtener_datos_solicitud.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Formatear fechas para mostrar
                const fechaSolicitud = new Date(response.data.fecha_solicitud).toLocaleString();
                const fechaEntregado = response.data.fecha_entregado ? 
                    new Date(response.data.fecha_entregado).toLocaleString() : 'No entregado';
                const fechaRecibido = response.data.fecha_recibido ? 
                    new Date(response.data.fecha_recibido).toLocaleString() : 'No recibido';
                
                Swal.fire({
                    title: 'Detalles completos de la solicitud',
                    html: `
                        <div class="text-start">
                            <p><strong>Herramienta:</strong> ${response.data.nombreitem}</p>
                            <p><strong>Cantidad:</strong> ${response.data.cantidad}</p>
                            <p><strong>Fecha de solicitud:</strong> ${fechaSolicitud}</p>
                            <p><strong>Fecha de entrega:</strong> ${fechaEntregado}</p>
                            <p><strong>Fecha de recepción:</strong> ${fechaRecibido}</p>
                            <p><strong>Estado de entrega:</strong> ${response.data.estado_entrega || 'No especificado'}</p>
                            <p><strong>Estado de devolución:</strong> ${response.data.estado_devolucion || 'No especificado'}</p>
                            <p><strong>Observaciones:</strong> ${response.data.observaciones || 'Ninguna'}</p>
                        </div>
                    `,
                    confirmButtonText: 'Cerrar',
                    width: '700px'
                });
            } else {
                Swal.fire('Error', response.error || 'No se pudieron obtener los detalles', 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'Error al comunicarse con el servidor', 'error');
        }
    });
});
</script>
<script>
// Función para manejar el cambio de estado
function cambiarEstado(checkbox, esMarcado) {
    const id = checkbox.dataset.id;
    
    $.ajax({
        url: 'actualizar_estados.php',
        type: 'POST',
        data: {
            item_id: id,
            accion: esMarcado ? 'marcar' : 'desmarcar'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload(); // Recargar para ver los cambios
            } else {
                Swal.fire('Error', response.error || 'Error al actualizar estado', 'error');
                checkbox.checked = !checkbox.checked; // Revertir el cambio visual
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'Error de conexión', 'error');
            checkbox.checked = !checkbox.checked; // Revertir el cambio visual
        }
    });
}

</script>
<!-- En el head o antes de los scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php require './../../layout/footer.htm'; ?>