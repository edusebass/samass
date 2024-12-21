<?php
require_once './../db/dbconn.php';
function ejecutar_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}