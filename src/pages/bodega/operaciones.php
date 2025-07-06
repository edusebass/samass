<?php
/**
 * Operaciones de Bodega - Listado de operaciones
 * 
 * Muestra el listado de operaciones de bodega con información
 * sobre préstamos, devoluciones y estado de items.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

require './../../layout/head.html';
require './../../utils/session_check.php';

?>
    <title>SAM Assistant</title>
</head>
<body>
    <?php require './../../layout/header.php';?>
       
<div class="tx" style="background-color: #e8ecf2;">
            <strong>OPERACIONES</strong>
        </div>
        <table bordercolor="chocolate" style="border-width:1px;">
        <tr>
            <th>ID</th>
            <th>ITEM</th>
            <th>VOLUNTARIO</th>
            <th>CANTIDAD</th>
            <th>ESTADO</th>
            <th>FECHA SALIDA</th>
            <th>FECHA DEVOLUCCION</th>
        </tr>
<?php
require_once './../../utils/session_check.php';

require './../../db/dbconn.php';

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$t = 0;
    // Prepara e esegue la query
    $stmt = $conn->prepare("SELECT *
FROM operaciones
JOIN items ON operaciones.itemid = items.iditems
JOIN `user` ON operaciones.voluntarioid = `user`.voluntario;
 ");
    $stmt->execute();

    // Imposta il modo di recupero dei risultati su FETCH_ASSOC
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    
    foreach ($stmt->fetchAll() as $row) {
        echo "<tr><td>"
        .$row['idoperaciones']."</td><td>"
        .$row['nombre']."</td><td>"
        .$row['nome']."</td><td>"
        .$row['cantidad']."</td><td>"
        .$row['estado']."</td><td>"
        .$row['fechasalida']."</td><td>"
        .$row['fechaentrada']."</td>
        </tr>";
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
        </div>
    </div>
    <?php require './../../layout/footer.htm'; ?>
</body>
</html>