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

header('Content-Type: application/json');

$response = ['success' => false];

// Verificar método POST y parámetros requeridos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['accion'], $_POST['voluntario_id'])) {
    try {
        // Validar que los IDs sean numéricos
        if (!is_numeric($_POST['item_id']) || !is_numeric($_POST['voluntario_id'])) {
            throw new Exception('ID inválido');
        }

        $item_id = (int)$_POST['item_id'];
        $voluntario_id = (int)$_POST['voluntario_id'];
        $accion = $_POST['accion'];

        // Determinar la acción a realizar
        if ($accion === 'marcar') {
            // Marcar como entregado (actualizar ambos estados)
            $stmt = $conn->prepare("UPDATE bodega_herramientas 
                                  SET estado_entrega = 'Entregado', 
                                      estado_devolucion = 'Entregado',
                                      fecha_entregado = NOW()
                                  WHERE idsolicitud = ? 
                                  AND voluntarioid = ?");
        } else {
            // Desmarcar (volver a pendiente)
            $stmt = $conn->prepare("UPDATE bodega_herramientas 
                                  SET estado_entrega = 'Pendiente', 
                                      estado_devolucion = NULL,
                                      fecha_entregado = NULL
                                  WHERE idsolicitud = ? 
                                  AND voluntarioid = ?");
        }
        
        $stmt->execute([$item_id, $voluntario_id]);
        
        // Verificar si se actualizó algún registro
        if ($stmt->rowCount() === 0) {
            throw new Exception('No se encontró la solicitud o no tienes permiso para modificarla');
        }
        
        $response['success'] = true;
        $response['new_state'] = ($accion === 'marcar') ? 'Entregado' : 'Pendiente';
    } catch(PDOException $e) {
        $response['error'] = 'Error al actualizar: ' . $e->getMessage();
    } catch(Exception $e) {
        $response['error'] = $e->getMessage();
    }
} else {
    // Identificar qué parámetros faltan
    $missing = [];
    if (!isset($_POST['item_id'])) $missing[] = 'item_id';
    if (!isset($_POST['accion'])) $missing[] = 'accion';
    if (!isset($_POST['voluntario_id'])) $missing[] = 'voluntario_id';
    
    $response['error'] = 'Solicitud inválida. Faltan parámetros: ' . implode(', ', $missing);
}

echo json_encode($response);
?>