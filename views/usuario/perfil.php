<?php
/**
 * Vista de Perfil de Usuario - InmoVision3D
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Usuario.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'views/auth/login.php');
    exit;
}

$usuarioModel = new Usuario();
$usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);

$mensaje = '';
$error = '';

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'actualizar_perfil') {
            $datos = [
                'nombre' => trim($_POST['nombre']),
                'email' => trim($_POST['email']),
                'telefono' => trim($_POST['telefono']),
                'direccion' => trim($_POST['direccion'])
            ];
            
            // Procesar avatar si se subió
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($ext, $permitidas)) {
                    $nombreArchivo = 'avatar_' . $_SESSION['usuario_id'] . '_' . time() . '.' . $ext;
                    $rutaDestino = __DIR__ . '/../../assets/uploads/avatars/' . $nombreArchivo;
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $rutaDestino)) {
                        $datos['avatar'] = $nombreArchivo;
                    }
                }
            }
            
            if ($usuarioModel->actualizar($_SESSION['usuario_id'], $datos)) {
                $_SESSION['nombre'] = $datos['nombre'];
                $mensaje = 'Perfil actualizado correctamente';
                $usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);
            } else {
                $error = 'Error al actualizar el perfil';
            }
        } elseif ($_POST['action'] === 'cambiar_password') {
            $actual = $_POST['password_actual'];
            $nueva = $_POST['password_nueva'];
            $confirmar = $_POST['password_confirmar'];
            
            if ($nueva !== $confirmar) {
                $error = 'Las contrasenas no coinciden';
            } elseif (strlen($nueva) < 6) {
                $error = 'La contrasena debe tener al menos 6 caracteres';
            } else {
                if ($usuarioModel->cambiarPassword($_SESSION['usuario_id'], $actual, $nueva)) {
                    $mensaje = 'Contrasena actualizada correctamente';
                } else {
                    $error = 'La contrasena actual es incorrecta';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - InmoVision3D</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                <i class="fas fa-building"></i>
                <span>InmoVision<span class="highlight">3D</span></span>
            </a>
            <nav class="nav-menu">
                <a href="<?php echo BASE_URL; ?>index.php">Inicio</a>
                <a href="<?php echo BASE_URL; ?>views/inmuebles/listar.php">Inmuebles</a>
                <a href="<?php echo BASE_URL; ?>views/usuario/favoritos.php">Favoritos</a>
                <?php if ($_SESSION['rol'] === 'publicador' || $_SESSION['rol'] === 'administrador'): ?>
                <a href="<?php echo BASE_URL; ?>views/inmuebles/publicar.php">Publicar</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <a href="<?php echo BASE_URL; ?>views/admin/dashboard.php">Admin</a>
                <?php endif; ?>
            </nav>
            <div class="header-actions">
                <a href="<?php echo BASE_URL; ?>views/usuario/perfil.php" class="btn-user active">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                </a>
                <a href="<?php echo BASE_URL; ?>controllers/AuthController.php?action=logout" class="btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1><i class="fas fa-user-circle"></i> Mi Perfil</h1>
                <p>Administra tu informacion personal</p>
            </div>

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

            <div class="perfil-grid">
                <!-- Información del perfil -->
                <div class="perfil-card">
                    <div class="perfil-header">
                        <div class="avatar-container">
                            <?php 
                            $avatar = !empty($usuario['avatar']) 
                                ? BASE_URL . 'assets/uploads/avatars/' . $usuario['avatar']
                                : BASE_URL . 'assets/img/default-avatar.png';
                            ?>
                            <img src="<?php echo $avatar; ?>" alt="Avatar" class="avatar-large">
                            <label for="avatar-input" class="avatar-edit">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>
                        <div class="perfil-info-header">
                            <h2><?php echo htmlspecialchars($usuario['nombre']); ?></h2>
                            <span class="badge-rol rol-<?php echo $usuario['rol']; ?>">
                                <?php echo ucfirst($usuario['rol']); ?>
                            </span>
                            <p class="perfil-email"><?php echo htmlspecialchars($usuario['email']); ?></p>
                        </div>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data" class="perfil-form">
                        <input type="hidden" name="action" value="actualizar_perfil">
                        <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display:none"
                               onchange="previewAvatar(this)">

                        <div class="form-group">
                            <label for="nombre"><i class="fas fa-user"></i> Nombre Completo</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Correo Electronico</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="telefono"><i class="fas fa-phone"></i> Telefono</label>
                            <input type="tel" id="telefono" name="telefono" 
                                   value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="direccion"><i class="fas fa-map-marker-alt"></i> Direccion</label>
                            <input type="text" id="direccion" name="direccion" 
                                   value="<?php echo htmlspecialchars($usuario['direccion'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn-primary btn-block">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </form>
                </div>

                <!-- Cambiar contraseña -->
                <div class="perfil-card">
                    <h3><i class="fas fa-lock"></i> Cambiar Contrasena</h3>
                    <form action="" method="POST" class="perfil-form">
                        <input type="hidden" name="action" value="cambiar_password">

                        <div class="form-group">
                            <label for="password_actual">Contrasena Actual</label>
                            <div class="input-password">
                                <input type="password" id="password_actual" name="password_actual" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password_actual')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_nueva">Nueva Contrasena</label>
                            <div class="input-password">
                                <input type="password" id="password_nueva" name="password_nueva" required minlength="6">
                                <button type="button" class="toggle-password" onclick="togglePassword('password_nueva')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmar">Confirmar Contrasena</label>
                            <div class="input-password">
                                <input type="password" id="password_confirmar" name="password_confirmar" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password_confirmar')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-secondary btn-block">
                            <i class="fas fa-key"></i> Cambiar Contrasena
                        </button>
                    </form>
                </div>

                <!-- Estadísticas -->
                <div class="perfil-card">
                    <h3><i class="fas fa-chart-bar"></i> Mis Estadisticas</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <i class="fas fa-heart"></i>
                            <span class="stat-value" id="stat-favoritos">0</span>
                            <span class="stat-label">Favoritos</span>
                        </div>
                        <?php if ($_SESSION['rol'] === 'publicador' || $_SESSION['rol'] === 'administrador'): ?>
                        <div class="stat-item">
                            <i class="fas fa-home"></i>
                            <span class="stat-value" id="stat-publicados">0</span>
                            <span class="stat-label">Publicados</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-envelope"></i>
                            <span class="stat-value" id="stat-solicitudes">0</span>
                            <span class="stat-label">Solicitudes</span>
                        </div>
                        <?php endif; ?>
                        <div class="stat-item">
                            <i class="fas fa-calendar"></i>
                            <span class="stat-value"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></span>
                            <span class="stat-label">Miembro desde</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <h3><i class="fas fa-building"></i> InmoVision3D</h3>
                <p>Encuentra tu hogar ideal con tecnologia 3D</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 InmoVision3D. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.querySelector('.avatar-large').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // Cargar estadísticas
    fetch('<?php echo BASE_URL; ?>api/estadisticas.php?usuario_id=<?php echo $_SESSION['usuario_id']; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('stat-favoritos').textContent = data.favoritos || 0;
                if (document.getElementById('stat-publicados')) {
                    document.getElementById('stat-publicados').textContent = data.publicados || 0;
                }
                if (document.getElementById('stat-solicitudes')) {
                    document.getElementById('stat-solicitudes').textContent = data.solicitudes || 0;
                }
            }
        });
    </script>
</body>
</html>
