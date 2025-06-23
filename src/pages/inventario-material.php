<?php
require './../layout/head.html';
require './../layout/header.php';
require './../utils/session_check.php';
require_once './../db/dbconn.php';
require './../utils/ejecutar_query.php';

function traer_inventario($conn) {
    $rol = $_SESSION['rol'];
    $id_mat_area = isset($_GET['id_mat_area']) ? $_GET['id_mat_area'] : null;
    $area_id_by = isset($_GET['area_id_by']) ? $_GET['area_id_by'] : null;  // Check if area_id_by is set

    // Base query
    $base_query = "SELECT id_materiales, codigo, mat_nombre, mat_descripcion, id_estado, mat_cantidad";

    if ($area_id_by) {
        // Query for grouping by mat_nombreº
        $query = "SELECT mat_nombre, 
                        MAX(mat_descripcion) as mat_descripcion, 
                        id_estado, 
                        SUM(mat_cantidad) as mat_cantidad_total, 
                FROM materiales 
                JOIN estado ON materiales.id_estado = estado.idestado";
    } else {
        // Query without grouping
        $query = $base_query . " FROM materiales JOIN estado ON materiales.id_estado = estado.idestado";
    }

    // Add area filter if applicable
    if ($id_mat_area) {
        if ($area_id_by) {
            $query .= " WHERE id_mat_area = :id_mat_area GROUP BY mat_nombre, id_estado";
        } else {
            $query .= " WHERE id_mat_area = :id_mat_area";
        }
    } elseif ($area_id_by) {
        $query .= " GROUP BY mat_nombre, id_estado";
    }

    $stmt = $conn->prepare($query);

    if ($id_mat_area) {
        $stmt->bindParam(':id_mat_area', $id_mat_area, PDO::PARAM_INT);
    }

    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($area_id_by) {
            $mat_nombre = $row["mat_nombre"];
            $mat_descripcion = $row["mat_descripcion"];
        } else {
            $codigo = $row["codigo"];
            $mat_nombre = $row["mat_nombre"];
            $mat_descripcion = $row["mat_descripcion"];
        }
    
        // Eliminar el prefijo "M_" del código
        $codigo_sin_prefijo = str_replace("M_", "", $codigo);
    
        echo "
            <tr>
                <td>
                    <a href='ficha-material.php?codigo={$codigo_sin_prefijo}'class='d-flex justify-content-between fw-bold'>
                        <em>".$codigo_sin_prefijo."</em>                    
                        <img src='/public/ico/eye.svg' alt='Ver ficha' class='icon-view'>    
                    </a>
                </td>
                <td>".$mat_nombre."</td>
                <td>".$mat_descripcion."</td>
                <td>1</td>
            </tr>
        ";
    }
    
}

$areas = [
    ["name" => "Auditorio", "icon" => "ico/auditorio.svg", "area_id" => 1],
    ["name" => "Vivienda", "icon" => "ico/residencia.svg", "area_id" => 2],
    ["name" => "Bodega/Oficina", "icon" => "ico/bodega.svg", "area_id" => 3],
    ["name" => "Asamblea", "icon" => "ico/comite.png", "area_id" => 4,"style" =>"width:45px; height: auto;"],
    ["name" => "Lavandería/Perrera", "icon" => "ico/lavanderia.png", "area_id" => 5],
];
?>

<main class="container-fluid mt-3">
    <?php require_once './../utils/breadcrumbs.php';

$breadcrumbs = [
['label' => 'Inicio', 'url' => '/inicio.php'],
['label' => 'Inventario', 'url' => '/inventario.php'],
['label' => 'Ficha de ítem', 'url' => null]
];


render_breadcrumbs($breadcrumbs, '/'); 
?>
    <header> <!-- Encabezado y botones -->
        <div class="w-100 mb-2 p-1 bg-plomo h5">INVENTARIO <b>MATERIALES</b></div>
        <div class="w-100 mb-2 p-1 bg-plomo btn-group d-block " role="group" aria-label="Primer grupo">
            <button type="button" class="btn btn-outline-primary m-1 active" data-bs-toggle="button" aria-pressed="true">
                <img  src="/public/ico/material.png" class="button-icon" >
                <span class="d-none d-md-inline"><a href="./inventario-material.php"><b>Materiales</b></a></span>
                <small class=" d-sm-inline d-md-none align-middle"><a href="./inventario.php">Materiales</a></small>
            </button>
            <button type="button" class="btn btn-outline-primary m-1">
                <img  src="/public/ico/herramienta.svg" class="button-icon" >
                <span class="d-none d-md-inline"><a href="./inventario.php">Herramientas</a></span>
                <small class=" d-sm-inline d-md-none align-middle"><a href="./inventario.php">Herramientas</a></small>
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
                <button type="button" class="btn btn-outline-primary m-1" onclick="filterByArea(null)">
                    <img  src="/public//ico/general.svg" class="button-icon" alt="General">
                    <span class="d-none d-md-inline">General</span>
                    <small class="d-inline d-md-none align-middle">General</small>
                </button>
            <?php foreach ($areas as $area): ?>
                <button type="button" class="btn btn-outline-primary m-1" onclick="filterByArea(<?php echo $area['area_id']; ?>)">
                    <img src="/public/<?php echo $area['icon']; ?>" class="button-icon" alt="<?php echo $area['name']; ?>" style="<?php echo isset($area['style']) ? $area['style'] : ''; ?>">
                    <span class="d-none d-md-inline"><?php echo $area['name']; ?></span>
                    <small class="d-inline d-md-none align-middle"><?php echo $area['name']; ?></small>
                </button>
            <?php endforeach; ?>
            </div>
            <!-- Flecha derecha -->
            <button class="btn btn-primary position-absolute end-0 d-sm-none" id="scrollRight" onclick="scrollRight()">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </header>
        <div class="table-responsive">          
            <table 
                id="table"
                class="table w-100 roundedTable table-bordered rounded-corners"
                style="overflow:hidden">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
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
                    "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es_es.json"
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
                url.searchParams.delete('id_mat_area');  
            } else {
                url.searchParams.set('id_mat_area', areaId);  
            }

            url.searchParams.delete('area_id_by');  
            window.location.href = url.toString();   
        }

        function groupByMateriales() {
            let url = new URL(window.location.href);
            url.searchParams.set('area_id_by', 'true');  // Set the area_id_by parameter in the URL
            url.searchParams.delete('id_mat_area');  
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