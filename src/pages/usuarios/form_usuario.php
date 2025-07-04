<?php
/**
 * Formulario de Usuario - Interfaz para crear/editar/visualizar usuarios
 *
 * Descripción:
 * Proporciona un formulario interactivo para la creación, edición y visualización de usuarios.
 * Maneja tres modos: creación (nuevo usuario), edición y visualización (solo lectura).
 *
 * Funcionalidades:
 * - Creación de nuevos usuarios con generación automática de contraseña.
 * - Edición de usuarios existentes (con opción de mantener o cambiar contraseña).
 * - Visualización de datos de usuario en modo solo lectura.
 * - Generación inteligente de contraseñas basada en ID + iniciales del nombre.
 * - Selector de roles con restricciones (excluye rol 6).
 * - Toggle para mostrar/ocultar contraseña en modo visualización.
 * - Validación básica de campos obligatorios.
 *
 * Variables principales:
 * - $mode: Determina el modo de operación (view/edit).
 * - $isViewMode: Flag para verificar si está en modo visualización.
 * - $roles: Array con los roles disponibles para asignar.
 * - $voluntario: ID del voluntario (para edición/visualización).
 * - $user: Array con los datos del usuario (en modo edición/visualización).
 *
 * Dependencias:
 * - Bootstrap (JS y CSS) para estilos y componentes.
 * - SweetAlert2 para notificaciones interactivas.
 * - session_check.php para verificación de sesión.
 * - dbconn.php para conexión a base de datos.
 *
 * Seguridad:
 * - Requiere sesión activa.
 * - Escapa todos los valores mostrados con htmlspecialchars.
 * - Manejo diferenciado de contraseñas (hash en visualización).
 * - Campos sensibles protegidos en modo visualización.
 * - Validación de datos antes de generar contraseñas.
 */
require './../../layout/head.html';
require './../../layout/header.php';
require './../../utils/session_check.php';
require_once './../../db/dbconn.php';

// Get mode (view or edit)
$mode = $_GET['mode'] ?? 'edit'; // Por defecto edit para nuevos registros
$isViewMode = ($mode === 'view');

// Get roles for dropdown
$roles = [];
try {
    $stmt = $conn->prepare("SELECT * FROM roles WHERE idroles NOT LIKE 6");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error al obtener roles: " . $e->getMessage());
}

// Check if editing existing user
$voluntario = $_GET['voluntario'] ?? '';
$user = null;

