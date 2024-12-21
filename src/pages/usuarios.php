<?php
require './../layout/head.html';
include('./../utils/verificar_rol.php');
?>
    <title>SAM assistant</title>
    </head>
<body>
<?php
require './../layout/header.php';
require './../utils/session_check.php';
?>
    <div class="container-fluid">
        <div class="w-100 mb-2 p-1" style="text-align:left; background-color: #e8ecf2; color:#5C6872;"><b>USUARIOS REGISTRADOS</b></div>
    <div class="container-fluid mt-3">
    <table bordercolor="chocolate" style="border-width:1px;">
        <tr style="text-align:left;">
            <th></th>
            <th>VOLUNTARIO</th>
            <th>NOMBRE</th>
            <th>ROL</th>
            <th>ACTIVO</th>
            <th>ULTIMA CONEXION</th>
        </tr>
        
<?php
require_once './../utils/session_check.php';
require './../db/dbconn.php';

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$t = 0;
    // Prepara e esegue la query
    $stmt = $conn->prepare("SELECT * FROM user JOIN roles ON roles.idroles=user.rol 
     and roles.idroles not like 6 order by voluntario asc  ");
    $stmt->execute();

    // Imposta il modo di recupero dei risultati su FETCH_ASSOC
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    
    foreach ($stmt->fetchAll() as $row) {
        echo "<tr><th></th><td><a href='#'>"
        .$row['voluntario']."</td><td>"
        .$row['nome']."</td><td>"
        .$row['rol']."</td><td>"
        .$row['activo']."</td><td>";
        $dateString = $row['ultimaconn'];
        if (empty($dateString)) {
            echo "Conexión aún no establecida";
        } else {
            $date = new DateTime($dateString);
        $newDateFormat = $date->format('d-m-Y H:i:s');
        echo $newDateFormat;
        }"</td>
        </tr>";
        $t++;}

} catch(PDOException $e) {
    echo "Errore: " . $e->getMessage();
}

?>
    <tr class="mio-testo">
        <td colspan="10"><hr>Voluntarios Registrados en el Sistema: <?php echo $t; ?></td>
    </tr>
    </table>
     </div></div>
     <br>
<?php
require './../layout/footer.htm';
    ?>   

    <script src="js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
 </div>
</body>
</html>