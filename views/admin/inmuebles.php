<?php
/**
 * Gestion de Inmuebles - Admin InmoVision3D
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Inmueble.php';
require_once __DIR__ . '/../../models/Usuario.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ' . BASE_URL . 'views/auth/login.php');
    exit;
}

$inmuebleModel = new Inmueble();
$usuarioModel = new Usuario();
$inmuebles = $inmuebleModel->listarTodos();

$mensaje = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'eliminar') {
        $id = intval($_POST['id']);
        if ($inmuebleModel->eliminar($id)) {
            $mensaje = 'Inmueble eliminado correctamente';
            $inmuebles = $inmuebleModel->listarTodos();
        } else {
            $error = 'Error al eliminar el inmueble';
        }
    } elseif ($action === 'cambiar_estado') {
        $id = intval($_POST['id']);
        $activo = intval($_POST['activo']);
        if ($inmuebleModel->cambiarEstado($id, $activo)) {
            $mensaje = 'Estado actualizado correctamente';
            $inmuebles = $inmuebleModel->listarTodos();
        } else {
            $error = 'Error al cambiar el estado';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Inmuebles - InmoVision3D</title>
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
            <a href="<?php echo BASE_URL; ?>views/admin/usuarios.php" class="nav-item">
                <i class="fas fa-users"></i> Usuarios
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/inmuebles.php" class="nav-item active">
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
                <h1>Gestion de Inmuebles</h1>
            </div>
            <div class="header-right">
                <a href="<?php echo BASE_URL; ?>views/inmuebles/publicar.php" class="btn-primary">
                    <i class="fas fa-plus-circle"></i> Nuevo Inmueble
                </a>
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
                            <input type="text" id="search-inmuebles" placeholder="Buscar inmuebles..." onkeyup="filtrarInmuebles()">
                        </div>
                        <div class="filter-group">
                            <select id="filter-tipo" onchange="filtrarInmuebles()">
                                <option value="">Todos los tipos</option>
                                <option value="casa">Casa</option>
                                <option value="apartamento">Apartamento</option>
                                <option value="local">Local</option>
                                <option value="oficina">Oficina</option>
                                <option value="terreno">Terreno</option>
                            </select>
                            <select id="filter-estado" onchange="filtrarInmuebles()">
                                <option value="">Todos los estados</option>
                                <option value="venta">En Venta</option>
                                <option value="arriendo">En Arriendo</option>
                            </select>
                            <select id="filter-activo" onchange="filtrarInmuebles()">
                                <option value="">Todos</option>
                                <option value="1">Activos</option>
                                <option value="0">Inactivos</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de inmuebles -->
            <div class="admin-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="admin-table" id="tabla-inmuebles">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Imagen</th>
                                    <th>Titulo</th>
                                    <th>Tipo</th>
                                    <th>Ciudad</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>3D</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inmuebles as $inmueble): ?>
                                <tr data-tipo="<?php echo $inmueble['tipo']; ?>" 
                                    data-estado="<?php echo $inmueble['estado']; ?>"
                                    data-activo="<?php echo $inmueble['activo']; ?>">
                                    <td><?php echo $inmueble['id']; ?></td>
                                    <td>
                                        <?php 
                                        $imagen = !empty($inmueble['imagen_principal']) 
                                            ? BASE_URL . 'assets/uploads/inmuebles/' . $inmueble['imagen_principal']
                                            : BASE_URL . 'assets/img/no-image.jpg';
                                        ?>
                                        <img src="<?php echo $imagen; ?>" alt="" class="table-thumb">
                                    </td>
                                    <td>
                                        <div class="inmueble-info">
                                            <span class="inmueble-titulo"><?php echo htmlspecialchars($inmueble['titulo']); ?></span>
                                            <span class="inmueble-direccion"><?php echo htmlspecialchars($inmueble['direccion']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo ucfirst($inmueble['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($inmueble['ciudad']); ?></td>
                                    <td>$<?php echo number_format($inmueble['precio'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge-estado estado-<?php echo $inmueble['estado']; ?>">
                                            <?php echo $inmueble['estado'] === 'venta' ? 'Venta' : 'Arriendo'; ?>
                                        </span>
                                        <span class="badge-estado estado-<?php echo $inmueble['activo'] ? 'activo' : 'inactivo'; ?>">
                                            <?php echo $inmueble['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($inmueble['tiene_plano_3d']): ?>
                                        <span class="badge-3d"><i class="fas fa-cube"></i></span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="<?php echo BASE_URL; ?>views/inmuebles/detalle.php?id=<?php echo $inmueble['id']; ?>" 
                                               class="btn-icon" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>views/inmuebles/publicar.php?edit=<?php echo $inmueble['id']; ?>" 
                                               class="btn-icon" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn-icon <?php echo $inmueble['activo'] ? 'btn-warning' : 'btn-success'; ?>" 
                                                    onclick="cambiarEstado(<?php echo $inmueble['id']; ?>, <?php echo $inmueble['activo'] ? 0 : 1; ?>)"
                                                    title="<?php echo $inmueble['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                                <i class="fas fa-<?php echo $inmueble['activo'] ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                            <button class="btn-icon btn-danger" 
                                                    onclick="confirmarEliminar(<?php echo $inmueble['id']; ?>, '<?php echo htmlspecialchars(addslashes($inmueble['titulo'])); ?>')" 
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
                    <p>Esta seguro de que desea eliminar el inmueble <strong id="eliminar-nombre"></strong>?</p>
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

    <!-- Form oculto para cambiar estado -->
    <form id="form-estado" method="POST" style="display:none;">
        <input type="hidden" name="action" value="cambiar_estado">
        <input type="hidden" name="id" id="estado-id">
        <input type="hidden" name="activo" id="estado-activo">
    </form>

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
    <script>
    function confirmarEliminar(id, titulo) {
        document.getElementById('eliminar-id').value = id;
        document.getElementById('eliminar-nombre').textContent = titulo;
        openModal('modal-eliminar');
    }

    function cambiarEstado(id, activo) {
        document.getElementById('estado-id').value = id;
        document.getElementById('estado-activo').value = activo;
        document.getElementById('form-estado').submit();
    }

    function filtrarInmuebles() {
        const search = document.getElementById('search-inmuebles').value.toLowerCase();
        const tipo = document.getElementById('filter-tipo').value;
        const estado = document.getElementById('filter-estado').value;
        const activo = document.getElementById('filter-activo').value;
        const rows = document.querySelectorAll('#tabla-inmuebles tbody tr');

        rows.forEach(row => {
            const texto = row.textContent.toLowerCase();
            const rowTipo = row.dataset.tipo;
            const rowEstado = row.dataset.estado;
            const rowActivo = row.dataset.activo;

            const matchSearch = texto.includes(search);
            const matchTipo = !tipo || rowTipo === tipo;
            const matchEstado = !estado || rowEstado === estado;
            const matchActivo = !activo || rowActivo === activo;

            row.style.display = matchSearch && matchTipo && matchEstado && matchActivo ? '' : 'none';
        });
    }
    </script>
</body>
</html>
