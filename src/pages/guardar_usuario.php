<?php
/**
 * Guardar Usuario - Procesamiento de formulario de usuarios
 *
 * Descripción:
 * Maneja la creación y actualización de usuarios en el sistema. Procesa los datos del formulario
 * y realiza las operaciones correspondientes en la base de datos (INSERT o UPDATE).
 *
 * Funcionalidades:
 * - Creación de nuevos usuarios con todos los campos obligatorios.
 * - Actualización de usuarios existentes (con o sin cambio de contraseña).
 * - Validación básica de campos obligatorios.
 * - Manejo de errores, incluyendo duplicados de código de voluntario.
 *
 * Variables principales:
 * - $voluntario: Código identificador del voluntario/usuario.
 * - $nome: Nombre completo del usuario.
 * - $pwd: Contraseña (opcional para actualización, obligatoria para nuevos).
 * - $rol: Rol/perfil del usuario en el sistema.
 * - $activo: Estado activo/inactivo del usuario.
 * - $voluntario_original: Código original (para actualizaciones).
 *
 * Dependencias:
 * - session_check.php: Verificación de sesión activa.
 * - dbconn.php: Conexión a la base de datos.
 *
 * Seguridad:
 * - Requiere sesión activa (session_check.php).
 * - Solo acepta método POST.
 * - Validación de campos obligatorios.
 * - Consideración: Uso de md5() para hashing (se recomienda password_hash() en producción).
 */

require './../utils/session_check.php';
require_once './../db/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: usuarios.php');
    exit;
}

// Obtener datos del formulario
$voluntario = trim($_POST['voluntario'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$pwd = $_POST['pwd'] ?? ''; // Contraseña en texto plano
$rol = $_POST['rol'] ?? '';
$activo = $_POST['activo'] ?? 'si';
$voluntario_original = $_POST['voluntario_original'] ?? '';

// Validación básica
if (empty($voluntario) || empty($nome) || empty($rol)) {
    die('Faltan campos obligatorios');
}

try {
    // Verificar si el voluntario ya existe (excepto en edición del mismo)
    if (!$voluntario_original || $voluntario_original != $voluntario) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM user WHERE voluntario = ?");
        $stmt->execute([$voluntario]);
        if ($stmt->fetchColumn() > 0) {
            die('El código de voluntario ya existe');
        }
    }
    
    if ($voluntario_original) {
        // Actualizar usuario existente
        if (!empty($pwd)) {
            $stmt = $conn->prepare("UPDATE user SET voluntario = ?, nome = ?, pwd = ?, rol = ?, activo = ? WHERE voluntario = ?");
            $stmt->execute([$voluntario, $nome, $pwd, $rol, $activo, $voluntario_original]);
        } else {
            $stmt = $conn->prepare("UPDATE user SET voluntario = ?, nome = ?, rol = ?, activo = ? WHERE voluntario = ?");
            $stmt->execute([$voluntario, $nome, $rol, $activo, $voluntario_original]);
        }
    } else {
        // Insertar nuevo usuario
        if (empty($pwd)) {
            die('La contraseña es obligatoria para nuevos usuarios');
        }
        $stmt = $conn->prepare("INSERT INTO user (voluntario, nome, pwd, rol, activo, create_time) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $stmt->execute([$voluntario, $nome, $pwd, $rol, $activo]);
    }
    
    header('Location: usuarios.php');
    exit;
} catch(PDOException $e) {
    die('Error al guardar usuario: ' . $e->getMessage());
}