if ($voluntario) {
    try {
        $stmt = $conn->prepare("SELECT * FROM user WHERE voluntario = ?");
        $stmt->execute([$voluntario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        die("Error al obtener usuario: " . $e->getMessage());
    }
}

// Asegurarse que los campos no tengan valores por defecto no deseados
if (!$voluntario) {
    $user = [
        'voluntario' => '',
        'nome' => '',
        'rol' => '',
        'activo' => 'si',
        'pwd' => ''
    ];
}
?>

<main class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><?php echo $voluntario ? ($isViewMode ? 'Visualizar Usuario' : 'Editar Usuario') : 'Nuevo Usuario'; ?></h4>
        <?php if ($isViewMode): ?>
            <a href="form_usuario.php?voluntario=<?php echo urlencode($voluntario); ?>&mode=edit" class="btn btn-warning">
                <i class="bi bi-pencil-square"></i> Editar
            </a>
        <?php endif; ?>
    </div>
    
    <form action="guardar_usuario.php" method="post">
        <?php if ($voluntario): ?>
            <input type="hidden" name="voluntario_original" value="<?php echo htmlspecialchars($voluntario); ?>">
        <?php endif; ?>
        
        <div class="mb-3">
            <label class="form-label">Voluntario</label>
            <input type="text" class="form-control" name="voluntario" id="voluntario" 
                value="<?php echo htmlspecialchars($user['voluntario']); ?>" 
                <?php echo $isViewMode ? 'readonly' : 'required'; ?>>
        </div>

        <div class="mb-3">
            <label class="form-label">Nombre Completo</label>
            <input type="text" class="form-control" name="nome" id="nome" 
                value="<?php echo htmlspecialchars($user['nome']); ?>" 
                <?php echo $isViewMode ? 'readonly' : 'required'; ?>>
        </div>

        <?php if ($isViewMode): ?>
            <!-- Mostrar contraseña en modo visualización -->
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="pwd-view" 
                        value="<?php echo htmlspecialchars($user['pwd'] ?? 'No disponible'); ?>" readonly>
                    <button type="button" class="btn btn-outline-secondary" id="toggle-pwd-view">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <small class="text-muted">La contraseña se muestra hasheada por seguridad</small>
            </div>
        <?php else: ?>
            <!-- Campo de contraseña en modo edición -->
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="text" class="form-control" name="pwd" id="pwd" 
                        value="<?php echo $voluntario ? '' : htmlspecialchars($user['pwd'] ?? ''); ?>">
                    <button type="button" class="btn btn-outline-secondary" id="generar-pwd">
                        <i class="bi bi-key"></i> Generar
                    </button>
                </div>
                <?php if ($voluntario): ?>
                    <small class="text-muted">Dejar en blanco para mantener la contraseña actual</small>
                <?php else: ?>
                    <small class="text-muted">La contraseña se generará automáticamente</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="mb-3">
            <label class="form-label">Rol</label>
            <select class="form-select" name="rol" <?php echo $isViewMode ? 'disabled' : 'required'; ?>>
                <option value="">-- Seleccione un rol --</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo htmlspecialchars($role['idroles']); ?>" 
                        <?php if ($user['rol'] == $role['idroles']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($role['rol']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($isViewMode): ?>
                <input type="hidden" name="rol" value="<?php echo htmlspecialchars($user['rol']); ?>">
            <?php endif; ?>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="activo" <?php echo $isViewMode ? 'disabled' : 'required'; ?>>
                <option value="si" <?php if ($user['activo'] == 'si') echo 'selected'; ?>>Activo</option>
                <option value="no" <?php if ($user['activo'] == 'no') echo 'selected'; ?>>Inactivo</option>
            </select>
            <?php if ($isViewMode): ?>
                <input type="hidden" name="activo" value="<?php echo htmlspecialchars($user['activo']); ?>">
            <?php endif; ?>
        </div>
        
        <?php if (!$isViewMode): ?>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">Guardar</button>
                <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
            </div>
        <?php else: ?>
            <a href="usuarios.php" class="btn btn-secondary">Volver</a>
        <?php endif; ?>
    </form>
</main>

<script>
// Generador de contraseña automático
document.getElementById('generar-pwd')?.addEventListener('click', function() {
    const voluntario = document.getElementById('voluntario').value.trim();
    const nome = document.getElementById('nome').value.trim();
    
    if (!voluntario || !nome) {
        Swal.fire({
            icon: 'error',
            title: 'Datos incompletos',
            text: 'Complete el código de voluntario y nombre completo primero',
            timer: 2000
        });
        return;
    }
    
    // Obtener partes del nombre
    const partes = nome.split(' ').filter(p => p.trim() !== '');
    
    if (partes.length < 2) {
        Swal.fire({
            icon: 'error',
            title: 'Nombre incompleto',
            text: 'Debe ingresar al menos un nombre y un apellido',
            timer: 2000
        });
        return;
    }
    
    // Primera letra del primer nombre (partes[0])
    const inicialNombre = partes[0].charAt(0).toUpperCase();
    
    // Primera letra del apellido (última parte)
    const inicialApellido = partes[partes.length - 1].charAt(0).toUpperCase();
    
    // Generar contraseña
    const pwd = voluntario + inicialNombre + inicialApellido;
    document.getElementById('pwd').value = pwd;
    
    // Mostrar notificación
    Swal.fire({
        icon: 'success',
        title: 'Contraseña generada',
        html: `Contraseña creada: <strong>${pwd}</strong><br>
               <small>Formato: ID + ${inicialNombre} (nombre) + ${inicialApellido} (apellido)</small>`,
        timer: 3000
    });
});

// Mostrar/ocultar contraseña en modo visualización
document.getElementById('toggle-pwd-view')?.addEventListener('click', function() {
    const pwdField = document.getElementById('pwd-view');
    const icon = this.querySelector('i');
    
    if (pwdField.type === 'password') {
        pwdField.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        pwdField.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});
</script>

<?php require './../../layout/footer.htm'; ?>