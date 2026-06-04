<?php
/**
 * Gestion de Usuarios - Admin InmoVision3D
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Usuario.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ' . BASE_URL . 'views/auth/login.php');
    exit;
}

$usuarioModel = new Usuario();
$usuarios = $usuarioModel->listarTodos();

$mensaje = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear') {
        $datos = [
            'nombre' => trim($_POST['nombre']),
            'email' => trim($_POST['email']),
            'password' => $_POST['password'],
            'rol' => $_POST['rol'],
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? '')
        ];
        
        if ($usuarioModel->crear($datos)) {
            $mensaje = 'Usuario creado correctamente';
            $usuarios = $usuarioModel->listarTodos();
        } else {
            $error = 'Error al crear el usuario. El email podria estar en uso.';
        }
    } elseif ($action === 'actualizar') {
        $id = intval($_POST['id']);
        $datos = [
            'nombre' => trim($_POST['nombre']),
            'email' => trim($_POST['email']),
            'rol' => $_POST['rol'],
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        if (!empty($_POST['password'])) {
            $datos['password'] = $_POST['password'];
        }
        
        if ($usuarioModel->actualizar($id, $datos)) {
            $mensaje = 'Usuario actualizado correctamente';
            $usuarios = $usuarioModel->listarTodos();
        } else {
            $error = 'Error al actualizar el usuario';
        }
    } elseif ($action === 'eliminar') {
        $id = intval($_POST['id']);
        if ($id !== $_SESSION['usuario_id']) {
            if ($usuarioModel->eliminar($id)) {
                $mensaje = 'Usuario eliminado correctamente';
                $usuarios = $usuarioModel->listarTodos();
            } else {
                $error = 'Error al eliminar el usuario';
            }
        } else {
            $error = 'No puedes eliminar tu propia cuenta';
        }
    }
}

$showModal = isset($_GET['action']) && $_GET['action'] === 'nuevo';
$editUsuario = null;
if (isset($_GET['edit'])) {
    $editUsuario = $usuarioModel->obtenerPorId(intval($_GET['edit']));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Usuarios - InmoVision3D</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                <i class="fas fa-building"></i>
                <span>InmoVision<span class="highlight">3D</span></span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="<?php echo BASE_URL; ?>views/admin/dashboard.php" class="nav-item">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/usuarios.php" class="nav-item active">
                <i class="fas fa-users"></i> Usuarios
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/inmuebles.php" class="nav-item">
                <i class="fas fa-home"></i> Inmuebles
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/solicitudes.php" class="nav-item">
                <i class="fas fa-envelope"></i> Solicitudes
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/categorias.php" class="nav-item">
                <i class="fas fa-tags"></i> Categorias
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/reportes.php" class="nav-item">
                <i class="fas fa-chart-bar"></i> Reportes
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/configuracion.php" class="nav-item">
                <i class="fas fa-cog"></i> Configuracion
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="<?php echo BASE_URL; ?>index.php" class="nav-item">
                <i class="fas fa-arrow-left"></i> Volver al Sitio
            </a>
            <a href="<?php echo BASE_URL; ?>controllers/AuthController.php?action=logout" class="nav-item">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesion
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Gestion de Usuarios</h1>
            </div>
            <div class="header-right">
                <button class="btn-primary" onclick="openModal('modal-usuario')">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </button>
            </div>
        </header>

        <div class="admin-content">
            <?php if ($mensaje): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $mensaje; ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="admin-card">
                <div class="card-body">
                    <div class="filters-row">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="search-usuarios" placeholder="Buscar usuarios..." onkeyup="filtrarUsuarios()">
                        </div>
                        <div class="filter-group">
                            <select id="filter-rol" onchange="filtrarUsuarios()">
                                <option value="">Todos los roles</option>
                                <option value="cliente">Cliente</option>
                                <option value="publicador">Publicador</option>
                                <option value="administrador">Administrador</option>
                            </select>
                            <select id="filter-estado" onchange="filtrarUsuarios()">
                                <option value="">Todos los estados</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de usuarios -->
            <div class="admin-card">
                <div class="card-body">
                    <table class="admin-table" id="tabla-usuarios">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr data-rol="<?php echo $usuario['rol']; ?>" data-activo="<?php echo $usuario['activo']; ?>">
                                <td><?php echo $usuario['id']; ?></td>
                                <td>
                                    <div class="user-info">
                                        <span class="user-name"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                                        <span class="user-tel"><?php echo htmlspecialchars($usuario['telefono'] ?? 'Sin telefono'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td><span class="badge-rol rol-<?php echo $usuario['rol']; ?>"><?php echo ucfirst($usuario['rol']); ?></span></td>
                                <td>
                                    <span class="badge-estado estado-<?php echo $usuario['activo'] ? 'activo' : 'inactivo'; ?>">
                                        <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn-icon" onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($usuario['id'] !== $_SESSION['usuario_id']): ?>
                                        <button class="btn-icon btn-danger" onclick="confirmarEliminar(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre']); ?>')" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Nuevo/Editar Usuario -->
    <div class="modal" id="modal-usuario">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-titulo"><i class="fas fa-user-plus"></i> Nuevo Usuario</h3>
                <button class="modal-close" onclick="closeModal('modal-usuario')">&times;</button>
            </div>
            <form id="form-usuario" method="POST">
                <input type="hidden" name="action" id="form-action" value="crear">
                <input type="hidden" name="id" id="usuario-id">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre Completo *</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Correo Electronico *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Contrasena <span id="pass-required">*</span></label>
                            <input type="password" id="password" name="password">
                            <small id="pass-hint" style="display:none;">Dejar vacio para mantener la actual</small>
                        </div>
                        <div class="form-group">
                            <label for="rol">Rol *</label>
                            <select id="rol" name="rol" required>
                                <option value="cliente">Cliente</option>
                                <option value="publicador">Publicador</option>
                                <option value="administrador">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telefono">Telefono</label>
                            <input type="tel" id="telefono" name="telefono">
                        </div>
                        <div class="form-group">
                            <label for="direccion">Direccion</label>
                            <input type="text" id="direccion" name="direccion">
                        </div>
                    </div>
                    <div class="form-group" id="grupo-activo" style="display:none;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="activo" id="activo" checked>
                            Usuario Activo
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('modal-usuario')">Cancelar</button>
                    <button type="submit" class="btn-primary" id="btn-guardar">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmar Eliminar -->
    <div class="modal" id="modal-eliminar">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminacion</h3>
                <button class="modal-close" onclick="closeModal('modal-eliminar')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="eliminar">
                <input type="hidden" name="id" id="eliminar-id">
                <div class="modal-body">
                    <p>Esta seguro de que desea eliminar al usuario <strong id="eliminar-nombre"></strong>?</p>
                    <p class="text-warning">Esta accion no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal('modal-eliminar')">Cancelar</button>
                    <button type="submit" class="btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
    <script>
    function editarUsuario(usuario) {
        document.getElementById('modal-titulo').innerHTML = '<i class="fas fa-user-edit"></i> Editar Usuario';
        document.getElementById('form-action').value = 'actualizar';
        document.getElementById('usuario-id').value = usuario.id;
        document.getElementById('nombre').value = usuario.nombre;
        document.getElementById('email').value = usuario.email;
        document.getElementById('rol').value = usuario.rol;
        document.getElementById('telefono').value = usuario.telefono || '';
        document.getElementById('direccion').value = usuario.direccion || '';
        document.getElementById('activo').checked = usuario.activo == 1;
        document.getElementById('password').removeAttribute('required');
        document.getElementById('pass-required').style.display = 'none';
        document.getElementById('pass-hint').style.display = 'block';
        document.getElementById('grupo-activo').style.display = 'block';
        openModal('modal-usuario');
    }

    function confirmarEliminar(id, nombre) {
        document.getElementById('eliminar-id').value = id;
        document.getElementById('eliminar-nombre').textContent = nombre;
        openModal('modal-eliminar');
    }

    function filtrarUsuarios() {
        const search = document.getElementById('search-usuarios').value.toLowerCase();
        const rol = document.getElementById('filter-rol').value;
        const estado = document.getElementById('filter-estado').value;
        const rows = document.querySelectorAll('#tabla-usuarios tbody tr');

        rows.forEach(row => {
            const texto = row.textContent.toLowerCase();
            const rowRol = row.dataset.rol;
            const rowActivo = row.dataset.activo;

            const matchSearch = texto.includes(search);
            const matchRol = !rol || rowRol === rol;
            const matchEstado = !estado || rowActivo === estado;

            row.style.display = matchSearch && matchRol && matchEstado ? '' : 'none';
        });
    }

    // Reset form on open new
    document.querySelector('.btn-primary[onclick="openModal(\'modal-usuario\')"]').addEventListener('click', function() {
        document.getElementById('modal-titulo').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Usuario';
        document.getElementById('form-action').value = 'crear';
        document.getElementById('form-usuario').reset();
        document.getElementById('usuario-id').value = '';
        document.getElementById('password').setAttribute('required', 'required');
        document.getElementById('pass-required').style.display = 'inline';
        document.getElementById('pass-hint').style.display = 'none';
        document.getElementById('grupo-activo').style.display = 'none';
    });

    <?php if ($showModal): ?>
    openModal('modal-usuario');
    <?php endif; ?>
    </script>
</body>
</html>
