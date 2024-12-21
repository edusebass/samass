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
    $area_id = isset($_GET['area_id']) ? $_GET['area_id'] : null;
    $group_by = isset($_GET['group_by']) ? $_GET['group_by'] : null;  // Check if group_by is set

    // Base query
    $base_query = "SELECT iditems, codigo, nombre, descripccion, estado_id, estado.descripcion, uso, seccion_id, observaciones, cantidad, grupo_id";

    if ($group_by) {
        // Query for grouping by grupo_id
        $query = "SELECT grupo_id, 
                         MAX(nombre) as nombre, 
                         MAX(descripccion) as descripccion, 
                         estado_id, 
                         estado.descripcion, 
                         SUM(cantidad) as cantidad_total, 
                         SUM(uso) as uso_total, 
                        (SELECT observaciones FROM items i WHERE i.grupo_id = items.grupo_id LIMIT 1) as observaciones
                  FROM items 
                  JOIN estado ON items.estado_id = estado.idestado";
    } else {
        // Query without grouping
        $query = $base_query . " FROM items JOIN estado ON items.estado_id = estado.idestado";
    }

    // Add area filter if applicable
    if ($area_id) {
        if ($group_by) {
            $query .= " WHERE seccion_id = :area_id GROUP BY grupo_id, estado_id, estado.descripcion";
        } else {
            $query .= " WHERE seccion_id = :area_id";
        }
    } elseif ($group_by) {
        $query .= " GROUP BY grupo_id, estado_id, estado.descripcion";
    }

    $stmt = $conn->prepare($query);

    if ($area_id) {
        $stmt->bindParam(':area_id', $area_id, PDO::PARAM_INT);
    }

    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($group_by) {
            $nombre = $row["nombre"];
            $descripccion = $row["descripccion"];
            $descripcion = $row["descripcion"];
            $cantidad_total = $row["cantidad_total"];
            $uso_total = $row["uso_total"];
            $observaciones = $row["observaciones"];
            $uso_horas = minutosAHoras($uso_total);
        } else {
            $codigo = $row["codigo"];
            $nombre = $row["nombre"];
            $descripccion = $row["descripccion"];
            $descripcion = $row["descripcion"];
            $cantidad = $row["cantidad"];
            $uso = $row["uso"];
            $observaciones = $row["observaciones"];
            $uso_horas = minutosAHoras($uso);
        }

        echo "
        <tr>
            <td><a href='fichaitem.php?".($group_by ? "grupo_id={$row['grupo_id']}" : "codigo=$codigo")."'>".$nombre."</a></td>
            <td>".$descripccion."</td>
            <td>".$descripcion."</td>
            <td>".($group_by ? $cantidad_total : $cantidad)."</td>
            <td>".$uso_horas."</td>
            <td>".$observaciones."</td>
        </tr>
        ";
    }
}

$areas = [
    ["name" => "Oficina", "icon" => "ico/oficina.svg", "area_id" => 1],
    ["name" => "Bodega", "icon" => "ico/bodega.svg", "area_id" => 2],
    ["name" => "Auditorio", "icon" => "ico/auditorio.svg", "area_id" => 3],
    ["name" => "Asamblea", "icon" => "ico/comite.png", "area_id" => 4],
    ["name" => "Residencia", "icon" => "ico/residencia.svg", "area_id" => 5],
];
?>
    <title>SAM assistant</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
    <style type="text/css">
        table {
            border-collapse: separate!important;
            border-spacing: 0!important;
        }
        thead tr:first-child td:first-child { border-top-left-radius: 15px!important; }
        thead tr:first-child td:last-child { border-top-right-radius: 15px!important; }

        tbody tr:last-child td:first-child { border-bottom-left-radius: 15px!important; }
        tbody tr:last-child td:last-child { border-bottom-right-radius: 15px!important; }

        tr:first-child td { border-top-style: solid!important; }
        tr td:first-child { border-left-style: solid!important; }

        tbody tr:last-child { border-bottom-color: transparent!important; }

        tbody tr td { border-left:solid 1px chocolate; }
        tbody tr td:first-child { border-left-color: transparent!important; }

        thead tr th { border-left:solid 1px chocolate; }
        thead tr th:first-child { border-left-color: transparent!important; }
    </style>
    <style>
        .dataTables_filter {
            float: right;
            margin: 10px;
        }
        .dataTables_filter input {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-left: 5px;
        }
        table {
            border-collapse: separate!important;
            border-spacing: 0!important;
        }
        /* ...existing styles... */
    </style>
</head>
<body>
    <div class="w-100 mb-2 p-1" style="text-align:left; background-color: #e8ecf2; color:#5C6872;"><b>INVENTARIO</b></div>
    <div class="container-fluid mt-3">
       <!-- Visualizacion de las areas para administracion -->
        <div class="btn-group justify-content-between me-2 p-2" role="group" aria-label="First group">
            <button type="button" class="btn btn-outline-primary mx-1" onclick="filterByArea(null)">
                <img  src="/public//ico/general.svg" class="button-icon" alt="General">
                <span class="d-none d-md-inline">General</span>
                <small class="d-none d-sm-inline d-md-none align-middle">General</small>
            </button>
            <?php foreach ($areas as $area): ?>
                <button type="button" class="btn btn-outline-primary mx-1" onclick="filterByArea(<?php echo $area['area_id']; ?>)">
                    <img src="/public/<?php echo $area['icon']; ?>" class="button-icon" alt="<?php echo $area['name']; ?>">
                    <span class="d-none d-md-inline"><?php echo $area['name']; ?></span>
                </button>
            <?php endforeach; ?>
            <button type="button" class="btn btn-outline-primary mx-1" onclick="groupByItems()">
                <img  src="/public/ico/cantidad.svg"  class="button-icon" alt="Cantidad por item" >
                <span class="d-none d-md-inline">Cantidad por item</span>
                <small class="d-none d-sm-inline d-md-none align-middle"><a href="#">Cantidad por item</a></small>
            </button>    
            <button type="button" class="btn btn-outline-primary mx-1">
                <img  src="/public/ico/nuevo.png"  class="button-icon" alt="Nuevo Item">
                <span class="d-none d-md-inline"><a href="nuevoitem.php">Nuevo</a></span>
                <small class="d-none d-sm-inline d-md-none align-middle"><a href="nuevoitem.php">Nuevo Item</a></small>
            </button>   
        </div>
        <div class="table-responsive">          
            <table 
                id="table"
                class="table w-100 roundedTable"
                style="border: solid 2px chocolate!important; overflow:hidden;
                    border-radius: 15px !important;
                    border-width: 2px !important;
                    border-style: solid !important;
                    border-color: chocolate !important;">
                <thead style="">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Cantidad</th>
                        <th>Tiempo de uso</th>
                        <th>Observaciones</th>
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