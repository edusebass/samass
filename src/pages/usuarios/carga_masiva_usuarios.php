<?php
/**
 * Carga Masiva de Usuarios - Importación desde Excel
 * 
 * Permite cargar usuarios masivamente desde archivos Excel.
 * Procesa la hoja de usuarios y valida los datos antes de insertarlos.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

require './../../layout/head.html';
require './../../layout/header.php';
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Mapeo de columnas del Excel
$columnas_usuarios = [
    'voluntario' => 'A', // Número de identificación
    'nome' => 'B',       // Nombre
    'segundo_nombre' => 'C', // Segundo nombre
    'apellidos' => 'D',  // Apellidos
    'password' => 'E',   // Contraseña
    'rol_nombre' => 'F', // Rol
    'activo' => 'G'      // Estado
];

// Obtener roles dinámicamente de la base de datos
function obtenerRolesDisponibles($conn) {
    try {
        $stmt = $conn->prepare("SELECT idroles, rol FROM roles WHERE idroles != 6 ORDER BY idroles");
        $stmt->execute();
        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[$row['rol']] = $row['idroles'];
        }
        return $roles;
    } catch (Exception $e) {
        // Fallback a mapeo estático si falla la consulta
        return [
            'Administración' => '1',
            'Bodega' => '2', 
            'Capitán' => '3',
            'Representante' => '4',
            'Comité Asamblea' => '5',
            'Desarrollo' => '7',
            'Voluntario' => '8'
        ];
    }
}

// Mapeo de roles - usando los IDs de la tabla roles
$roles_mapeo = obtenerRolesDisponibles($conn);

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_usuarios'])) {
    try {
        $archivo = $_FILES['archivo_usuarios'];
        
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo');
        }
        
        // Verificar que sea un archivo Excel
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($extension), ['xlsx', 'xls'])) {
            throw new Exception('El archivo debe ser un Excel (.xlsx o .xls)');
        }
        
        // Cargar el archivo
        $spreadsheet = IOFactory::load($archivo['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        $usuarios_insertados = 0;
        $errores = [];
        
        // Procesar filas (empezar desde la fila 2, omitir encabezados)
        for ($row = 2; $row <= $highestRow; $row++) {
            try {
                // Leer datos de la fila
                $voluntario = trim($worksheet->getCell('A' . $row)->getCalculatedValue());
                $nombre = trim($worksheet->getCell('B' . $row)->getCalculatedValue());
                $segundo_nombre = trim($worksheet->getCell('C' . $row)->getCalculatedValue());
                $apellidos = trim($worksheet->getCell('D' . $row)->getCalculatedValue());
                $password = trim($worksheet->getCell('E' . $row)->getCalculatedValue());
                $rol_nombre = trim($worksheet->getCell('F' . $row)->getCalculatedValue());
                $estado = trim($worksheet->getCell('G' . $row)->getCalculatedValue());
                
                // Validar datos obligatorios
                if (empty($voluntario) || empty($nombre) || empty($apellidos)) {
                    $errores[] = "Fila $row: Faltan datos obligatorios (ID, Nombre o Apellidos)";
                    continue;
                }
                
                // Mapear rol
                if (!isset($roles_mapeo[$rol_nombre])) {
                    $errores[] = "Fila $row: Rol '$rol_nombre' no válido";
                    continue;
                }
                $rol_id = $roles_mapeo[$rol_nombre];
                
                // Generar contraseña si está vacía
                if (empty($password)) {
                    // Generar contraseña automática basada en nombre y apellidos
                    // Formato: primeras 3 letras del nombre + primeras 3 letras del apellido + últimos 2 dígitos del ID
                    $nombre_limpio = preg_replace('/[^a-zA-Z]/', '', $nombre);
                    $apellido_limpio = preg_replace('/[^a-zA-Z]/', '', $apellidos);
                    
                    $parte_nombre = strtolower(substr($nombre_limpio, 0, 3));
                    $parte_apellido = strtolower(substr($apellido_limpio, 0, 3));
                    $parte_id = substr($voluntario, -2); // Últimos 2 dígitos del ID
                    
                    $password = $parte_nombre . $parte_apellido . $parte_id;
                    
                    // Si es muy corta, usar método alternativo
                    if (strlen($password) < 6) {
                        $iniciales = strtolower(substr($nombre, 0, 1) . substr($apellidos, 0, 1));
                        $password = $iniciales . $voluntario;
                    }
                }
                
                // Construir nombre completo
                $nombre_completo = $nombre;
                if (!empty($segundo_nombre)) {
                    $nombre_completo .= ' ' . $segundo_nombre;
                }
                $nombre_completo .= ' ' . $apellidos;
                
                // Convertir estado
                $activo = (strtolower($estado) === 'activo') ? 'si' : 'no';
                
                // Verificar si el usuario ya existe
                $stmt_check = $conn->prepare("SELECT voluntario FROM user WHERE voluntario = ?");
                $stmt_check->execute([$voluntario]);
                
                if ($stmt_check->rowCount() > 0) {
                    $errores[] = "Fila $row: El usuario $voluntario ya existe";
                    continue;
                }
                
                // Insertar usuario - usando los nombres correctos de columnas
                $stmt = $conn->prepare("INSERT INTO user (voluntario, nome, pwd, rol, activo) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$voluntario, $nombre_completo, $password, $rol_id, $activo]);
                
                $usuarios_insertados++;
                
            } catch (Exception $e) {
                $errores[] = "Fila $row: " . $e->getMessage();
            }
        }
        
        // Preparar mensaje de resultado
        $mensaje = "Carga completada. Usuarios insertados: $usuarios_insertados";
        if ($usuarios_insertados > 0) {
            $mensaje .= "\n\nNOTA: Las contraseñas generadas automáticamente siguen el formato: nombre(3) + apellido(3) + ID(2 últimos)";
            $mensaje .= "\nEjemplo: Emily Cauja (6361985) → Contraseña: emicau85";
        }
        if (!empty($errores)) {
            $mensaje .= "\n\nErrores encontrados:\n" . implode("\n", $errores);
        }
        $tipo_mensaje = $usuarios_insertados > 0 ? 'success' : 'warning';
        
    } catch (Exception $e) {
        $mensaje = 'Error al procesar el archivo: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}
?>

<main class="container-fluid mt-3">
    <?php require_once './../../utils/breadcrumbs.php';
    $breadcrumbs = [
        ['label' => 'Inicio', 'url' => '/src/pages/dashboard/admin.php'],
        ['label' => 'Usuarios', 'url' => './usuarios.php'],
        ['label' => 'Carga Masiva', 'url' => null]
    ];
    render_breadcrumbs($breadcrumbs, '/');
    ?>

    <header>
        <div class="w-100 mb-2 p-1 bg-plomo h5">
            <span>CARGA MASIVA DE USUARIOS</span>
        </div>
    </header>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
            <pre><?php echo htmlspecialchars($mensaje); ?></pre>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Subir Archivo de Usuarios</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="archivo_usuarios" class="form-label">Seleccionar archivo Excel</label>
                            <input type="file" class="form-control" id="archivo_usuarios" name="archivo_usuarios" 
                                   accept=".xlsx,.xls" required>
                            <div class="form-text">
                                Formatos soportados: .xlsx, .xls
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload"></i> Cargar Usuarios
                            </button>
                            <a href="usuarios.php" class="btn btn-secondary ms-2">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Instrucciones</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Descarga la plantilla de Excel</li>
                        <li>Completa los datos de los usuarios</li>
                        <li>Guarda el archivo</li>
                        <li>Sube el archivo usando el formulario</li>
                    </ol>
                    
                    <div class="mt-3">
                        <a href="/public/Formato usuarios SAM ASSISTANT.xlsx" class="btn btn-info btn-sm" download>
                            <i class="bi bi-download"></i> Descargar Plantilla
                        </a>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Columnas requeridas:</h6>
                        <ul class="small">
                            <li><strong>Número de identificación:</strong> ID único</li>
                            <li><strong>Nombre:</strong> Nombre del usuario</li>
                            <li><strong>Segundo nombre:</strong> Opcional</li>
                            <li><strong>Apellidos:</strong> Apellidos</li>
                            <li><strong>Contraseña:</strong> Si está vacía, se genera automáticamente (ej: emi + cau + 85)</li>
                            <li><strong>Rol:</strong> Administración, Bodega, Voluntario, etc.</li>
                            <li><strong>Estado:</strong> Activo / Inactivo</li>
                        </ul>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Generación automática de contraseñas:</h6>
                        <div class="small">
                            <p><strong>Formato:</strong> 3 letras del nombre + 3 letras del apellido + 2 últimos dígitos del ID</p>
                            <p><strong>Ejemplo:</strong> Emily Cauja (ID: 6361985) → Contraseña: <code>emicau85</code></p>
                            <p><em>Si no se proporciona contraseña en el Excel, se generará automáticamente</em></p>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Roles disponibles:</h6>
                        <ul class="small">
                            <?php foreach ($roles_mapeo as $rol_nombre => $rol_id): ?>
                                <li><?php echo htmlspecialchars($rol_nombre); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php require './../../layout/footer.htm'; ?>
