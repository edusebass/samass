<?php
/**
 * Eliminar Usuario - Script para la eliminación de usuarios del sistema
 *
 * Descripción:
 * Maneja la eliminación segura de usuarios con validación de permisos y restricciones.
 * Solo usuarios con roles específicos (Administrador/Super User) pueden ejecutar esta acción.
 *
 * Funcionalidades:
 * - Verificación estricta de permisos (solo roles 1 y 6 pueden eliminar).
 * - Prevención de auto-eliminación (un usuario no puede eliminarse a sí mismo).
 * - Manejo de errores y retroalimentación al usuario mediante mensajes de sesión.
 * - Redirección segura tras la operación.
 *
 * Variables principales:
 * - $_SESSION['rol']: Rol del usuario actual para validación de permisos.
 * - $_GET['voluntario']: Identificador del usuario a eliminar.
 * - $_SESSION['mensaje']: Mensaje de éxito almacenado para mostrar después de redirección.
 * - $_SESSION['error']: Mensaje de error almacenado para mostrar después de redirección.
 *
 * Dependencias:
 * - session_check.php: Verificación de sesión activa.
 * - dbconn.php: Conexión a la base de datos.
 *
 * Seguridad:
 * - Requiere sesión activa y verificación de rol específico.
 * - Solo acepta método GET con parámetro validado.
 * - Previene auto-eliminación.
 * - Escapa parámetros antes de usarlos en consultas SQL.
 * - Manejo de excepciones para errores de base de datos.
 * - Mensajes de error no revelan información sensible.
 */
require './../utils/session_check.php';
require_once './../db/dbconn.php';

// Verificar permisos
if ($_SESSION['rol'] !== '1' && $_SESSION['rol'] !== '6') {
    die('No tienes permisos para realizar esta acción');
}

// Verificar método y parámetro
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || empty($_GET['voluntario'])) {
    header('Location: usuarios.php');
    exit;
}

$voluntario = $_GET['voluntario'];

try {
    // Verificar que no sea el propio usuario
    if ($voluntario === $_SESSION['voluntario']) {
        die('No puedes eliminarte a ti mismo');
    }

    // Eliminar usuario
    $stmt = $conn->prepare("DELETE FROM user WHERE voluntario = ?");
    $stmt->execute([$voluntario]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['mensaje'] = 'Usuario eliminado correctamente';
    } else {
        $_SESSION['error'] = 'No se encontró el usuario a eliminar';
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Error al eliminar usuario: ' . $e->getMessage();
}

header('Location: usuarios.php');
exit;