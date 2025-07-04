<?php 
/**
 * Página de Inicio de Sesión
 * 
 * Página principal de autenticación para el sistema SAM Assistant.
 * Maneja la autenticación de usuarios, validación de credenciales,
 * gestión de sesiones y redirección según el rol del usuario.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

session_start();
require './../../layout/head.html';
require './../../layout/header.php';
require './../../db/dbconn.php';

function ejecutar_query($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

// Función para manejar errores y mensajes
function setMessage(&$var, $message) {
    $var = $message;
}

// Inicializar variables
$loginError = $logoutMessage = '';

// Manejar mensajes de sesión expirada y cierre de sesión
if (isset($_GET['expired'])) {
    setMessage($loginError, "Tu sesión ha expirado. Por favor, inicia sesión de nuevo.");
} elseif (isset($_GET['logout']) && $_GET['logout'] == 1) {
    setMessage($logoutMessage, "Has cerrado sesión correctamente.");
}

// Función para autenticar al usuario
function authenticateUser($userLogin, $userPassword, $conn) {
    $stmt = $conn->prepare("
        SELECT u.*, r.codigo AS rol_codigo
        FROM user u
        LEFT JOIN roles r ON u.rol = r.idroles
        WHERE u.voluntario = :username
    ");
    $stmt->bindParam(':username', $userLogin);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Función para iniciar sesión
function login($user) {
    $_SESSION['user_id'] = $user['voluntario'];
    $_SESSION['username'] = $user['nome'] ?? $user['voluntario'];
    $_SESSION['rol'] = $user['rol'] ?? '';
    $_SESSION['codigo'] = $user['rol_codigo'] ?? '';
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['message'] = $user['message'];
    
    $displayName = $user['rol_codigo'] ? $user['rol_codigo'] . ' ' . ($user['nome'] ?? $user['voluntario']) : ($user['nome'] ?? $user['voluntario']);
    $_SESSION['display_name'] = $displayName;

}

// Procesar el formulario de login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userLogin = $_POST['username'];
    $userPassword = $_POST['password'];
    
    try {
        $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $user = authenticateUser($userLogin, $userPassword, $conn);
        
        if ($user && $user['pwd'] === $userPassword) {
            if ($user['activo'] === 'si') {
                $id_usuario = $user['voluntario'];
                //logica para insertar ultima conexion del usuario
                $query_ultima_conexion = "UPDATE user SET ultimaconn = NOW() WHERE voluntario = ?";
                ejecutar_query($conn, $query_ultima_conexion, [$id_usuario]);

                login($user);
                header("Location: ./../../utils/Redirect.php");
                exit();
            } else {
                setMessage($loginError, "Cuenta inactiva. Por favor, contacte al administrador.");
            }
        } else {
            setMessage($loginError, "Usuario o contraseña incorrectos");
        }
    } catch(PDOException $e) {
        setMessage($loginError, "Error de conexión: " . $e->getMessage());
    }
    $conn = null;
}
?>
</head>
<main>
    <div class="login-form">
        <img src="/public/ico/logoSAM.png" alt="Logo SAM" style="width: 270px;">
        <h2>Login</h2>

        <?php if ($loginError): ?>
            <p class="alert alert-warning"><?php echo htmlspecialchars($loginError); ?></p>
        <?php endif; ?>
        <?php if ($logoutMessage): ?>
            <p class="alert alert-success"><?php echo htmlspecialchars($logoutMessage); ?></p>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
<div class="mb-3">
    <label for="password" class="form-label">Contraseña</label>
    <div class="input-group" style="align-items: center;">
        <input type="password" id="password" name="password" class="form-control" required 
               style="height: 50px; border-right: none; font-size: 1rem;">
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" 
                id="togglePassword" 
                style="height: 50px; width: 46px; border-left: none; border-radius: 0 0.375rem 0.375rem 0; padding: 0;">
            <i class="bi bi-eye" style="font-size: 1.1rem;"></i>
        </button>
    </div>
</div>
            <input type="submit" class="mb-2" value="Iniciar sesión">
        </form>
        <a href="#" onclick="showForgotPasswordAlert()">Olvidé mi contraseña</a>
    </div>
</main>

<!-- SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Mostrar/ocultar contraseña
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordField = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});

// Función para recuperación de contraseña (existente)
function showForgotPasswordAlert() {
    Swal.fire({
        title: 'Recuperación de contraseña',
        text: 'Póngase en contacto con el Administrador o el Capitán del día',
        icon: 'info',
        confirmButtonText: 'Entendido'
    });
}
</script>

<?php require './../../layout/footer.htm';?>

</body>
</html>