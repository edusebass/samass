<?php
/**
 * Actualizar Devolución - Endpoint para gestión de estados de devolución
 *
 * Descripción:
 * Script API que maneja la actualización de estados de devolución para herramientas prestadas.
 * Procesa solicitudes POST para cambiar el estado de devolución de items y registra fechas de recepción.
 *
 * Funcionalidades:
 * - Actualiza el estado de devolución (Devuelto/No devuelto/Perdido/Entregado).
 * - Registra automáticamente la fecha de recepción cuando el estado es "Devuelto".
 * - Valida que el estado recibido sea uno de los permitidos.
 * - Restringe actualizaciones solo a items pertenecientes al voluntario logueado.
 * - Retorna respuestas en formato JSON para integración con AJAX.
 *
 * Variables principales:
 * - $_POST['id']: ID de la solicitud a actualizar.
 * - $_POST['estado']: Nuevo estado de devolución.
 * - $response: Array con resultado de la operación.
 *
 * Estados permitidos:
 * - Devuelto: Item retornado (registra fecha automática).
 * - Entregado: Item entregado pero no devuelto.
 * - No devuelto: Item no retornado en plazo.
 * - Perdido: Item dado por perdido.
 *
 * Dependencias:
 * - dbconn.php: Conexión a la base de datos.
 * - session_check.php: Verificación de sesión activa.
 *
 * Seguridad:
 * - Requiere método POST y parámetros específicos.
 * - Valida sesión activa del voluntario.
 * - Verifica que el voluntario solo pueda modificar sus propios items.
 * - Filtra estados permitidos para prevenir inyección.
 * - Cabecera Content-Type definida como JSON.
 *
 * Respuestas JSON:
 * - success: boolean indica si la operación fue exitosa.
 * - new_state: string con el nuevo estado aplicado (solo en éxito).
 * - error: string con mensaje de error (solo en fallos).
 *
 * Flujo típico:
 * 1. Validar método POST y parámetros requeridos.
 * 2. Verificar que el estado recibido sea válido.
 * 3. Actualizar registro en base de datos con condición de voluntario.
 * 4. Registrar fecha automática si el estado es "Devuelto".
 * 5. Retornar resultado en formato JSON.
 *
 * Casos de error:
 * - Solicitud no POST o parámetros faltantes.
 * - Estado no incluido en lista permitida.
 * - Error de conexión a base de datos.
 * - Intento de modificar item de otro voluntario.
 */
require_once './../../db/dbconn.php';
require_once './../../utils/session_check.php';

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['estado'])) {
    try {
        // Validar el estado recibido
        $estadosPermitidos = ['Devuelto', 'Entregado', 'No devuelto', 'Perdido'];
        if (!in_array($_POST['estado'], $estadosPermitidos)) {
            throw new Exception('Estado no válido');
        }

        $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                              SET estado_devolucion = ?,
                                  estado_entrega = CASE
                                      WHEN ? = 'Devuelto' THEN 'Entregado'
                                      ELSE estado_entrega
                                  END,
                                  fecha_recibido = CASE 
                                      WHEN ? = 'Devuelto' THEN NOW() 
                                      ELSE NULL 
                                  END
                              WHERE idsolicitud = ? 
                              AND voluntarioid = ?");
        $stmt->execute([
            $_POST['estado'],
            $_POST['estado'],
            $_POST['estado'],
            $_POST['id'],
            $_SESSION['user_id']
        ]);
        
        $response['success'] = true;
        $response['new_state'] = $_POST['estado'];
    } catch(PDOException $e) {
        $response['error'] = 'Error al actualizar: ' . $e->getMessage();
    } catch(Exception $e) {
        $response['error'] = $e->getMessage();
    }
} else {
    $response['error'] = 'Solicitud inválida';
}

echo json_encode($response);
?>