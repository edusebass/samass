<?php
/**
 * Código Generator - Utilidad para generar códigos de inventario
 *
 * Descripción:
 * Funciones centralizadas para generar códigos únicos de inventario
 * usando el formato 003-GS[n]-[nnn]
 *
 * @author  SAM Assistant Team
 * @version 1.0
 * @since   2025-01-02
 */

/**
 * Obtiene el prefijo de la tabla según las reglas de negocio
 * @param string $tabla Nombre de la tabla
 * @return string Prefijo correspondiente (GS1, GS2, etc.)
 */
function obtenerPrefijoTabla($tabla) {
    $prefijos = [
        'maquinas' => 'GS1',
        'herramientas_manuales' => 'GS2',
        'herramientas_equipo_jardineria' => 'GS3',
        'equipo_seguridad' => 'GS4',
        'habitacion_huesped_betel' => 'GS5',
        'items_generales_por_edificio' => 'GS6'
    ];
    return $prefijos[$tabla] ?? 'GS9';
}

/**
 * Genera el siguiente código disponible para la tabla
 * Formato: 003-GS[n]-[nnn]
 * 
 * @param PDO $conn Conexión a la base de datos
 * @param string $tabla Nombre de la tabla
 * @return string Código generado
 */
function generarCodigo($conn, $tabla) {
    $prefijo = obtenerPrefijoTabla($tabla);
    $stmt = $conn->prepare("SELECT codigo FROM $tabla WHERE codigo IS NOT NULL AND codigo != ''");
    $stmt->execute();
    $codigos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $max = 0;
    // Buscar el patrón: 003-GS[número]-[número]
    foreach ($codigos as $codigo) {
        if (preg_match('/^003-' . preg_quote($prefijo, '/') . '-(\d{3})$/', $codigo, $m)) {
            $num = intval($m[1]);
            if ($num > $max) $max = $num;
        }
    }
    
    $nuevo_num = str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    return "003-$prefijo-$nuevo_num";
}

/**
 * Valida el formato de un código de inventario
 * @param string $codigo Código a validar
 * @return bool True si el formato es válido
 */
function validarFormatoCodigo($codigo) {
    return preg_match('/^003-GS\d+-\d{3}$/', $codigo) === 1;
}

/**
 * Obtiene información del código (tabla, número secuencial)
 * @param string $codigo Código a analizar
 * @return array|null Array con información o null si no es válido
 */
function analizarCodigo($codigo) {
    if (!validarFormatoCodigo($codigo)) {
        return null;
    }
    
    if (preg_match('/^003-(GS\d+)-(\d{3})$/', $codigo, $m)) {
        $prefijo = $m[1];
        $numero = intval($m[2]);
        
        // Mapear prefijo a tabla
        $tablasMap = [
            'GS1' => 'maquinas',
            'GS2' => 'herramientas_manuales',
            'GS3' => 'herramientas_equipo_jardineria',
            'GS4' => 'equipo_seguridad',
            'GS5' => 'habitacion_huesped_betel',
            'GS6' => 'items_generales_por_edificio'
        ];
        
        return [
            'prefijo' => $prefijo,
            'numero' => $numero,
            'tabla' => $tablasMap[$prefijo] ?? 'unknown'
        ];
    }
    
    return null;
}

/**
 * Obtiene ejemplos de códigos para documentación
 * @return array Array con ejemplos por tabla
 */
function obtenerEjemplosCodigos() {
    return [
        'maquinas' => ['003-GS1-001', '003-GS1-002', '003-GS1-003'],
        'herramientas_manuales' => ['003-GS2-001', '003-GS2-002', '003-GS2-003'],
        'herramientas_equipo_jardineria' => ['003-GS3-001', '003-GS3-002', '003-GS3-003'],
        'equipo_seguridad' => ['003-GS4-001', '003-GS4-002', '003-GS4-003'],
        'habitacion_huesped_betel' => ['003-GS5-001', '003-GS5-002', '003-GS5-003'],
        'items_generales_por_edificio' => ['003-GS6-001', '003-GS6-002', '003-GS6-003']
    ];
}
?>
