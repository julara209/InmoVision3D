<?php
/**
 * Dashboard de Administrador - InmoVision3D
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Inmueble.php';
require_once __DIR__ . '/../../models/Solicitud.php';

// Verificar autenticación y rol
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    header('Location: ' . BASE_URL . 'views/auth/login.php');
    exit;
}

$usuarioModel = new Usuario();
$inmuebleModel = new Inmueble();
$solicitudModel = new Solicitud();

// Obtener estadísticas
$totalUsuarios = count($usuarioModel->listarTodos());
$totalInmuebles = count($inmuebleModel->listarTodos());
$solicitudesPendientes = count($solicitudModel->obtenerPendientes());

// Obtener usuarios recientes
$usuariosRecientes = array_slice($usuarioModel->listarTodos(), 0, 5);
$inmueblesRecientes = array_slice($inmuebleModel->listarTodos(), 0, 5);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administracion - InmoVision3D</title>
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
            <a href="<?php echo BASE_URL; ?>views/admin/dashboard.php" class="nav-item active">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/usuarios.php" class="nav-item">
                <i class="fas fa-users"></i> Usuarios
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/inmuebles.php" class="nav-item">
                <i class="fas fa-home"></i> Inmuebles
            </a>
            <a href="<?php echo BASE_URL; ?>views/admin/solicitudes.php" class="nav-item">
                <i class="fas fa-envelope"></i> Solicitudes
                <?php if ($solicitudesPendientes > 0): ?>
                <span class="badge-count"><?php echo $solicitudesPendientes; ?></span>
                <?php endif; ?>
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
                <h1>Dashboard</h1>
            </div>
            <div class="header-right">
                <span class="admin-user">
                    <i class="fas fa-user-shield"></i>
                    <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                </span>
            </div>
        </header>

        <div class="admin-content">
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalUsuarios; ?></h3>
                        <p>Usuarios Totales</p>
                    </div>
                </div>
                <div class="stat-card stat-success">
                    <div class="stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalInmuebles; ?></h3>
                        <p>Inmuebles Publicados</p>
                    </div>
                </div>
                <div class="stat-card stat-warning">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $solicitudesPendientes; ?></h3>
                        <p>Solicitudes Pendientes</p>
                    </div>
                </div>
                <div class="stat-card stat-info">
                    <div class="stat-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="planos3d-count">0</h3>
                        <p>Planos 3D</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="admin-grid">
                <!-- Recent Users -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Usuarios Recientes</h3>
                        <a href="<?php echo BASE_URL; ?>views/admin/usuarios.php" class="btn-link">Ver todos</a>
                    </div>
                    <div class="card-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuariosRecientes as $usuario): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <span class="user-name"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($usuario['email']); ?></span>
                                        </div>
                                    </td>
                                    <td><span class="badge-rol rol-<?php echo $usuario['rol']; ?>"><?php echo ucfirst($usuario['rol']); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="btn-icon" onclick="editarUsuario(<?php echo $usuario['id']; ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon btn-danger" onclick="eliminarUsuario(<?php echo $usuario['id']; ?>)" title="Eliminar">
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

                <!-- Recent Properties -->
                <div class="admin-card">
                    <div class="card-header">
                        <h3><i class="fas fa-home"></i> Inmuebles Recientes</h3>
                        <a href="<?php echo BASE_URL; ?>views/admin/inmuebles.php" class="btn-link">Ver todos</a>
                    </div>
                    <div class="card-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Inmueble</th>
                                    <th>Tipo</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inmueblesRecientes as $inmueble): ?>
                                <tr>
                                    <td>
                                        <div class="inmueble-info">
                                            <span class="inmueble-titulo"><?php echo htmlspecialchars($inmueble['titulo']); ?></span>
                                            <span class="inmueble-ubicacion"><?php echo htmlspecialchars($inmueble['ciudad']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo ucfirst($inmueble['tipo']); ?></td>
                                    <td>$<?php echo number_format($inmueble['precio'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge-estado estado-<?php echo $inmueble['activo'] ? 'activo' : 'inactivo'; ?>">
                                            <?php echo $inmueble['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> Acciones Rapidas</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="<?php echo BASE_URL; ?>views/admin/usuarios.php?action=nuevo" class="quick-action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Nuevo Usuario</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>views/inmuebles/publicar.php" class="quick-action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <span>Nuevo Inmueble</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>views/admin/solicitudes.php" class="quick-action-btn">
                            <i class="fas fa-envelope-open"></i>
                            <span>Ver Solicitudes</span>
                        </a>
                        <a href="<?php echo BASE_URL; ?>views/admin/reportes.php" class="quick-action-btn">
                            <i class="fas fa-file-pdf"></i>
                            <span>Generar Reporte</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/admin.js"></script>
</body>
</html>
