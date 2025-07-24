<?php
/**
 * Actualizar Estados - Endpoint para gestión de estados de entrega
 *
 * Descripción:
 * Script API que maneja la actualización de estados de entrega (Entregado/Pendiente) para herramientas solicitadas.
 * Procesa solicitudes POST para cambiar el estado de entrega y gestiona fechas asociadas.
 *
 * Funcionalidades:
 * - Alterna entre estados 'Entregado' y 'Pendiente' según la acción recibida.
 * - Gestiona automáticamente las fechas de entrega (NOW() al marcar, NULL al desmarcar).
 * - Sincroniza el estado de devolución con el estado de entrega.
 * - Valida que el ítem pertenezca al voluntario logueado.
 * - Retorna respuestas en formato JSON para integración con AJAX.
 *
 * Variables principales:
 * - $_POST['item_id']: ID de la solicitud a actualizar.
 * - $_POST['accion']: Tipo de acción ('marcar' o cualquier otro valor para desmarcar).
 * - $response: Array con resultado de la operación.
 *
 * Acciones soportadas:
 * - 'marcar': Cambia estado a 'Entregado' y registra fecha actual.
 * - otras: Cambia estado a 'Pendiente' y limpia fechas/estados relacionados.
 *
 * Dependencias:
 * - dbconn.php: Conexión a la base de datos.
 * - session_check.php: Verificación de sesión activa.
 *
 * Seguridad:
 * - Requiere método POST y parámetros específicos.
 * - Valida sesión activa del voluntario.
 * - Restringe actualizaciones solo a items del voluntario logueado.
 * - Cabecera Content-Type definida como JSON.
 *
 * Respuestas JSON:
 * - success: boolean indica si la operación fue exitosa.
 * - error: string con mensaje de error (solo en fallos).
 *
 * Efectos en base de datos:
 * - Al marcar:
 *   * estado_entrega = 'Entregado'
 *   * estado_devolucion = 'Entregado'
 *   * fecha_entregado = NOW()
 * - Al desmarcar:
 *   * estado_entrega = 'Pendiente'
 *   * estado_devolucion = NULL
 *   * fecha_entregado = NULL
 *
 * Flujo típico:
 * 1. Validar método POST y parámetros requeridos.
 * 2. Determinar acción a realizar (marcar/desmarcar).
 * 3. Construir y ejecutar consulta SQL condicional.
 * 4. Retornar resultado en formato JSON.
 *
 * Casos de error:
 * - Solicitud no POST o parámetros faltantes.
 * - Error de conexión a base de datos.
 * - Intento de modificar item de otro voluntario.
 */
require_once './../../db/dbconn.php';
require_once './../../utils/session_check.php';

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['accion'])) {
    try {
        $item_id = $_POST['item_id'];
        $voluntario_id = $_SESSION['user_id'];
        
        if ($_POST['accion'] === 'marcar') {
            // Marcar como entregado (actualizar ambos estados)
            $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                                  SET estado_entrega = 'Entregado', 
                                      estado_devolucion = 'Entregado',
                                      fecha_entregado = NOW()
                                  WHERE idsolicitud = ? 
                                  AND voluntarioid = ?");
        } else {
            // Desmarcar (volver a pendiente)
            $stmt = $conn->prepare("UPDATE solicitudes_herramientas 
                                  SET estado_entrega = 'Pendiente', 
                                      estado_devolucion = NULL,
                                      fecha_entregado = NULL
                                  WHERE idsolicitud = ? 
                                  AND voluntarioid = ?");
        }
        
        $stmt->execute([$item_id, $voluntario_id]);
        
        $response['success'] = true;
    } catch(PDOException $e) {
        $response['error'] = 'Error al actualizar: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Solicitud inválida';
}

echo json_encode($response);
?>