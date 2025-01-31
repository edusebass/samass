<?php
require './../layout/head.html';
require './../layout/header.php';
require './../utils/session_check.php';
require_once './../db/dbconn.php';
require './../utils/ejecutar_query.php';

function minutosAHoras($minutos) {
    $horas = $minutos / 60;
    return round($horas, 1) . ' horas';
}

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
                <td><a href='ficha-material.php?codigo={$codigo_sin_prefijo}'>".$codigo_sin_prefijo."</a></td>
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
    <title>SAM assistant</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
</head>
<body>
<div class="container-fluid mt-3">
    <header> <!-- Encabezado y botones -->
        <div class="w-100 mb-2 p-1 bg-plomo h5">INVENTARIO <b>MATERIALES</b></div>
        <div class="w-100 mb-2 p-1 bg-plomo btn-group d-block " role="group" aria-label="Primer grupo">
            <button type="button" class="btn btn-outline-primary m-1" >
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
           
            <div class="btn-group d-block d-lg-flex me-2 p-2" role="group" aria-label="Segundo grupo">
            <button type="button" class="btn btn-outline-primary m-1" onclick="filterByArea(null)">
                <img  src="/public//ico/general.svg" class="button-icon" alt="General">
                <span class="d-none d-md-inline">General</span>
                <small class="d-none d-sm-inline d-md-none align-middle">General</small>
            </button>
            <?php foreach ($areas as $area): ?>
                <button type="button" class="btn btn-outline-primary m-1" onclick="filterByArea(<?php echo $area['area_id']; ?>)">
                    <img src="/public/<?php echo $area['icon']; ?>" class="button-icon" alt="<?php echo $area['name']; ?>" style="<?php echo isset($area['style']) ? $area['style'] : ''; ?>">
                    <span class="d-none d-md-inline"><?php echo $area['name']; ?></span>
                    <small class="d-none d-sm-inline d-md-none align-middle"><?php echo $area['name']; ?></small>
                </button>
            <?php endforeach; ?>
             
            <button type="button" class="btn btn-outline-primary m-1">
                <img  src="/public/ico/nuevo.svg"  class="button-icon" alt="Nuevo Item">
                <span class="d-none d-md-inline"><a href="./nuevoitem.php">Nuevo</a></span>
                <small class="d-none d-sm-inline d-md-none align-middle"><a href="./nuevoitem.php">Nuevo Item</a></small>
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
</div>
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
                paging: true
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
    </script>
    <?php require './../layout/footer.htm';?>
</body>
</html>