<?php
require './../layout/head.html';
require './../layout/header.php';
require './../utils/session_check.php';
require_once './../db/dbconn.php';
require './../utils/ejecutar_query.php';
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : null;

function minutosAHoras($minutos) {
    $horas = $minutos / 60;
    return round($horas, 1) . ' horas';
}

function traer_inventario($conn) {
    $rol = $_SESSION['rol'];
    $area_id = isset($_GET['area_id']) ? $_GET['area_id'] : null;
    $group_by = isset($_GET['group_by']) ? $_GET['group_by'] : null;  // Check if group_by is set

    // Base query
    $base_query = "SELECT iditems, codigo, items.nombre, items.descripcion AS item_descripcion, estado_id, estado.descripcion AS estado_descripcion, uso, seccion_id, observaciones, cantidad, grupo_id";

    if ($group_by) {
        // Query for grouping by nombre
        $query = "SELECT items.nombre, 
                        MAX(items.descripcion) as item_descripcion, 
                        estado_id, 
                        estado.descripcion as estado_descripcion, 
                        SUM(cantidad) as cantidad_total, 
                        SUM(uso) as uso_total, 
                        (SELECT observaciones FROM items i WHERE i.nombre = items.nombre LIMIT 1) as observaciones
                FROM items 
                JOIN estado ON items.estado_id = estado.idestado";
    } else {
        // Query without grouping
        $query = $base_query . " FROM items JOIN estado ON items.estado_id = estado.idestado";
    }

    // Add area filter if applicable
    if ($area_id) {
        if ($group_by) {
            $query .= " WHERE area_id = :area_id GROUP BY items.nombre, estado_id, estado.descripcion";
        } else {
            $query .= " WHERE area_id = :area_id";
        }
    } elseif ($group_by) {
        $query .= " GROUP BY items.nombre, estado_id, estado.descripcion";
    }

    $stmt = $conn->prepare($query);

    if ($area_id) {
        $stmt->bindParam(':area_id', $area_id, PDO::PARAM_INT);
    }

    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($group_by) {
            $nombre = $row["nombre"];
            $descripcion = $row["item_descripcion"];
            $cantidad_total = $row["cantidad_total"];
            $uso_total = $row["uso_total"];
            $observaciones = $row["observaciones"];
            $uso_horas = minutosAHoras($uso_total);
        } else {
            $codigo = $row["codigo"];
            $nombre = $row["nombre"];
            $descripcion = $row["item_descripcion"];
            $estado = $row["estado_descripcion"];
            $cantidad = $row["cantidad"];
            $uso = $row["uso"];
            $observaciones = $row["observaciones"];
            $uso_horas = minutosAHoras($uso);
        }
            
        echo "
        <tr>
            ".(!$group_by ? "
                <td>
                    <a href='fichaitem.php?codigo=$codigo' class='d-flex justify-content-between align-items-center w-100 fw-bold'>
                        <em>".$codigo."</em>
                        <img src='/public/ico/eye.svg' alt='Ver ficha' class='icon-view'>    
                    </a>
                </td>" : "")."
                <td>".$nombre."</td>
                <td>".$descripcion."</td>
                ".(!$group_by ? "<td>".$estado."</td>" : "")." <!-- Solo muestra estado si NO está agrupado -->
                <td>".($group_by ? $cantidad_total : $cantidad)."</td>
                <td>".$uso_horas."</td>
                <td>".$observaciones."</td>
            </tr>
        ";
    }
}

$areas = [
    ["name" => "Auditorio", "icon" => "ico/auditorio.svg", "area_id" => 1],
    ["name" => "Vivienda", "icon" => "ico/residencia.svg", "area_id" => 2],
    ["name" => "Bodega/Oficina", "icon" => "ico/bodega.svg", "area_id" => 3],
    ["name" => "Asamblea", "icon" => "ico/comite.png", "area_id" => 4,"style" =>"width:45px; height: auto;"],
    ["name" => "Lavandería/Perrera", "icon" => "ico/lavanderia.png", "area_id" => 5], //aún falta crear en base de datos
];

error_log($group_by);
?> 
<main class="container-fluid mt-3">
    <?php require_once './../utils/breadcrumbs.php';

$breadcrumbs = [
    ['label' => 'Inicio', 'url' => '/src/pages/admin.php'],
['label' => 'Inventario', ],// sin 'url' para marcar la página actual
];

render_breadcrumbs($breadcrumbs, '/'); 
?>
    <nav><!-- Encabezado y botones -->
        <div class="w-100 bg-plomo mb-2 p-1 h5">INVENTARIO <b>HERRAMIENTAS</b></div>
        <div class="w-100 mb-2 p-1 bg-plomo btn-group d-block" role="group" aria-label="Primer grupo">
            <button type="button" class="btn btn-outline-primary m-1" aria-pressed="false" >
                <img  src="/public/ico/material.png" class="button-icon" >
                <span class="d-none d-md-inline"><a href="./inventario-material.php">Materiales</a></span>
                <small class="d-sm-inline d-md-none align-middle"><a href="./inventario-material.php">Materiales</a></small>
            </button>
            <button type="button" class="btn btn-outline-primary m-1 active" data-bs-toggle="button" aria-pressed="true">
                <img  src="/public/ico/herramienta.svg" class="button-icon" >
                <span class="d-none d-md-inline"><a href="./inventario.php"><b>Herramientas</b></a></span>
                <small class="d-sm-inline d-md-none align-middle"><a href="./inventario.php">Herramientas</a></small>
            </button>
        </div>
