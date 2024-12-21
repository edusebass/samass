<?php
require './../layout/head.html';
require './../utils/session_check.php';
require_once './../utils/session_check.php';
require './../db/dbconn.php';
?>
    <title>SAM Assistant</title>
    <style>

    </style>
</head>
<body>
<?php
    require './../layout/header.php';
?>
       <div class="bg">
        <div class="tx">
            <h1>SECCIONES</h1>
        </div>
    <table>
        <tr>
            <th>ID</th>
            <th>SECCION</th>
        </tr>
        
<?php


try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$t = 0;
    // Prepara e esegue la query
    $stmt = $conn->prepare("SELECT * FROM secciones order by idsecciones asc  ");
    $stmt->execute();

    // Imposta il modo di recupero dei risultati su FETCH_ASSOC
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    
    foreach ($stmt->fetchAll() as $row) {
        echo "<tr><td>"
        .$row['idsecciones']."</td><td>"
        .$row['seccion']."</td>
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
    
</body>
</html>