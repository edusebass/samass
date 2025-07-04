/**
 * Perfil de Usuario
 * 
 * Página para visualizar y gestionar el perfil del usuario autenticado.
 * Muestra información personal, rol y configuraciones del usuario.
 * 
 * @package SAM Assistant
 * @version 1.0
 * @author Sistema SAM
 */

<?php
require './../../layout/head.html';
require './../../utils/session_check.php';
?>

<?php require './../../layout/header.php'; ?>
    <title>SAM Assistant</title>
    </head>
    <body>
    <div class="container">
    <div class="content">
    <div class="bg">
        <div class="tx">
            <h1>PERFIL USUARIO</h1>
            <br>
            <ul>
        
<?php
require './../../db/dbconn.php';
unset($_SESSION['qr_content']);
unset($_SESSION['id_voluntario']);
unset($_SESSION['codigo_item']);

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $t = 0;
    $stmt = $conn->prepare("SELECT voluntario, nome, roles.rol activo FROM user left JOIN roles ON roles.idroles=user.rol 
    where activo like 'si' and voluntario like '$_SESSION[user_id]'");
    $stmt->execute();

    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    
    foreach ($stmt->fetchAll() as $row) {
        echo "<li><h4>Numero Voluntario: "
        .$row['voluntario']."</li><li><h4>Nombre y Apellido: "
        .$row['nome']."</li><li><h4>Departamento: "
        .$row['activo']."</li></ul></div>";
        $t++;}
} catch(PDOException $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage().'</div>';
}

if (isset($_SESSION['password_message'])) {
    echo '<div class="alert '.($_SESSION['password_success'] ? 'alert-success' : 'alert-danger').' alert-dismissible fade show" role="alert">'
         .$_SESSION['password_message'].
         '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    unset($_SESSION['password_message']);
    unset($_SESSION['password_success']);
}
?>

<!-- Botón para abrir modal -->
<button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#passwordModal">
    Cambiar Contraseña
</button>

<!-- Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordModalLabel">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="passwordForm" method="post">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Contraseña Actual</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn btn-outline-secondary toggle-password" type="button">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="passwordForm" class="btn btn-primary">Actualizar</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botones para mostrar/ocultar contraseña
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });

    // Manejo del formulario con SweetAlert
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Validación básica
        if (newPassword !== confirmPassword) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Las contraseñas nuevas no coinciden',
                confirmButtonColor: '#3085d6',
            });
            return;
        }

        // Mostrar carga mientras se procesa
        Swal.fire({
            title: 'Actualizando contraseña',
            html: 'Por favor espera...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Enviar con Fetch API
        const formData = new FormData(this);
        
        fetch('actualizar_password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('passwordModal'));
                modal.hide();
                
                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: data.message,
                    confirmButtonColor: '#3085d6',
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#3085d6',
                });
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al procesar la solicitud',
                confirmButtonColor: '#3085d6',
            });
        });
    });
});
</script>
<!-- JS y Footer -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</div>

<?php require './../layout/footer.htm'; ?>   

</div> 
</body>
</html>