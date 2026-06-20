<?php
/**
 * Vista de Perfil de Usuario - InmoVision3D
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Usuario.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . SITE_URL . '/views/auth/login.php');
    exit;
}

$usuarioModel = new Usuario();
$usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'actualizar_perfil') {
            $datos = [
                'nombre'    => trim($_POST['nombre']),
                'apellido'    => trim($_POST['apellido']),
                'correo'     => trim($_POST['email']),
                'telefono'  => trim($_POST['telefono']?? '')
            ];
            if ($usuarioModel->actualizar($_SESSION['usuario_id'], $datos)) {
                $_SESSION['nombre'] = $datos['nombre'];
                $mensaje = 'Perfil actualizado correctamente';
                $usuario = $usuarioModel->obtenerPorId($_SESSION['usuario_id']);
            } else {
                $error = 'Error al actualizar el perfil';
            }
        } elseif ($_POST['action'] === 'cambiar_password') {
            $actual    = $_POST['password_actual'];
            $nueva     = $_POST['password_nueva'];
            $confirmar = $_POST['password_confirmar'];
            if ($nueva !== $confirmar) {
                $error = 'Las contraseñas no coinciden';
            } elseif (strlen($nueva) < 6) {
                $error = 'La contraseña debe tener al menos 6 caracteres';
            } else {
                if ($usuarioModel->cambiarPassword($_SESSION['usuario_id'], $actual, $nueva)) {
                    $mensaje = 'Contraseña actualizada correctamente';
                } else {
                    $error = 'La contraseña actual es incorrecta';
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
    <link rel="icon" href="../../Assets/img/logo.png" type="image/png" />
    <title>Mi Perfil — InmoVision3D</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <style>
        /* ── LAYOUT ── */
        .perfil-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.8rem; color: #475569; margin-bottom: 1.75rem;
        }
        .breadcrumb a { color: #475569; text-decoration: none; transition: color .15s; }
        .breadcrumb a:hover { color: #0ea5e9; }
        .breadcrumb .sep { color: #334155; }
        .breadcrumb .current { color: #94a3b8; }

        /* ── HERO DEL PERFIL ── */
        .perfil-hero {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 20px;
            padding: 2rem 2rem 0;
            margin-bottom: 1.5rem;
            overflow: hidden;
            position: relative;
        }
        /* Banda de gradiente decorativa arriba */
        .perfil-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(135deg, #0ea5e9, #f97316);
        }

        .perfil-hero-inner {
            display: flex;
            align-items: flex-end;
            gap: 1.75rem;
            flex-wrap: wrap;
        }

        /* Avatar de iniciales */
        .avatar-initials {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(14, 165, 233, 0.2);
            color: #0ea5e9;
            font-size: 2.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(14, 165, 233, 0.4);
            flex-shrink: 0;
            margin-bottom: 1rem;
        }
        .perfil-hero-text {
            padding-bottom: 1.5rem;
            flex: 1;
        }
        .perfil-hero-text h1 {
            font-size: 1.5rem; font-weight: 700; margin-bottom: 4px;
        }
        .perfil-hero-text .hero-email {
            font-size: 0.85rem; color: #64748b; margin-bottom: 10px;
        }

        /* Chip de rol */
        .rol-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 600;
        }
        .rol-administrador { background: rgba(139,92,246,0.15); color: #8b5cf6; }
        .rol-publicador    { background: rgba(20,184,166,0.15);  color: #14b8a6; }
        .rol-cliente       { background: rgba(14,165,233,0.15);  color: #0ea5e9; }

        /* Tabs dentro del hero */
        .perfil-tabs {
            display: flex; gap: 0; margin-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .perfil-tab {
            padding: 12px 20px;
            font-size: 0.85rem; font-weight: 500;
            color: #64748b; cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: color .2s, border-color .2s;
            background: none; border-left: none; border-right: none; border-top: none;
            font-family: 'Poppins', sans-serif;
            display: flex; align-items: center; gap: 7px;
        }
        .perfil-tab:hover { color: #cbd5e1; }
        .perfil-tab.active {
            color: #0ea5e9;
            border-bottom-color: #0ea5e9;
        }

        /* ── PANELS ── */
        .perfil-panel { display: none; }
        .perfil-panel.active { display: block; }

        /* ── GRID DE CONTENIDO ── */
        .perfil-content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            align-items: start;
        }
        .span-full { grid-column: 1 / -1; }

        /* ── CARD GENÉRICA ── */
        .p-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            overflow: hidden;
        }
        .p-card-header {
            display: flex; align-items: center; gap: 10px;
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.02);
        }
        .p-card-icon {
            width: 32px; height: 32px; border-radius: 8px;
            background: rgba(14,165,233,0.12);
            display: flex; align-items: center; justify-content: center;
            color: #0ea5e9; font-size: 0.85rem; flex-shrink: 0;
        }
        .p-card-icon.orange { background: rgba(249,115,22,0.12); color: #f97316; }
        .p-card-icon.purple { background: rgba(139,92,246,0.12); color: #8b5cf6; }
        .p-card-header h2 { font-size: 0.95rem; font-weight: 600; color: #e2e8f0; }
        .p-card-body { padding: 1.5rem; }

        /* ── FORM ── */
        .fgroup { display: flex; flex-direction: column; gap: 6px; margin-bottom: 1rem; }
        .fgroup:last-of-type { margin-bottom: 0; }
        .fgroup label {
            font-size: 0.75rem; font-weight: 600; color: #64748b;
            text-transform: uppercase; letter-spacing: .04em;
            display: flex; align-items: center; gap: 6px;
        }
        .fgroup label i { font-size: 0.7rem; }
        .fgroup input {
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 9px;
            padding: 11px 14px;
            color: #f1f5f9;
            font-family: 'Poppins', sans-serif;
            font-size: 0.875rem;
            transition: border-color .2s, box-shadow .2s;
            width: 100%;
        }
        .fgroup input::placeholder { color: #334155; }
        .fgroup input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.1);
        }

        /* Input con icono de mostrar/ocultar */
        .input-pw { position: relative; }
        .input-pw input { padding-right: 44px; }
        .input-pw .toggle-pw {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #475569; cursor: pointer;
            font-size: 0.875rem; padding: 4px; transition: color .15s;
        }
        .input-pw .toggle-pw:hover { color: #94a3b8; }

        /* Botones del formulario */
        .btn-form {
            width: 100%; padding: 12px;
            border: none; border-radius: 9px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.875rem; font-weight: 600;
            cursor: pointer; transition: opacity .18s, transform .15s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 1.25rem;
        }
        .btn-form-primary {
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            color: #fff;
            box-shadow: 0 4px 16px rgba(14,165,233,0.2);
        }
        .btn-form-primary:hover { opacity: .88; transform: translateY(-1px); }
        .btn-form-secondary {
            background: transparent;
            border: 1px solid rgba(249,115,22,0.4);
            color: #f97316;
        }
        .btn-form-secondary:hover { background: rgba(249,115,22,0.08); transform: translateY(-1px); }

        /* ── ESTADÍSTICAS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 1rem;
        }
        .stat-item {
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 1.25rem 1rem;
            text-align: center;
            transition: border-color .2s, transform .2s;
        }
        .stat-item:hover { border-color: rgba(14,165,233,0.25); transform: translateY(-2px); }
        .stat-item i {
            font-size: 1.2rem;
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block; margin-bottom: 8px;
        }
        .stat-value {
            display: block;
            font-size: 1.5rem; font-weight: 700; line-height: 1;
            margin-bottom: 4px;
        }
        .stat-label { font-size: 0.72rem; color: #475569; font-weight: 500; }

        /* ── ALERTS ── */
        .p-alert {
            display: flex; align-items: center; gap: 10px;
            padding: 1rem 1.25rem; border-radius: 10px;
            font-size: 0.875rem; font-weight: 500;
            margin-bottom: 1.5rem;
        }
        .p-alert-success { background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.2); color: #4ade80; }
        .p-alert-error   { background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #f87171; }

        /* ── RESPONSIVE ── */
        @media (max-width: 680px) {
            .perfil-content-grid { grid-template-columns: 1fr; }
            .span-full { grid-column: 1; }
            .perfil-hero-inner { gap: 1rem; }
            .avatar-initials { width: 72px; height: 72px; font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<!-- ═══ HEADER ═══ -->
<header class="header">
    <div class="header-container">
        <a href="<?php echo SITE_URL; ?>index.php" class="logo">
            <img src="<?php echo SITE_URL; ?>/assets/img/logo.png" alt="InmoVision 3D" class="logo-icon" width="50" height="50">
            <span class="logo-text">InmoVision <span class="highlight">3D</span></span>
        </a>

        <nav class="nav" id="mainNav">
            <a href="<?php echo SITE_URL; ?>/index.php" class="nav-link">Inicio</a>
            <a href="<?php echo SITE_URL; ?>/views/inmuebles/listar.php" class="nav-link">Inmuebles</a>
            <?php if (in_array($_SESSION['rol'], ['publicador','admin'])): ?>
            <a href="<?php echo SITE_URL; ?>/views/inmuebles/publicar.php" class="nav-link">Publicar</a>
            <?php endif; ?>
        </nav>

        <div class="profile-nav" id="profileNav">
            <div class="profile-box">
                            <div class="avatar"><?php echo strtoupper(substr($_SESSION['nombre'], 0, 2)); ?></div>
                            <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            </div>
            <div class="dropdown-menu" id="profileDropdown">
                <a href="<?php echo SITE_URL; ?>/views/usuario/perfil.php" class="active"> Mi perfil</a>
                <?php if (isPublicador()): ?>
                <a href="<?php echo SITE_URL; ?>/views/usuario/mis-inmuebles.php">Mis Inmuebles</a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/views/usuario/favoritos.php"> Favoritos </a>
                <a href="<?php echo SITE_URL; ?>/views/usuario/solicitudes.php"> Solicitudes </a>
                <?php if ($_SESSION['rol'] === 'admin'): ?>
                <div class="dropdown-divider"></div>
                <a href="<?php echo SITE_URL; ?>/views/admin/dashboard.php"> Panel Admin </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout"> Cerrar sesión </a>
            </div>
        </div>
    </div>
</header>

<!-- ═══ CONTENIDO ═══ -->
<main style="padding-top:70px;">
<div class="perfil-wrapper">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?php echo SITES_URL; ?>index.php">Inicio</a>
        <span class="sep">›</span>
        <span class="current">Mi perfil</span>
    </div>

    <!-- Alertas -->
    <?php if ($mensaje): ?>
    <div class="p-alert p-alert-success">
        <i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="p-alert p-alert-error">
        <i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <!-- Hero del perfil -->
    <div class="perfil-hero">
        <div class="perfil-hero-inner">
            <div class="avatar-initials"><?php echo strtoupper(substr($_SESSION['nombre'], 0, 2)); ?></div>
            <div class="perfil-hero-text">
                <h1><?php echo htmlspecialchars($usuario['nombre']); ?></h1>
                <p class="hero-email"><?php echo htmlspecialchars($usuario['correo']); ?></p>
                <span class="rol-chip rol-<?php echo $usuario['rol']; ?>">
                    <i class="fas fa-<?php echo $usuario['rol'] === 'admin' ? 'shield-halved' : ($usuario['rol'] === 'publicador' ? 'pen-to-square' : 'user'); ?>"></i>
                    <?php echo ucfirst($usuario['rol']); ?>
                </span>
            </div>
        </div>

        <!-- Tabs -->
        <div class="perfil-tabs">
            <button class="perfil-tab active" onclick="switchTab('datos', this)">
                <i class="fas fa-sliders"></i> Mis datos
            </button>
            <button class="perfil-tab" onclick="switchTab('seguridad', this)">
                <i class="fas fa-lock"></i> Seguridad
            </button>
            <button class="perfil-tab" onclick="switchTab('actividad', this)">
                <i class="fas fa-chart-bar"></i> Actividad
            </button>
        </div>
    </div>

    <!-- Panel: Mis datos -->
    <div class="perfil-panel active" id="panel-datos">
        <div class="perfil-content-grid">
            <div class="p-card span-full">
                <div class="p-card-header">
                    <div class="p-card-icon"><i class="fas fa-user"></i></div>
                    <h2>Información personal</h2>
                </div>
                <div class="p-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="actualizar_perfil">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="fgroup">
                                <label><i class="fas fa-user"></i> Nombre</label>
                                <input type="text" name="nombre" required
                                       value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                       placeholder="Tu nombre">
                            </div>
                            <div class="fgroup">
                                <label><i class="fas fa-user"></i> Apellido</label>
                                <input type="text" name="apellido" required
                                       value="<?php echo htmlspecialchars($usuario['apellido']); ?>"
                                       placeholder="Tu apellido">
                            </div>
                            <div class="fgroup">
                                <label><i class="fas fa-envelope"></i> Correo electrónico</label>
                                <input type="email" name="email" required
                                       value="<?php echo htmlspecialchars($usuario['correo']); ?>"
                                       placeholder="tu@email.com">
                            </div>
                            <div class="fgroup">
                                <label><i class="fas fa-phone"></i> Teléfono</label>
                                <input type="tel" name="telefono"
                                       value="<?php echo htmlspecialchars($usuario['telefono'] ?? ''); ?>"
                                       placeholder="+57 300 000 0000">
                            </div>
                        </div>
                        <button type="submit" class="btn-form btn-form-primary">
                            <i class="fas fa-floppy-disk"></i> Guardar cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel: Seguridad -->
    <div class="perfil-panel" id="panel-seguridad">
        <div class="perfil-content-grid">
            <div class="p-card">
                <div class="p-card-header">
                    <div class="p-card-icon orange"><i class="fas fa-key"></i></div>
                    <h2>Cambiar contraseña</h2>
                </div>
                <div class="p-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="cambiar_password">
                        <div class="fgroup">
                            <label>Contraseña actual</label>
                            <div class="input-pw">
                                <input type="password" id="pw_actual" name="password_actual" required
                                       placeholder="••••••••">
                                <button type="button" class="toggle-pw" onclick="togglePw('pw_actual', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="fgroup">
                            <label>Nueva contraseña</label>
                            <div class="input-pw">
                                <input type="password" id="pw_nueva" name="password_nueva" required minlength="6"
                                       placeholder="Mínimo 6 caracteres">
                                <button type="button" class="toggle-pw" onclick="togglePw('pw_nueva', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="fgroup">
                            <label>Confirmar contraseña</label>
                            <div class="input-pw">
                                <input type="password" id="pw_confirmar" name="password_confirmar" required
                                       placeholder="Repite la nueva contraseña">
                                <button type="button" class="toggle-pw" onclick="togglePw('pw_confirmar', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn-form btn-form-secondary">
                            <i class="fas fa-shield-halved"></i> Actualizar contraseña
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info de cuenta -->
            <div class="p-card">
                <div class="p-card-header">
                    <div class="p-card-icon purple"><i class="fas fa-circle-info"></i></div>
                    <h2>Datos de la cuenta</h2>
                </div>
                <div class="p-card-body">
                    <div style="display:flex;flex-direction:column;gap:1rem;">
                        <div style="background:#0f172a;border-radius:10px;padding:1rem 1.25rem;
                                    border:1px solid rgba(255,255,255,0.07);">
                            <div style="font-size:.72rem;color:#475569;text-transform:uppercase;
                                        letter-spacing:.04em;margin-bottom:4px;">ID de usuario</div>
                            <div style="font-size:.9rem;color:#94a3b8;font-family:monospace;">
                                #<?php echo $_SESSION['usuario_id']; ?>
                            </div>
                        </div>
                        <div style="background:#0f172a;border-radius:10px;padding:1rem 1.25rem;
                                    border:1px solid rgba(255,255,255,0.07);">
                            <div style="font-size:.72rem;color:#475569;text-transform:uppercase;
                                        letter-spacing:.04em;margin-bottom:4px;">Rol</div>
                            <span class="rol-chip rol-<?php echo $usuario['rol']; ?>">
                                <?php echo ucfirst($usuario['rol']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel: Actividad -->
    <div class="perfil-panel" id="panel-actividad">
        <div class="p-card">
            <div class="p-card-header">
                <div class="p-card-icon"><i class="fas fa-chart-bar"></i></div>
                <h2>Mis estadísticas</h2>
            </div>
            <div class="p-card-body">
                <div class="stats-grid">
                    <div class="stat-item">
                        <i class="fas fa-heart"></i>
                        <span class="stat-value" id="stat-favoritos">—</span>
                        <span class="stat-label">Favoritos</span>
                    </div>
                    <?php if (in_array($_SESSION['rol'], ['publicador','admin'])): ?>
                    <div class="stat-item">
                        <i class="fas fa-house"></i>
                        <span class="stat-value" id="stat-publicados">—</span>
                        <span class="stat-label">Publicados</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-envelope"></i>
                        <span class="stat-value" id="stat-solicitudes">—</span>
                        <span class="stat-label">Solicitudes</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="footer-bottom">
        <p>&copy; 2026 InmoVision3D. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
<script>
// Dropdown header
const profileNav = document.getElementById('profileNav');
const profileDD  = document.getElementById('profileDropdown');
profileNav?.addEventListener('click', e => {
    e.stopPropagation();
    profileDD.classList.toggle('show');
});
document.addEventListener('click', () => profileDD?.classList.remove('show'));

// Tabs
function switchTab(tab, btn) {
    document.querySelectorAll('.perfil-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.perfil-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    btn.classList.add('active');
}

// Toggle contraseña
function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Estadísticas
fetch('<?php echo SITE_URL; ?>/Api/estadisticas.php?usuario_id=<?php echo $_SESSION['usuario_id']; ?>')
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val ?? 0; };
        set('stat-favoritos',   data.favoritos);
        set('stat-publicados',  data.publicados);
        set('stat-solicitudes', data.solicitudes);
    })
    .catch(() => {}); // silencioso si la API no existe aún
</script>
</body>
</html>