<?php
/**
 * Ejecutar Query - Función auxiliar para ejecutar consultas SQL
 * 
 * Función genérica para ejecutar consultas preparadas de manera segura
 * con parámetros opionales.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

require './../../db/dbconn.php';

function ejecutar_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}