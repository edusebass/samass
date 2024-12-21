<?php
require './../layout/head.html';
require './../utils/session_check.php';

?>
<?php require './../layout/header.php';?>
    <title>SAM Assistant</title>
    </head>
    <body></body>
    <div >
        <div style="text-align:left;">
            <h1>CATEGORIAS</h1>
    </div>
    <table>
        <tr text-align="left">
            <th>SECCION</th>
            <th>CATEGORIAS</th>
        </tr>
        
<?php
require_once './../utils/session_check.php';
require './../db/dbconn.php';

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    // Prepara e esegue la query
    $stmt = $conn->prepare("SELECT * FROM categorias order by seccion asc");
    $stmt->execute();

    // Imposta il modo di recupero dei risultati su FETCH_ASSOC
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);

    foreach ($stmt->fetchAll() as $row) {
        echo "<tr><td>"
        .$row['seccion']."</td><td>"
        .$row['categorias']."</td>
        </tr>";
    }

} catch(PDOException $e) {
    echo "Errore: " . $e->getMessage();
}



?>
    <tr class="mio-testo">
        <td colspan="10"><hr>Totales: <? echo "$t";?></td>
    </tr>

    </table>
            <br>
  

    <?php
require './../layout/footer.htm';
    ?>   
    </div></div> 
</body>
</html>