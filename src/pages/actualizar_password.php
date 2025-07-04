<?php
session_start();
require './../db/dbconn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validaciones básicas
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Las nuevas contraseñas no coinciden']);
    exit;
}

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verificar contraseña actual 
    $stmt = $conn->prepare("SELECT pwd FROM user WHERE voluntario = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || $current_password !== $user['pwd']) {
        echo json_encode(['success' => false, 'message' => 'La contraseña actual es incorrecta']);
        exit;
    }

    // Actualizar contraseña 
    $stmt = $conn->prepare("UPDATE user SET pwd = :password WHERE voluntario = :user_id");
    $stmt->bindParam(':password', $new_password);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();

    $_SESSION['password_message'] = 'Contraseña actualizada correctamente';
    $_SESSION['password_success'] = true;
    echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
    exit;

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la contraseña: ' . $e->getMessage()]);
    exit;
}
?>