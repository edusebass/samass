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
                <td><a href='fichaitem.php?codigo=$codigo'>".$codigo."</a></td>" : "")."
                <td>".$nombre."</td>
                <td>".$descripcion."</td>
                <th>".$estado."</th>
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
    <title>SAM assistant</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
   
</head>
<body>
<div class="container-fluid mt-3">
    <header><!-- Encabezado y botones -->
        <div class="w-100 bg-plomo mb-2 p-1 h5"><b>INVENTARIO</b></div>
        <div class="w-100 mb-2 p-1 bg-plomo btn-group d-block" role="group" aria-label="Primer grupo">
            <button type="button" class="btn btn-outline-primary m-1" >
                <img  src="/public/ico/material.png" class="button-icon" >
                <span class="d-none d-md-inline"><a href="./inventario-material.php">Materiales</a></span>
                <small class="d-sm-inline d-md-none align-middle"><a href="./inventario-material.php">Materiales</a></small>
            </button>
            <button type="button" class="btn btn-outline-primary m-1">
                <img  src="/public/ico/herramienta.svg" class="button-icon" >
                <span class="d-none d-md-inline"><a href="./inventario.php"><b>Herramientas</b></a></span>
                <small class="d-sm-inline d-md-none align-middle"><a href="./inventario.php">Herramientas</a></small>
            </button>
        </div>

       <!-- Visualizacion de las areas para administracion -->
        <div class="btn-group d-block d-lg-flex me-2 p-2" role="group" aria-label="Segundo grupo">
            <button type="button" class="btn btn-outline-primary m-1" onclick="filterByArea(null)">
                <img  src="/public/ico/general.svg" class="button-icon" alt="General">
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
            <button type="button" class="btn btn-outline-primary m-1" onclick="groupByItems()">
                <img  src="/public/ico/cantidad.svg"  class="button-icon" alt="Cantidad por item" >
                <span class="d-none d-md-inline">Cantidad por item</span>
                <small class="d-none d-sm-inline d-md-none align-middle"><a href="#">Cantidad por item</a></small>
            </button>    
            <button type="button" class="btn btn-outline-primary m-1" onclick="window.location.href='./nuevoitem.php'">
                <img  src="/public/ico/nuevo.svg"  class="button-icon" alt="Nuevo Item">
                <span class="d-none d-md-inline">Nuevo</span>
                <small class="d-none d-sm-inline d-md-none align-middle">Nuevo Item</small>
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
    </script>
    <?php require './../layout/footer.htm';?>
</body>
</html>
