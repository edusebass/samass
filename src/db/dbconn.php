<?php
$servername = "127.0.0.1";
$username = "root";
$password = "root";
$database = "samass";
$port = 3306;

$conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


