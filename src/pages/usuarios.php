<?php
/**
 * Usuarios - Vista de gestión de usuarios del sistema
 *
 * Descripción:
 * Muestra una lista de todos los usuarios registrados en el sistema con capacidad para visualizar,
 * editar y eliminar (según permisos). Incluye filtrado, paginación y búsqueda mediante DataTables.
 *
 * Funcionalidades:
 * - Listado completo de usuarios con sus datos principales.
 * - Filtrado y búsqueda avanzada con DataTables.
 * - Visualización del estado de actividad y última conexión.
 * - Botón para agregar nuevos usuarios.
 * - Acciones por usuario (visualizar, editar, eliminar) según permisos.
 * - Confirmación en dos pasos para eliminación de usuarios.
 * - Conteo de usuarios registrados.
 *
 * Variables principales:
 * - $row: Array con datos de cada usuario (voluntario, nombre, rol, estado, etc.).
 * - $t: Contador de usuarios registrados.
 * - $fechaConexion: Fecha formateada de última conexión del usuario.
 *
 * Dependencias:
 * - DataTables (JS y CSS) para tablas interactivas.
 * - Bootstrap (JS y CSS) para estilos y componentes.
 * - SweetAlert2 para diálogos de confirmación.
 * - session_check.php para verificación de sesión.
 * - verificar_rol.php para control de acceso.
 * - dbconn.php para conexión a base de datos.
 *
 * Seguridad:
 * - Requiere sesión activa y verificación de rol.
 * - Escapa todos los valores mostrados con htmlspecialchars.
 * - Restringe acciones de eliminación a roles específicos (Admin/Super User).
 * - Validación en dos pasos para eliminación de usuarios.
 */
require './../layout/head.html';
include('./../utils/verificar_rol.php');
?>
    <title>SAM assistant</title>
    </head>
<body>
<?php
require './../layout/header.php';
require './../utils/session_check.php';
?>

<main class="container-fluid mt-3">
    <?php require_once './../utils/breadcrumbs.php';
    $breadcrumbs = [
        ['label' => 'Inicio', 'url' => '/src/pages/admin.php'],
        ['label' => 'Usuarios', 'url' => null]
    ];
    render_breadcrumbs($breadcrumbs, '/');
    ?>

    <header>
        <div class="w-100 mb-2 p-1 bg-plomo h5">
            <div class="d-flex justify-content-between align-items-center">
                <span>USUARIOS REGISTRADOS</span>
                <a href="form_usuario.php" class="btn btn-dark text-white">
                    <i class="bi bi-plus-circle"></i> Agregar Usuario
                </a>
            </div>
        </div>
    </header>

    <div class="table-responsive">
        <table class="table w-100 roundedTable table-bordered rounded-corners" id="tabla-usuarios">
            <thead>
                <tr>
                    <th>VOLUNTARIO</th>
                    <th>NOMBRE</th>
                    <th>ROL</th>
                    <th>ACTIVO</th>
                    <th>ULTIMA CONEXION</th>
                    <th>ACCIONES</th>
                </tr>
            </thead>
            <tbody>
<?php
require_once './../utils/session_check.php';
require './../db/dbconn.php';

try {
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $t = 0;
    
    $stmt = $conn->prepare("SELECT * FROM user JOIN roles ON roles.idroles=user.rol 
     and roles.idroles not like 6 order by voluntario asc");
    $stmt->execute();
    $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
    
    foreach ($stmt->fetchAll() as $row) {
        $dateString = $row['ultimaconn'];
        $fechaConexion = empty($dateString) ? 
            "Conexión aún no establecida" : 
            (new DateTime($dateString))->format('d-m-Y H:i:s');
        
        echo "<tr>
                <td>".htmlspecialchars($row['voluntario'])."</td>
                <td>".htmlspecialchars($row['nome'])."</td>
                <td>".htmlspecialchars($row['rol'])."</td>
                <td>".htmlspecialchars($row['activo'])."</td>
                <td>".$fechaConexion."</td>
                <td>
                    <a href='form_usuario.php?voluntario=".urlencode($row['voluntario'])."&mode=view' class='btn btn-sm btn-info' title='Visualizar'>
    <i class='bi bi-eye'></i>
</a>";
        
        // Mostrar botón Eliminar solo para Administrador y Super User
        if ($_SESSION['rol'] == '1' || $_SESSION['rol'] == '6') {
            echo "<button class='btn btn-sm btn-danger btn-eliminar-usuario ms-1' 
        data-voluntario='".trim($row['voluntario'])."'
        title='Eliminar'>
        <i class='bi bi-trash'></i>
    </button>";
        }
        
        echo "</td>
              </tr>";
        $t++;
    }
} catch(PDOException $e) {
    echo "<tr><td colspan='6'>Error: ".htmlspecialchars($e->getMessage())."</td></tr>";
}
?>
            </tbody>
        </table>
    </div>

    <div class="container-fluid m-3 pl-4">
        <div class="alert alert-info">
            Voluntarios Registrados en el Sistema: <strong><?php echo $t; ?></strong>
        </div>
    </div>
</main>

<!-- JS y Footer -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $('#tabla-usuarios').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/es_es.json"
            },
            responsive: true,
            searching: true,
            paging: true,
            "dom": '<"top"lf>rt<"bottom"ip><"clear">'
        });

$('.btn-eliminar-usuario').click(function(e) {
    e.preventDefault();
    const voluntario = $(this).data('voluntario').toString().trim();
    
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Estás por eliminar al usuario ${voluntario}. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Confirmación final',
                text: `Escribe "${voluntario}" para confirmar la eliminación:`,
                input: 'text',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Confirmar eliminación',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debes escribir el código para continuar';
                    }
                    if (value.toString().trim() !== voluntario.toString().trim()) {
                        return 'Debes escribir exactamente el código de voluntario para confirmar';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `eliminar_usuario.php?voluntario=${encodeURIComponent(voluntario)}`;
                }
            });
        }
    });
});
    });
</script>

<?php require './../layout/footer.htm'; ?>
</body>
</html>