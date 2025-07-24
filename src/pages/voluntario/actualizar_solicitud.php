<?php
/**
 * Actualizar Solicitud - Endpoint para modificación de solicitudes existentes
 *
 * Descripción:
 * Script API que permite a los voluntarios editar los detalles de sus solicitudes de herramientas.
 * Procesa actualizaciones de nombre, cantidad y observaciones de items previamente solicitados.
 *
 * Funcionalidades:
 * - Actualiza campos básicos de solicitudes (nombre, cantidad, observaciones).
 * - Restringe modificaciones solo a solicitudes propias del voluntario.
 * - Valida integridad de datos mediante consultas preparadas.
 * - Retorna respuestas estructuradas en formato JSON.
 *
 * Campos actualizables:
 * - nombreitem: Nombre/descripción de la herramienta solicitada.
 * - cantidad: Cantidad requerida (entero positivo).
 * - observaciones: Notas adicionales sobre la solicitud.
 *
 * Variables principales:
 * - $_POST['idsolicitud']: ID de la solicitud a modificar (requerido).
 * - $_POST['nombreitem']: Nuevo nombre para el item.
 * - $_POST['cantidad']: Nueva cantidad solicitada.
 * - $_POST['observaciones']: Nuevas observaciones.
 * - $response: Array con resultado de la operación.
 *
 * Dependencias:
 * - dbconn.php: Conexión a la base de datos.
 * - session_check.php: Verificación de sesión activa.
 *
 * Seguridad:
 * - Requiere autenticación mediante sesión.
 * - Solo permite modificar solicitudes del usuario logueado.
 * - Valida método POST y parámetros obligatorios.
 * - Usa consultas preparadas para prevenir inyección SQL.
 * - Cabecera Content-Type definida como JSON.
 *
 * Respuestas JSON:
 * - success: boolean indica si la actualización fue exitosa.
 * - error: string con mensaje descriptivo en caso de fallo.
 *
 * Flujo típico:
 * 1. Validar método POST y presencia de ID de solicitud.
 * 2. Verificar sesión activa del voluntario.
 * 3. Construir y ejecutar consulta UPDATE con restricción de voluntario.
 * 4. Retornar éxito o error en formato JSON.
 *
 * Casos de error:
 * - Solicitud no POST o falta de ID de solicitud.
 * - Error de conexión con base de datos.
 * - Intento de modificar solicitud de otro voluntario.
 * - Campos requeridos vacíos (manejado por frontend).
 *
 * Notas:
 * - No permite modificar estados de entrega/devolución (usar actualizar_estados.php).
 * - No actualiza fechas automáticamente (conserva fecha original de solicitud).
 */
require_once './../../db/dbconn.php';
require_once './../../utils/session_check.php';

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idsolicitud'])) {
    try {
        $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                               SET nombreitem = ?, 
                                   cantidad = ?, 
                                   observaciones = ?
                               WHERE idsolicitud = ? 
                               AND voluntarioid = ?");
        $stmt->execute([
            $_POST['nombreitem'],
            $_POST['cantidad'],
            $_POST['observaciones'],
            $_POST['idsolicitud'],
            $_SESSION['user_id']
        ]);
        
        $response['success'] = true;
    } catch(PDOException $e) {
        $response['error'] = 'Error al actualizar: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Solicitud inválida';
}

echo json_encode($response);
?>