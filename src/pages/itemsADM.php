<?php
require './../layout/head.html';
require './../utils/session_check.php';
?>
   

    <?php require './../layout/header.php';?>
    <title>Splash IntraSAM</title>
    </head>
    <body>
    <div class="container">
    <div class="content">
    <div class="bg">
        <div class="tx">
            <h1>ITEMS</h1>
        </div>
    <table>
        <tr>
            <th>ID</th>
            <th>NOMBRE</th>
            <th>DESCRIPCCION</th>
            <th>ESTADO</th>
        </tr>
        
<?php
require_once './../utils/session_check.php';
require './../db/dbconn.php';

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$t = 0;
    // Prepara e esegue la query
    $stmt = $conn->prepare("SELECT * FROM items order by iditems asc  ");
    $stmt->execute();

    // Imposta il modo di recupero dei risultati su FETCH_ASSOC
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    
    foreach ($stmt->fetchAll() as $row) {
        echo "<tr><td>"
        .$row['iditems']."</td><td>"
        .$row['nombre']."</td><td>"
        .$row['descripccion']."</td><td>"
        .$row['estado']."</td><td>"
        .$row['cantidad']."</td>
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
    
    <?php
require './../utils/footer.htm';
    ?>   
</body>
</html>