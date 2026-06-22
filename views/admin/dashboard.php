<?php
/**
 * Dashboard de Administrador - InmoVision3D
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../models/Inmueble.php';
require_once __DIR__ . '/../../models/Solicitud.php';

if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'], ['admin', 'administrador'])) {
    header('Location: ' . SITE_URL . 'views/auth/login.php');
    exit;
}

$usuarioModel   = new Usuario();
$inmuebleModel  = new Inmueble();
$solicitudModel = new Solicitud();

$totalUsuarios         = count($usuarioModel->listarTodos());
$totalInmuebles        = count($inmuebleModel->listar());
$solicitudesPendientes = count($solicitudModel->obtenerPendientes());

// Tab activo — sólo valores permitidos
$tabsValidos = ['usuarios', 'inmuebles', 'solicitudes', 'reportes'];
$tab = in_array($_GET['tab'] ?? '', $tabsValidos) ? $_GET['tab'] : 'usuarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../Assets/img/logo.png" type="image/png" />
    <title>Panel de Administración - InmoVision3D</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <style>
        /* ── Reset ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }

        /* ════════════════════ HEADER ════════════════════ */
        .adm-header {
            position: sticky; top: 0; z-index: 100;
            background: rgba(15,23,42,0.97);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 3rem; height: 70px;
        }

        .adm-logo {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none;
        }
        .adm-logo img { width: 50px; height: 50px; border-radius: 8px; object-fit: contain; }
        .adm-logo .logo-text { font-size: 1.4rem; font-weight: 700; color: #f1f5f9; }
        .adm-logo .logo-text span {
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }

        .adm-header-nav { display: flex; align-items: center; gap: 1.5rem; margin-left: auto; margin-right: 1rem; }
        .adm-header-nav a { color: #94a3b8; text-decoration: none; font-size: 1rem; font-weight: 500; transition: color .2s; }
        .adm-header-nav a:hover { color: #f1f5f9; }

        /* Perfil dropdown */
        .adm-profile {
            position: relative; display: flex; align-items: center; gap: 8px;
            cursor: pointer; padding: 6px 10px; border-radius: 8px; transition: background .2s;
        }
        .adm-profile:hover { background: rgba(255,255,255,0.06); }
        .adm-profile .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: rgba(14,165,233,0.2); color: #0ea5e9;
            font-size: 0.75rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid rgba(14,165,233,0.4);
        }
        .adm-profile .pname { font-size: 1rem; font-weight: 500; }

        .adm-dropdown {
            display: none; position: absolute; top: calc(100% + 8px); right: 0;
            min-width: 190px; background: #1e293b;
            border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;
            overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.4); z-index: 200;
        }
        .adm-dropdown.open { display: block; }
        .adm-dropdown a {
            display: block; padding: 11px 16px; color: #94a3b8;
            text-decoration: none; font-size: 1rem; transition: background .15s, color .15s;
        }
        .adm-dropdown a:hover { background: rgba(255,255,255,0.07); color: #f1f5f9; }
        .adm-dropdown a.active { color: #0ea5e9; background: rgba(14,165,233,0.1); }
        .adm-dropdown hr { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 4px 0; }

        /* ════════════════════ BODY ════════════════════ */
        .adm-body { max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem 3rem; }

        .adm-page-title { margin-bottom: 1.75rem; }
        .adm-page-title h1 { font-size: 1.6rem; font-weight: 700; }
        .adm-page-title p  { color: #64748b; font-size: 0.9rem; margin-top: 4px; }

        /* ════════════════════ STAT CARDS ════════════════════ */
        .stat-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px; padding: 1.25rem 1.5rem;
            display: flex; align-items: center; gap: 1rem;
            transition: transform .2s, border-color .2s;
        }
        .stat-card:hover { transform: translateY(-2px); border-color: rgba(14,165,233,0.25); }
        .stat-icon-box {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .si-blue   { background: rgba(14,165,233,0.15);  color: #0ea5e9; }
        .si-green  { background: rgba(34,197,94,0.15);   color: #22c55e; }
        .si-amber  { background: rgba(245,158,11,0.15);  color: #f59e0b; }
        .si-purple { background: rgba(139,92,246,0.15);  color: #8b5cf6; }
        .stat-val  { font-size: 1.9rem; font-weight: 700; line-height: 1; }
        .stat-lbl  { font-size: 0.78rem; color: #64748b; margin-top: 3px; }

        /* ════════════════════ TABS ════════════════════ */
        .adm-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }

        .adm-tab {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: 50px;
            font-size: 0.875rem; font-weight: 500; cursor: pointer;
            text-decoration: none; border: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8; background: transparent; transition: all .2s;
        }
        .adm-tab:hover { background: rgba(255,255,255,0.05); color: #f1f5f9; }
        .adm-tab.active {
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            border-color: transparent; color: #fff;
        }
        .adm-tab .tab-count {
            background: rgba(255,255,255,0.25); font-size: 0.72rem;
            font-weight: 700; padding: 1px 7px; border-radius: 20px;
        }
        .adm-tab.active .tab-count { background: rgba(255,255,255,0.3); }

        /* ════════════════════ RESPONSIVE ════════════════════ */
        @media (max-width: 768px) {
            .adm-header { padding: 0 1rem; }
            .adm-header-nav { display: none; }
            .adm-body { padding: 1.25rem 1rem 2rem; }
        }
        @media (max-width: 480px) {
            .stat-row { grid-template-columns: 1fr 1fr; }
        }

        /* ════════════════════ ESTILOS COMPARTIDOS (usados por _includes) ════════════════════ */

        /* Panel */
        .adm-panel {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px; overflow: hidden; margin-bottom: 1.5rem;
        }
        .adm-panel-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.07);
            gap: 1rem; flex-wrap: wrap;
        }

        /* Buscador */
        .search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 400px; }
        .search-wrap svg {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: #475569; width: 16px; height: 16px;
        }
        .search-wrap input {
            width: 100%; background: #0f172a;
            border: 1px solid rgba(255,255,255,0.08); border-radius: 8px;
            padding: 9px 14px 9px 36px; color: #f1f5f9;
            font-family: inherit; font-size: 0.875rem; transition: border-color .2s;
        }
        .search-wrap input::placeholder { color: #475569; }
        .search-wrap input:focus { outline: none; border-color: #0ea5e9; }

        /* Tabla */
        .adm-table { width: 100%; border-collapse: collapse; }
        .adm-table th {
            padding: 10px 1.5rem; text-align: left;
            font-size: 0.78rem; font-weight: 600; color: #64748b;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .adm-table td {
            padding: 13px 1.5rem; font-size: 0.875rem;
            border-bottom: 1px solid rgba(255,255,255,0.05); color: #e2e8f0;
        }
        .adm-table tbody tr:last-child td { border-bottom: none; }
        .adm-table tbody tr:hover td { background: rgba(255,255,255,0.02); }

        /* Chips */
        .chip { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .chip-admin   { background: rgba(139,92,246,0.15); color: #8b5cf6; }
        .chip-pub     { background: rgba(20,184,166,0.15);  color: #14b8a6; }
        .chip-cliente { background: rgba(14,165,233,0.15);  color: #0ea5e9; }

        /* Botones de tabla */
        .tbl-actions { display: flex; gap: 5px; }
        .btn-tbl {
            width: 30px; height: 30px; border-radius: 6px;
            border: 1px solid rgba(255,255,255,0.1); background: transparent;
            color: #64748b; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: all .15s; font-size: 0.85rem;
        }
        .btn-tbl:hover        { background: rgba(255,255,255,0.07); color: #f1f5f9; }
        .btn-tbl.danger:hover { background: rgba(239,68,68,0.12); color: #ef4444; border-color: rgba(239,68,68,0.3); }

        /* ════ MODAL INLINE ════ */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            z-index: 500; align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }

        .modal-box {
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px; padding: 2rem; width: 100%; max-width: 540px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 24px 64px rgba(0,0,0,0.5);
            animation: modalIn .2s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(.96) translateY(8px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        .modal-header h2 { font-size: 1.1rem; font-weight: 600; }
        .modal-close {
            width: 32px; height: 32px; border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1); background: transparent;
            color: #64748b; cursor: pointer; font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            transition: all .15s;
        }
        .modal-close:hover { background: rgba(239,68,68,0.12); color: #ef4444; }

        /* Formulario del modal */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-grid .span2 { grid-column: span 2; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 0.8rem; font-weight: 600; color: #94a3b8; }
        .form-group input,
        .form-group select {
            background: #0f172a; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; padding: 10px 12px; color: #f1f5f9;
            font-family: inherit; font-size: 0.875rem; transition: border-color .2s;
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus { outline: none; border-color: #0ea5e9; }
        .form-group input::placeholder { color: #475569; }
        .form-group select option { background: #1e293b; }

        .form-footer {
            display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;
        }
        .btn-cancel {
            padding: 10px 20px; border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1); background: transparent;
            color: #94a3b8; font-family: inherit; font-size: 0.875rem;
            cursor: pointer; transition: all .15s;
        }
        .btn-cancel:hover { background: rgba(255,255,255,0.05); color: #f1f5f9; }
        .btn-primary {
            padding: 10px 24px; border-radius: 8px; border: none;
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            color: #fff; font-family: inherit; font-size: 0.875rem;
            font-weight: 600; cursor: pointer; transition: opacity .15s;
        }
        .btn-primary:hover { opacity: 0.88; }

        /* Notificación toast */
        .toast {
            position: fixed; bottom: 2rem; right: 2rem;
            background: #1e293b; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px; padding: 0.9rem 1.4rem;
            font-size: 0.875rem; font-weight: 500;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            z-index: 999; display: none;
            animation: toastIn .25s ease;
        }
        .toast.show { display: block; }
        .toast.success { border-left: 3px solid #22c55e; color: #22c55e; }
        .toast.error   { border-left: 3px solid #ef4444; color: #ef4444; }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- ═══ HEADER ═══ -->
<header class="adm-header">
    <a href="<?php echo SITE_URL; ?>/index.php" class="adm-logo">
        <img src="../../assets/img/logo.png" alt="InmoVision 3D logo">
        <span class="logo-text">InmoVision <span>3D</span></span>
    </a>

    <nav class="adm-header-nav">
        <a href="<?php echo SITE_URL; ?>/index.php" class="nav-link">Inicio</a>
        <a href="<?php echo SITE_URL; ?>/views/inmuebles/listar.php" class="nav-link">Inmuebles</a>
        <a href="<?php echo SITE_URL; ?>/views/inmuebles/publicar.php" class="nav-link">Publicar</a>
    </nav>

    <div class="adm-profile" id="profileBtn">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['nombre'], 0, 2)); ?></div>
        <span class="pname"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        <div class="adm-dropdown" id="profileDropdown">
            <a href="<?php echo SITE_URL; ?>/views/usuario/perfil.php">Mi perfil</a>
            <?php if (isPublicador()): ?>
            <a href="<?php echo SITE_URL; ?>/views/usuario/mis-inmuebles.php">Mis Inmuebles</a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/views/usuario/favoritos.php">Favoritos</a>
            <a href="<?php echo SITE_URL; ?>/views/usuario/solicitudes.php">Solicitudes</a>
            <hr>
            <a href="<?php echo SITE_URL; ?>/views/admin/dashboard.php" class="active">Panel Admin</a>
            <hr>
            <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout">Cerrar sesión</a>
        </div>
    </div>
</header>

<!-- ═══ CUERPO ═══ -->
<main class="adm-body">

    <!-- Título -->
    <div class="adm-page-title">
        <h1>Panel de Administración</h1>
        <p>Gestiona usuarios, inmuebles y solicitudes del sistema</p>
    </div>

    <!-- Stat Cards -->
    <div class="stat-row">
        <div class="stat-card">
            <div class="stat-icon-box si-blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <div class="stat-val"><?php echo $totalUsuarios; ?></div>
                <div class="stat-lbl">Usuarios totales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box si-green">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div>
                <div class="stat-val"><?php echo $totalInmuebles; ?></div>
                <div class="stat-lbl">Inmuebles publicados</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box si-amber">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
                <div class="stat-val"><?php echo $solicitudesPendientes; ?></div>
                <div class="stat-lbl">Solicitudes pendientes</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-box si-purple">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div>
                <div class="stat-val" id="planos3d-count">0</div>
                <div class="stat-lbl">Planos 3D</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="adm-tabs">
        <a href="?tab=usuarios" class="adm-tab <?php echo $tab === 'usuarios' ? 'active' : ''; ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Usuarios
            <span class="tab-count"><?php echo $totalUsuarios; ?></span>
        </a>
        <a href="?tab=inmuebles" class="adm-tab <?php echo $tab === 'inmuebles' ? 'active' : ''; ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Inmuebles
            <span class="tab-count"><?php echo $totalInmuebles; ?></span>
        </a>
        <a href="?tab=solicitudes" class="adm-tab <?php echo $tab === 'solicitudes' ? 'active' : ''; ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Solicitudes
            <?php if ($solicitudesPendientes > 0): ?>
                <span class="tab-count"><?php echo $solicitudesPendientes; ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=reportes" class="adm-tab <?php echo $tab === 'reportes' ? 'active' : ''; ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Reportes
        </a>
    </div>

    <!-- Área de contenido dinámico -->
    <div id="tab-content">
        <?php
        // dirname(__FILE__) es más confiable que __DIR__ en algunos setups
        $tabFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_tabs' . DIRECTORY_SEPARATOR . '_' . $tab . '.php';
        if (file_exists($tabFile)) {
            include $tabFile;
        } else {
            echo '<p style="color:#ef4444;padding:2rem;font-size:.85rem">
                    ⚠ No se encontró el archivo: <code>' . htmlspecialchars($tabFile) . '</code><br>
                    Asegúrate de que la carpeta <strong>_tabs/</strong> exista dentro de la misma carpeta que dashboard.php
                  </p>';
        }
        ?>
    </div>

</main>

<!-- Toast de notificaciones -->
<div class="toast" id="toast"></div>

<script>
    // Dropdown perfil
    const profileBtn = document.getElementById('profileBtn');
    const dropdown   = document.getElementById('profileDropdown');
    profileBtn.addEventListener('click', e => {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });
    document.addEventListener('click', e => {
        if (!profileBtn.contains(e.target)) dropdown.classList.remove('open');
    });
    dropdown.addEventListener('click', e => e.stopPropagation());

    // Toast helper global
    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = `toast ${type} show`;
        setTimeout(() => t.classList.remove('show'), 3200);
    }

    // Modal helpers globales
    function openModal(id) {
        document.getElementById(id).classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
    }

    // Cerrar modal al hacer clic fuera
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });
</script>
</body>
</html>