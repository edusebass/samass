<?php
$servername = "127.0.0.1";
$username = "root";
$password = "SAM003";
$database = "samass";
$port = 3307;

$conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


