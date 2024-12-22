<?php
$servername = "127.0.0.1";
$username = "root";
$password = "root";
$database = "samass";
$port = 3306; 

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    
    // Imposta il modo di errore PDO su eccezione
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connessione riuscita";
} catch(PDOException $e) {
    echo "Connessione fallita: " . $e->getMessage();
}
?>
