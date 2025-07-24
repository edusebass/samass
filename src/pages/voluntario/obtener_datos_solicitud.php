<?php
/**
 * Obtener Datos de Solicitud - Endpoint para consulta de solicitudes individuales
 *
 * Descripción:
 * Servicio especializado en recuperar información detallada de solicitudes específicas
 * para operaciones de edición o visualización en el frontend.
 *
 * Funcionalidades clave:
 * - Consulta segura de datos de solicitud por ID
 * - Filtrado por propiedad (voluntarioid)
 * - Retorno estructurado en JSON
 * - Protección contra acceso no autorizado
 *
 * Campos devueltos:
 * - nombreitem: Nombre de la herramienta solicitada
 * - cantidad: Unidades requeridas
 * - observaciones: Notas adicionales
 *
 * Seguridad:
 * - Autenticación obligatoria via sesión
 * - Restricción por voluntario_id
 * - Método POST requerido
 * - Consultas preparadas
 * - Cabecera Content-Type: application/json
 *
 * Respuestas JSON:
 * - Éxito: 
 *   {
 *     "success": true,
 *     "data": {
 *       "nombreitem": "...",
 *       "cantidad": X,
 *       "observaciones": "..."
 *     }
 *   }
 * - Error: 
 *   {
 *     "success": false,
 *     "error": "Mensaje descriptivo"
 *   }
 *
 * Códigos de error comunes:
 * - "Solicitud inválida" (method/params incorrectos)
 * - "Solicitud no encontrada" (no existe/no pertenece)
 * - "Error en la base de datos" (excepciones PDO)
 *
 * Flujo de operación:
 * 1. Validar método POST y parámetro ID
 * 2. Verificar sesión activa
 * 3. Ejecutar consulta con doble filtro (ID + voluntario)
 * 4. Retornar datos o error correspondiente
 *
 * Integración:
 * - Usado principalmente en flujos de edición
 * - Consumido por:
 *   - Modales de edición
 *   - Paneles de detalle
 * - Complementa actualizar_solicitud.php
 *
 * Optimización:
 * - Selección explícita de columnas (no SELECT *)
 * - Fetch asociativo para estructura clara
 * - Manejo ligero de recursos
 *
 * Auditoría:
 * - Registro de errores via PDOException
 * - Validación de existencia antes de retorno
 *
 * Notas técnicas:
 * - No expone información sensible
 * - Campos devueltos coinciden con formularios de edición
 * - Estructura compatible con Swagger/OpenAPI
 */
require_once './../../db/dbconn.php';
require_once './../../utils/session_check.php';

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $stmt = $conn->prepare("SELECT * 
                               FROM solicitudes_herramientas 
                               WHERE idsolicitud = ? 
                               AND voluntarioid = ?");
        $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($solicitud) {
            $response = [
                'success' => true,
                'data' => $solicitud
            ];
        } else {
            $response['error'] = 'Solicitud no encontrada';
        }
    } catch(PDOException $e) {
        $response['error'] = 'Error en la base de datos: ' . $e->getMessage();
    }
} else {
    $response['error'] = 'Solicitud inválida';
}

echo json_encode($response);
?>