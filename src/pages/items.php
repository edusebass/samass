<?php
require './../layout/head.html';
require './../utils/session_check.php';
?>
    <title>SAM Assistant</title>

    <style>     
    </style>
    </head>
    <body>

    <?php require './../layout/header.php';?>
    <br>
         <div class="tx" style="background-color: #e8ecf2;">
            <strong>INVENTARIO</strong>
        </div>
        
    <table bordercolor="chocolate" style="border-width:1px;">
        <tr>
            <th>ID</th>
            <th>NOMBRE</th>
            <th>DESCRIPCIÓN</th>
            <th>CANTIDAD</th>
            <th>ESTADO</th>
            <?php
            $codigo = $_SESSION['codigo'];
            if ($codigo == 'ADM') {
                echo'
                <th  style="text-align:center;">MINIMALES</th>';
            }
            ?>
        </tr>
        
<?php
require_once './../utils/session_check.php';
require './../db/dbconn.php';

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$t = 0;
    // Prepara e esegue la query
    $codigo = $_SESSION['codigo'];
    if ($codigo == 'ADM' || $codigo == 'SU') {
        $stmt = $conn->prepare("SELECT * FROM items order by iditems asc  ");
    } else {
        $stmt = $conn->prepare("SELECT * FROM items where bodega like 'si' order by iditems asc  ");
    }
    $stmt->execute();

    // Imposta il modo di recupero dei risultati su FETCH_ASSOC
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    
    foreach ($stmt->fetchAll() as $row) {
        echo "<tr><td>"
        .$row['iditems']."</td><td>"
        .$row['nombre']."</td><td>"
        .$row['descripcion']."</td><td>"
        .$row['cantidad']."</td><td>"
        .$row['estado']."</td><td  style='text-align:center;'>";
        if ($codigo == 'ADM') {
        echo $row['cantidad_minima1'].' / '.$row['cantidad_minima2']."</td>
        </tr>";
        }
    $t++;}

} catch(PDOException $e) {
    echo "Errore: " . $e->getMessage();
}

?>
    <tr class="mio-testo">
        <td colspan="10"><hr>Totales: <?php echo $t; ?></td>
    </tr>

    </table>
    <br>
    <?php
require './../layout/footer.htm';
    ?>   
    </div></div> 
</body>
</html>