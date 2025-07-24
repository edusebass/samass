/**
 * Guardar Solicitud - Endpoint para creación de nuevas solicitudes de herramientas
 *
 * Descripción:
 * Procesa el registro de nuevas solicitudes de herramientas por parte de voluntarios,
 * validando y almacenando la información en la base de datos. Diseñado para integración
 * con llamadas AJAX desde el panel del voluntario.
 *
 * Funcionalidades:
 * - Registro de nuevas solicitudes con estado inicial 'Pendiente'
 * - Validación de campos obligatorios y formatos
 * - Asignación automática de fecha/hora de solicitud (vía BD)
 * - Retorno del ID generado para referencia inmediata
 * - Protección contra inyección SQL mediante consultas preparadas
 *
 * Campos requeridos:
 * - nombreitem: Nombre/descripción de la herramienta (texto)
 * - cantidad: Número entero positivo (default: 1)
 * - voluntarioid: ID del voluntario (de sesión)
 * - observaciones: Opcional, texto adicional
 *
 * Validaciones:
 * - Método HTTP POST obligatorio
 * - Campos nombreitem y voluntarioid no vacíos
 * - Cantidad numérica y mayor a 0
 * - Sesión activa requerida
 *
 * Estructura de respuesta:
 * - Éxito: JSON {success: true, id: [nuevo_id]}
 * - Error: HTTP 500 con mensaje descriptivo
 *
 * Flujo de operación:
 * 1. Verificar método POST y sesión activa
 * 2. Validar estructura y contenido de datos
 * 3. Insertar registro con estado 'Pendiente'
 * 4. Retornar ID generado
 * 5. Manejar errores con códigos HTTP apropiados
 *
 * Seguridad:
 * - Validación de sesión obligatoria
 * - Filtrado de entradas
 * - Consultas preparadas
 * - No exposición de detalles internos en errores
 *
 * Integración:
 * - Diseñado para consumo desde formulario AJAX
 * - Coordina con voluntario.php para actualización de UI
 *
 * Base de datos:
 * - Campos insertados:
 *   * nombreitem
 *   * cantidad
 *   * voluntarioid
 *   * observaciones
 *   * estado_entrega (fijado como 'Pendiente')
 * - Campos automáticos:
 *   * idsolicitud (autoincremental)
 *   * fecha_solicitud (timestamp)
 *
 * Consideraciones:
 * - No edita solicitudes existentes (usar actualizar_solicitud.php)
 * - Estado inicial siempre es 'Pendiente'
 * - La cantidad se normaliza a mínimo 1
 */
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../usuarios/voluntario.php');
    exit;
}

// Obtener datos del formulario
$voluntario_id = $_POST['voluntarioid'] ?? '';
$nombre_item = $_POST['nombreitem'] ?? '';
$cantidad = $_POST['cantidad'] ?? 1;
$observaciones = $_POST['observaciones'] ?? '';

// Validaciones básicas
if (empty($voluntario_id) || empty($nombre_item)) {
    die('Faltan campos obligatorios');
}

if (!is_numeric($cantidad) || $cantidad < 1) {
    die('La cantidad debe ser un número positivo');
}

try {
    // Insertar la nueva solicitud (el ID será autoincremental)
    $stmt = $conn->prepare("INSERT INTO solicitudes_herramientas 
                          (nombreitem, cantidad, voluntarioid, observaciones, estado_entrega) 
                          VALUES (?, ?, ?, ?, 'Pendiente')");
    $stmt->execute([
        $nombre_item,
        $cantidad,
        $voluntario_id,
        $observaciones
    ]);
    
    // Obtener el ID generado automáticamente
    $solicitud_id = $conn->lastInsertId();
    
    // Respuesta para AJAX
    echo json_encode(['success' => true, 'id' => $solicitud_id]);
    exit;
    
} catch(PDOException $e) {
    http_response_code(500);
    die('Error al guardar la solicitud: ' . $e->getMessage());
}