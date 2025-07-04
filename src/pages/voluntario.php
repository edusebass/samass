<?php
require './../layout/head.html';
require './../layout/header.php';
require './../utils/session_check.php';

$voluntario_id = $_SESSION['user_id'] ?? '';
if (empty($voluntario_id)) {
    die('No se ha identificado al voluntario');
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    require_once './../db/dbconn.php';
    try {
        $item_id = $_POST['item_id'];
        $nuevo_estado = $_POST['accion'] === 'marcar' ? 'Entregado' : 'Pendiente';
        
        $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                              SET estado_entrega = ? 
                              WHERE idsolicitud = ? 
                              AND voluntarioid = ?");
        $stmt->execute([$nuevo_estado, $item_id, $voluntario_id]);
        
        header("Location: voluntario.php");
        exit();
    } catch(PDOException $e) {
        die("Error al actualizar estado: " . $e->getMessage());
    }
}

require_once './../db/dbconn.php';
$todas_solicitudes = [];

try {
    // Obtenemos TODAS las solicitudes sin filtrar por estado
$stmt = $conn->prepare("SELECT *, 
                       DATE_FORMAT(fecha_solicitud, '%d/%m %H:%i') as fecha_solicitud_formateada, 
                       DATE_FORMAT(fecha_entregado, '%d/%m %H:%i') as fecha_entregado_formateada
                       FROM solicitudes_herramientas 
                       WHERE voluntarioid = ? 
                       ORDER BY fecha_solicitud DESC");
    $stmt->execute([$voluntario_id]);
    $todas_solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}
?>

<main class="container mt-4">
   
<!-- Formulario oculto para manejar cambios -->
<form id="form-estado" method="post" style="display: none;">
    <input type="hidden" name="item_id" id="input-item-id">
    <input type="hidden" name="accion" id="input-accion">
</form>

<div class="row mt-4">
   <!-- Columna 1: Todas las solicitudes (mostrando estado actual) -->
<div class="col-md-6">
    <h4>Solicitud de Herramientas</h4>

    <div class="card p-3 rounded-4 w-100 d-block" style="border: solid 2px #E4640D;">
        <?php if (!empty($todas_solicitudes)): ?>
            <table class="table table-borderless align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align:center;">Cantidad</th>
                        <th style="text-align:center;">Fecha/Hora</th>
                        <th style="text-align:center;">Estado</th>
                        <th style="text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todas_solicitudes as $solicitud): ?>
                         <?php if (empty($solicitud['estado_devolucion']) || $solicitud['estado_devolucion'] !== 'No devuelto'): ?>
                        <tr id="row-<?php echo $solicitud['idsolicitud']; ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="form-check me-2">
                                        <input type="checkbox" 
                                               class="form-check-input visto-item" 
                                               id="visto-<?php echo $solicitud['idsolicitud']; ?>"
                                               data-id="<?php echo $solicitud['idsolicitud']; ?>"
                                               <?php echo $solicitud['estado_entrega'] === 'Entregado' ? 'checked' : ''; ?>
                                               onchange="cambiarEstado(this, <?php echo $solicitud['estado_entrega'] === 'Entregado' ? 'false' : 'true'; ?>)">
                                    </div>
                                    <label for="visto-<?php echo $solicitud['idsolicitud']; ?>" class="form-check-label mb-0">
                                        <?php echo htmlspecialchars($solicitud['nombreitem']); ?>
                                    </label>
                                </div>
                            </td>
                            <td style="text-align:center;"><?php echo htmlspecialchars($solicitud['cantidad']); ?></td>
                            <td style="text-align:center;"><?php echo date('d/m H:i', strtotime($solicitud['fecha_solicitud'])); ?></td>
                            <td style="text-align:center;"><?php echo htmlspecialchars($solicitud['estado_entrega'] ?? 'Pendiente'); ?></td>
                            <td style="text-align:center;">
                                <button class="btn btn-sm btn-outline-secondary btn-tachar-item" 
                                        data-id="<?php echo $solicitud['idsolicitud']; ?>">
                                    <i class="bi bi-check2-square"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-cancelar-solicitud" 
                                        data-id="<?php echo $solicitud['idsolicitud']; ?>">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </td>
                        </tr>
                            <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-muted">No hay solicitudes registradas.</p>
        <?php endif; ?>
    </div>
</div>

    
<!-- Columna 2: Mismos items pero mostrando solo los marcados como Entregado -->
<div class="col-md-6">
    <h4>Items Prestados</h4>

    <div class="card p-3 rounded-4 w-100 d-block" style="border: solid 2px #E4640D;">
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
                    <?php if ($item['estado_entrega'] === 'Entregado' && (empty($item['estado_devolucion']) || $item['estado_devolucion'] !== 'No devuelto')): ?>
                        
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
                            <td style="text-align:center;">Entregado</td>
                            <td style="text-align:center;">
                                <button class="btn btn-sm btn-outline-secondary btn-tachar-prestado" 
                                        data-id="<?php echo $item['idsolicitud']; ?>"
                                        data-tipo="prestado">
                                    <i class="bi bi-check2-square"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-cancelar-prestado" 
                                        data-id="<?php echo $item['idsolicitud']; ?>">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

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

<div class="row mt-4">
    <div class="col-12">
        <h3>NUEVA SOLICITUD</h3>
        <div class="card p-3 rounded-4 w-100 d-block" style="border: solid 2px #E4640D;">
            <form id="form-solicitud" action="guardar_solicitud.php" method="post">
                <input type="hidden" name="voluntarioid" value="<?php echo htmlspecialchars($voluntario_id); ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombreitem" class="form-label">Nombre del Item</label>
                            <input type="text" class="form-control" id="nombreitem" name="nombreitem" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="cantidad" name="cantidad" min="1" value="1" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <input type="text" class="form-control" id="observaciones" name="observaciones">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Añadir Solicitud
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Columna 3: Herramientas no entregadas -->
<div class="col-md-6 mt-4">
    <h4>Herramientas No Entregadas</h4>
    <div class="card p-3 rounded-4 w-100 d-block" style="border: solid 2px #dc3545;">
        <table id="tabla-no-entregadas" class="table table-borderless align-middle">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align:center;">Cantidad</th>
                    <th style="text-align:center;">Fecha Préstamo</th>
                    <th style="text-align:center;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($todas_solicitudes as $item): ?>
                    <?php if (!empty($item['estado_devolucion']) && $item['estado_devolucion'] === 'No devuelto'): ?>
                        <tr id="no-entregado-<?php echo $item['idsolicitud']; ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <label class="form-check-label mb-0">
                                        <?php echo htmlspecialchars($item['nombreitem']); ?>
                                    </label>
                                </div>
                            </td>
                            <td style="text-align:center;"><?php echo htmlspecialchars($item['cantidad']); ?></td>
                            <td style="text-align:center;">
                                <?php echo !empty($item['fecha_entregado']) ? htmlspecialchars($item['fecha_entregado_formateada']) : htmlspecialchars($item['fecha_solicitud_formateada']); ?>
                            </td>
                            <td style="text-align:center;" class="text-danger">
                                No devuelto
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</main>

<style>
    .tachado {
        text-decoration: line-through;
        color: #6c757d;
        background-color: #f8f9fa;
    }
    
    .btn-tachar-item.active {
        background-color: #28a745;
        color: white;
    }
    
    .form-check-input.visto-item, .form-check-input.visto-item-prestado {
        width: 1.2em;
        height: 1.2em;
        margin-top: 0;
    }
    
    .form-check-input.visto-item:checked, .form-check-input.visto-item-prestado:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    .btn-tachar-item.active, .btn-tachar-prestado.active {
    background-color: #28a745 !important;
    color: white !important;
    border-color: #28a745 !important;
}
</style>

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

    // Manejar el botón de Entrega Total
$('#btn-entrega-total').click(function() {
    Swal.fire({
        title: '¿Marcar todos como entregados?',
        text: 'Esta acción tachará todos los items de la lista',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, tachar todos',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Tachar todos los items en la tabla de prestados
            $('#tabla-prestados tbody tr').each(function() {
                const id = $(this).attr('id').replace('prestado-', '');
                const btnTachar = $(this).find('.btn-tachar-prestado');
                
                if (!btnTachar.hasClass('active')) {
                    // Solo tachar si no está ya tachado
                    $(this).addClass('tachado');
                    btnTachar.addClass('active').attr('title', 'Des-tachar');
                    document.cookie = `tachado_prestado_${id}=1; expires=${new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString()}; path=/;`;
                }
            });
            
            Swal.fire({
                icon: 'success',
                title: 'Todos los items tachados',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
});


    // Manejar tachado en tabla de prestados
$('#tabla-prestados').on('click', '.btn-tachar-prestado', function() {
    const id = $(this).data('id');
    const row = $('#prestado-'+id);
    const isTachado = row.hasClass('tachado');
    
    if (isTachado) {
        row.removeClass('tachado');
        $(this).removeClass('active').attr('title', 'Tachar');
        document.cookie = `tachado_prestado_${id}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
    } else {
        row.addClass('tachado');
        $(this).addClass('active').attr('title', 'Des-tachar');
        document.cookie = `tachado_prestado_${id}=1; expires=${new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString()}; path=/;`;
    }
});

// Manejar cancelación en tabla de prestados
$('#tabla-prestados').on('click', '.btn-cancelar-prestado', function() {
    const id = $(this).data('id');
    
    Swal.fire({
        title: '¿Cancelar préstamo?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `cancelar_prestado.php?id=${id}`;
        }
    });
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
    Swal.fire({
        title: '¿Confirmar entrega aprobada?',
        text: 'Esta acción eliminará TODOS los items prestados de tu lista. ¿Estás seguro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar todos',
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
</script>
<script>
function cambiarEstado(checkbox, esMarcado) {
    document.getElementById('input-item-id').value = checkbox.dataset.id;
    document.getElementById('input-accion').value = esMarcado ? 'marcar' : 'desmarcar';
    document.getElementById('form-estado').submit();
}
</script>

<?php require './../layout/footer.htm'; ?>