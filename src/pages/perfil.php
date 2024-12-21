<?php
require './../layout/head.html';
require './../utils/session_check.php';

?>
<?php require './../layout/header.php'; ?>
    <title>SAM Assistant</title>
    </head>
    <body>
    <div class="container">
    <div class="content">
    <div class="bg">
        <div class="tx">
            <h1>PERFIL USUARIO</h1>
            <br>
            <ul>
        
<?php
require './../db/dbconn.php';
unset($_SESSION['qr_content']);
unset($_SESSION['id_voluntario']);
unset($_SESSION['codigo_item']);

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$t = 0;
    // Prepara e esegue la query
    $stmt = $conn->prepare("SELECT voluntario, nome, roles.rol activo FROM user left JOIN roles ON roles.idroles=user.rol 
    where activo like 'si' and voluntario like '$_SESSION[user_id]'");
    $stmt->execute();

    // Imposta il modo di recupero dei risultati su FETCH_ASSOC
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    
    foreach ($stmt->fetchAll() as $row) {
        echo "<li><h4>Numero Voluntario: "
        .$row['voluntario']."</li><li><h4>Nombre y Apellido: "
        .$row['nome']."</li><li><h4>Departamento: "
        .$row['activo']."</li></ul></div>";
        $t++;}

} catch(PDOException $e) {
    echo "Errore: " . $e->getMessage();
}

?>
    
</ul></div>

</div>

<?php require './../layout/footer.htm'; ?>   

</div> 
</body>
</html>