<?php

require './../../layout/head.html';
require './../../layout/header.php';
require './../../utils/session_check.php';

$voluntario_id = $_SESSION['user_id'] ?? '';
if (empty($voluntario_id)) {
    die('No se ha identificado al voluntario');
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'])) {
    require_once './../../db/dbconn.php';
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

require_once './../../db/dbconn.php';
$todas_solicitudes = [];

try {
    // Obtenemos TODAS las solicitudes sin filtrar por estado
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
                             <?php if (empty($solicitud['estado_devolucion']) || !in_array($solicitud['estado_devolucion'], ['No devuelto', 'Perdido'])): ?>
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
                          <td style="text-align:center; white-space: nowrap;">
    <div class="d-flex gap-1 justify-content-center">
        <?php if (!empty($solicitud['observaciones'])): ?>
            <button class="btn btn-sm btn-info btn-ver-observaciones" 
                    data-id="<?php echo $solicitud['idsolicitud']; ?>"
                    data-observaciones="<?php echo htmlspecialchars($solicitud['observaciones']); ?>">
                <i class="bi bi-eye"></i>
            </button>
        <?php endif; ?>
        <button class="btn btn-sm btn-outline-secondary btn-tachar-item" 
                data-id="<?php echo $solicitud['idsolicitud']; ?>">
            <i class="bi bi-check2-square"></i>
        </button>
        <button class="btn btn-sm btn-danger btn-cancelar-solicitud" 
                data-id="<?php echo $solicitud['idsolicitud']; ?>">
            <i class="bi bi-trash"></i>
        </button>
    </div>
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
    <h4>Lista de entrega de herramientas</h4>

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


<!-- Columna 3: Herramientas no entregadas - Versión simplificada -->
<div class="col-md-12 mt-4">
    <h4>Herramientas No Entregadas</h4>
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
</main>

<style>
     /* ===== ESTILOS GENERALES DE LA TABLA ===== */
    #tabla-no-entregadas {
        table-layout: fixed;
        width: 100%;
        border-collapse: collapse; /* Bordes unificados */
        margin-bottom: 1rem;
        background-color: white; /* Fondo general */
    }

    /* ===== CABECERA (TH) ===== */
    #tabla-no-entregadas thead th {
        background-color: white !important; /* Fondo blanco */
        border: 1px solidrgb(224, 183, 47) !important; /* Borde gris claro (mismo que las celdas) */
        padding: 10px 12px;
        text-align: center;
        font-weight: 600; /* Negrita para títulos */
        color: #495057; /* Color de texto oscuro */
        white-space: nowrap; /* Evita saltos de línea */
        overflow: hidden;
        text-overflow: ellipsis; /* Puntos suspensivos si el texto es largo */
    }

    /* ===== CELDAS (TD) ===== */
    #tabla-no-entregadas td {
        border: 1px solidrgb(204, 134, 43); /* Mismo borde que los títulos */
        padding: 8px 12px;
        word-wrap: break-word; /* Ajusta texto largo */
        vertical-align: middle;
        background-color: white; /* Fondo blanco */
    }

    /* ===== AJUSTES DE COLUMNAS ===== */
    #tabla-no-entregadas th:nth-child(1), 
    #tabla-no-entregadas td:nth-child(1) {
        width: 15%; /* Fecha */
    }

    #tabla-no-entregadas th:nth-child(2), 
    #tabla-no-entregadas td:nth-child(2) {
        width: 25%; /* Herramienta */
    }

    #tabla-no-entregadas th:nth-child(3), 
    #tabla-no-entregadas td:nth-child(3) {
        width: 30%; /* ¿A quién se entregó? */
        min-width: 180px; /* Ancho mínimo garantizado */
    }

    #tabla-no-entregadas th:nth-child(4), 
    #tabla-no-entregadas td:nth-child(4) {
        width: 15%; /* Estado */
    }

    #tabla-no-entregadas th:nth-child(5), 
    #tabla-no-entregadas td:nth-child(5) {
        width: 15%; /* Acciones */
    }

    /* ===== COLORES DE ESTADO ===== */
    #tabla-no-entregadas .text-danger {
        color: #dc3545 !important; /* Rojo para "Perdido" */
    }
    #tabla-no-entregadas .text-warning {
        color: #ffc107 !important; /* Amarillo para "No devuelto" */
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        #tabla-no-entregadas th:nth-child(3), 
        #tabla-no-entregadas td:nth-child(3) {
            min-width: 150px; /* Ajuste para móviles */
        }
    }

    /* ===== BOTONES ===== */
    #tabla-no-entregadas .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    /* Tachado solo para celdas específicas, excluyendo .no-tachar */
    .tachado > td:not(.no-tachar),
    .tachado > td:not(.no-tachar) > div,
    .tachado > td:not(.no-tachar) > div > label {
        text-decoration: line-through;
        color: #6c757d;
    }
    
    /* Estado - nunca se tacha */
    .no-tachar,
    .no-tachar * {
        text-decoration: none !important;
        color: inherit !important;
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