<!-- Visualizacion de las areas para administracion -->
        <div class="d-flex align-items-center position-relative">
            <!-- Flecha izquierda (oculta por defecto) -->
            <button class="btn btn-primary position-absolute start-0 d-sm-none" id="scrollLeft" onclick="scrollLeft()">
                <i class="bi bi-chevron-left"></i>
            </button>
            <!-- Contenedor del menú con scroll horizontal -->
            <div class="btn-group me-2 p-2 flex-grow-0" id="scrollmenu" role="group" aria-label="Segundo grupo">
                <button type="button" class="btn btn-outline-primary m-1 " aria-pressed="false" onclick="filterByArea(null)">
                    <img  src="/public/ico/general.svg" class="button-icon" alt="General">
                    <span class="d-none d-md-inline">General</span>
                    <small class="d-inline d-md-none align-middle">General</small>
                </button>
                <?php foreach ($areas as $area): ?>
                    <button type="button" class="btn btn-outline-primary m-1" aria-pressed="false" onclick="filterByArea(<?php echo $area['area_id']; ?>)">
                        <img src="/public/<?php echo $area['icon']; ?>" class="button-icon" alt="<?php echo $area['name']; ?>" style="<?php echo isset($area['style']) ? $area['style'] : ''; ?>">
                        <span class="d-none d-md-inline"><?php echo $area['name']; ?></span>
                        <small class="d-inline d-md-none align-middle"><?php echo $area['name']; ?></small>
                    </button>
                <?php endforeach; ?>
                <button type="button" class="btn btn-outline-primary m-1 " aria-pressed="false" onclick="groupByItems()">
                    <img  src="/public/ico/cantidad.svg"  class="button-icon" alt="Cantidad por item" >
                    <span class="d-none d-md-inline">Cantidad por item</span>
                    <small class="d-inline d-md-none align-middle"><a href="#">Cantidad por item</a></small>
                </button> 
            </div>
            <!-- Flecha derecha -->
            <button class="btn btn-primary position-absolute end-0 d-sm-none" id="scrollRight" onclick="scrollRight()">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </nav>
    <div class="table-responsive">          
            <table 
                id="table"
                class="table w-100 roundedTable table-bordered rounded-corners"
                style="overflow:hidden">
                <thead>
                    <tr>
                    <?php if ($group_by): ?>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Uso</th>
                        <th>Observaciones</th>
                    <?php else: ?>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Cantidad</th>
                        <th>Uso</th>
                        <th>Observaciones</th>
                    <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php echo traer_inventario($conn);?>
                </tbody>
            </table>
    </div>
</main>

    <div class="container-fluid m-3 pl-4">
        <form action="./../utils/export_excel.php" method="post">
            <button type="submit" class="btn btn-primary">Exportar a Excel</button>
        </form>
    </div>
    <?php require './../utils/upload.php';?>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
    
    <script>

    $(document).ready(function() {
        $('#table').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es_es.json" // Cargar texto en español
            },
            responsive: true,
            searching: true,
            paging: true,
            "dom": '<"top"lf>rt<"bottom"ip><"clear">' // Ajustar el diseño de los controles
        });
    });
    
    function filterByArea(areaId) {
        let url = new URL(window.location.href);
            
        if (areaId === null) {
            url.searchParams.delete('area_id');  
        } else {
            url.searchParams.set('area_id', areaId);  
        }

        url.searchParams.delete('group_by');  
        window.location.href = url.toString();   
        }

    function groupByItems() {
        let url = new URL(window.location.href);
        url.searchParams.set('group_by', 'true');  // Set the group_by parameter in the URL
        url.searchParams.delete('area_id');  
        window.location.href = url.toString();   // Redirect to the new URL with the grouping applied
    }
document.querySelectorAll('.btn-outline-primary').forEach(button => {
    button.addEventListener('click', function() {
        // Remover "active" y resetear aria-pressed de todos los botones
        document.querySelectorAll('.btn-outline-primary').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });

        // Activar el botón clickeado
        this.classList.add('active');
        this.setAttribute('aria-pressed', 'true');

        // Guardar el ID del botón seleccionado en localStorage
        localStorage.setItem('activeButton', this.getAttribute('onclick')); 
    });
});

// Verificar si hay un botón activo al cargar la página
window.addEventListener('load', function() {
    let activeButton = localStorage.getItem('activeButton');
    if (activeButton) {
        let button = document.querySelector(`button[onclick='${activeButton}']`);
        if (button) {
            button.classList.add('active');
            button.setAttribute('aria-pressed', 'true');
        }
    }
});

const scrollMenu = document.getElementById("scrollmenu");
const arrowLeft = document.getElementById("scrollLeft");
const arrowRight = document.getElementById("scrollRight");

function scrollRight() {
    scrollMenu.scrollBy({ left: 150, behavior: "smooth" });
    updateArrows();
}

function scrollLeft() {
    scrollMenu.scrollBy({ left: -150, behavior: "smooth" });
    updateArrows();
}

function updateArrows() {
    arrowLeft.classList.toggle("d-none", scrollMenu.scrollLeft <= 0);
    arrowRight.classList.toggle("d-none", scrollMenu.scrollWidth - scrollMenu.clientWidth - scrollMenu.scrollLeft <= 1); // Added tolerance for floating point inaccuracies
}

// Detectar scroll manual para actualizar la visibilidad de las flechas
if(scrollMenu) {
    scrollMenu.addEventListener("scroll", updateArrows);

    // **Add these lines to attach click listeners to the scroll buttons:**
    if(arrowLeft) {
        arrowLeft.addEventListener('click', scrollLeft);
    }
    if(arrowRight) {
        arrowRight.addEventListener('click', scrollRight);
    }


    // Initial update of arrows on page load
    updateArrows();
}
    </script>
    <?php require './../layout/footer.htm';?>
</body>
</html>
