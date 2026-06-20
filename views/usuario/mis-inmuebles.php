<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Inmueble.php';

/* ── Autenticación ── */
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . SITE_URL . '/views/auth/login.php');
    exit;
}
if ($_SESSION['rol'] === 'cliente') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$inmuebleModel = new Inmueble();
$userId    = (int)$_SESSION['usuario_id'];
$esAdmin   = $_SESSION['rol'] === 'admin';

if ($esAdmin && isset($_GET['todos'])) {
    $inmuebles = $inmuebleModel->listar();
} else {
    if (method_exists($inmuebleModel, 'listarPorPublicador')) {
        $inmuebles = $inmuebleModel->listarPorPublicador($userId);
    } else {
        $todos = $inmuebleModel->listar();

        $inmuebles = array_filter($todos, function($i) use ($userId) {
            return (int)$i['idPublicador'] === $userId;
        });

        $inmuebles = array_values($inmuebles);
    }
}

/* ── Helper chips ── */
function chipInmueble($valor, $tipo) {
    if ($tipo === 'operacion') {
        if ($valor === 'arriendo') return ['chip-arriendo', 'Arriendo'];
        if ($valor === 'venta')    return ['chip-venta',    'Venta'];
        return ['chip-cliente', ucfirst($valor)];
    }
    if ($tipo === 'estado') {
        if ($valor === 'disponible') return ['chip-disp',    'Disponible'];
        if ($valor === 'vendido')    return ['chip-vendido',  'Vendido'];
        if ($valor === 'arrendado')  return ['chip-arriendo', 'Arrendado'];
        if ($valor === 'pausado')    return ['chip-pausado',  'Pausado'];
        return ['chip-cliente', ucfirst($valor)];
    }
    return ['chip-cliente', ucfirst($valor)];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../Assets/img/logo.png" type="image/png" />
    <title>Mis Inmuebles — InmoVision3D</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">

    <style>

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }

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
        .adm-header-nav a.active { color: #0ea5e9; }

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
        
        @media (max-width: 768px) {
            .adm-header { padding: 0 1rem; }
            .adm-header-nav { display: none; }
        }

        .mis-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.8rem; color: #475569; margin-bottom: 1.5rem;
        }
        .breadcrumb a { color: #475569; text-decoration: none; transition: color .15s; }
        .breadcrumb a:hover { color: #0ea5e9; }
        .breadcrumb .sep { color: #334155; }
        .breadcrumb .current { color: #94a3b8; }

        /* Page title */
        .page-title {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;
        }
        .page-title-left { display: flex; align-items: center; gap: 1rem; }
        .page-title-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(14,165,233,0.12);
            border: 1px solid rgba(14,165,233,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .page-title h1 { font-size: 1.5rem; font-weight: 700; }
        .page-title p  { font-size: 0.82rem; color: #64748b; margin-top: 2px; }

        .btn-nuevo {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 22px; border-radius: 9px; border: none;
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            color: #fff; font-family: 'Poppins', sans-serif;
            font-size: 0.875rem; font-weight: 600; cursor: pointer;
            transition: opacity .18s, transform .15s;
            box-shadow: 0 4px 20px rgba(14,165,233,0.25);
            white-space: nowrap;
        }
        .btn-nuevo:hover { opacity: .88; transform: translateY(-1px); }

        /* Stats rápidos */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem; margin-bottom: 1.5rem;
        }
        .stat-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            display: flex; align-items: center; gap: .875rem;
        }
        .stat-icon {
            width: 38px; height: 38px; border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem; flex-shrink: 0;
        }
        .stat-icon.blue  { background: rgba(14,165,233,0.12); color: #0ea5e9; }
        .stat-icon.green { background: rgba(34,197,94,0.12);  color: #22c55e; }
        .stat-icon.amber { background: rgba(245,158,11,0.12); color: #f59e0b; }
        .stat-icon.violet{ background: rgba(139,92,246,0.12); color: #8b5cf6; }
        .stat-icon.slate { background: rgba(100,116,139,0.12);color: #64748b; }
        .stat-num  { font-size: 1.3rem; font-weight: 700; line-height: 1; }
        .stat-lbl  { font-size: 0.72rem; color: #64748b; margin-top: 2px; }

        .adm-panel {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            overflow: hidden;
        }
        .adm-panel-header {
            display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            background: rgba(255,255,255,0.02);
        }

        .search-wrap {
            position: relative; display: flex; align-items: center;
            flex: 1; min-width: 180px; max-width: 280px;
        }
        .search-wrap svg {
            position: absolute; left: 10px; width: 15px; height: 15px;
            color: #475569; pointer-events: none;
        }
        .search-wrap input {
            width: 100%;
            background: #0f172a; border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px; padding: 8px 10px 8px 32px;
            color: #94a3b8; font-family: 'Poppins', sans-serif;
            font-size: 0.8rem; transition: border-color .2s;
        }
        .search-wrap input:focus { outline: none; border-color: #0ea5e9; color: #f1f5f9; }
        .search-wrap input::placeholder { color: #334155; }

        .filter-bar { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
        .filter-bar select {
            background: #0f172a; border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; padding: 8px 12px; color: #94a3b8;
            font-family: 'Poppins', sans-serif; font-size: 0.8rem;
            cursor: pointer; transition: border-color .2s;
        }
        .filter-bar select:focus { outline: none; border-color: #0ea5e9; }
        .filter-bar select option { background: #1e293b; }

        /* Tabla */
        .adm-table {
            width: 100%; border-collapse: collapse;
        }
        .adm-table thead th {
            padding: 11px 14px; text-align: left;
            font-size: 0.72rem; font-weight: 600;
            color: #475569; text-transform: uppercase; letter-spacing: .05em;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            background: rgba(255,255,255,0.015);
        }
        .adm-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background .15s;
        }
        .adm-table tbody tr:last-child { border-bottom: none; }
        .adm-table tbody tr:hover { background: rgba(255,255,255,0.03); }
        .adm-table td { padding: 12px 14px; font-size: 0.85rem; color: #cbd5e1; vertical-align: middle; }

        /* Chips */
        .chip {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 0.72rem; font-weight: 600;
        }
        .chip-arriendo { background: rgba(245,158,11,0.15);  color: #f59e0b; }
        .chip-venta    { background: rgba(14,165,233,0.15);  color: #0ea5e9; }
        .chip-disp     { background: rgba(34,197,94,0.15);   color: #22c55e; }
        .chip-vendido  { background: rgba(139,92,246,0.15);  color: #8b5cf6; }
        .chip-pausado  { background: rgba(100,116,139,0.15); color: #64748b; }
        .chip-cliente  { background: rgba(148,163,184,0.1);  color: #94a3b8; }

        .price-cell { color: #0ea5e9; font-weight: 600; }

        .thumb-placeholder {
            width: 40px; height: 40px; border-radius: 8px;
            background: rgba(14,165,233,0.1); border: 1px solid rgba(14,165,233,0.2);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; overflow: hidden; font-size: 1rem;
        }
        .thumb-placeholder img { width: 100%; height: 100%; object-fit: cover; }

        /* Acciones */
        .tbl-actions { display: flex; gap: 6px; }
        .btn-tbl {
            width: 30px; height: 30px; border-radius: 7px; border: none;
            background: rgba(255,255,255,0.06); color: #94a3b8;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background .15s, color .15s;
        }
        .btn-tbl:hover { background: rgba(14,165,233,0.15); color: #0ea5e9; }
        .btn-tbl.danger:hover { background: rgba(239,68,68,0.15); color: #ef4444; }

        /* Ver detalle */
        .btn-tbl.view:hover { background: rgba(34,197,94,0.15); color: #22c55e; }

        /* Footer tabla */
        .tabla-footer {
            padding: .75rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.05);
            font-size: .78rem; color: #64748b;
            display: flex; justify-content: space-between; align-items: center;
        }

        /* Estado vacío */
        .empty-state {
            text-align: center; padding: 4rem 2rem;
        }
        .empty-state .empty-icon {
            width: 64px; height: 64px; border-radius: 16px;
            background: rgba(14,165,233,0.08);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; margin: 0 auto 1rem;
        }
        .empty-state h3 { font-size: 1rem; font-weight: 600; margin-bottom: .4rem; }
        .empty-state p  { font-size: .82rem; color: #64748b; margin-bottom: 1.25rem; }

        /* ══════════════════════════════════════
           MODALES
        ══════════════════════════════════════ */
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 500;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            align-items: center; justify-content: center;
            padding: 1rem;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            width: 100%; max-height: 90vh;
            overflow-y: auto;
            animation: slideUp .22s ease;
        }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            position: sticky; top: 0; background: #1e293b; z-index: 1;
        }
        .modal-header h2 { font-size: 1rem; font-weight: 700; }
        .modal-close {
            width: 30px; height: 30px; border-radius: 8px; border: none;
            background: rgba(255,255,255,0.07); color: #94a3b8;
            cursor: pointer; font-size: .85rem;
            display: flex; align-items: center; justify-content: center;
            transition: background .15s, color .15s;
        }
        .modal-close:hover { background: rgba(239,68,68,0.15); color: #ef4444; }

        .modal-body { padding: 1.5rem; }
        .modal-divider { border: none; border-top: 1px solid rgba(255,255,255,0.07); margin: 1.25rem 0; }

        /* Form grid dentro de modal */
        .form-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
        }
        .form-grid .span2 { grid-column: span 2; }
        @media (max-width: 540px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .span2 { grid-column: span 1; }
        }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label {
            font-size: 0.75rem; font-weight: 600; color: #64748b;
            text-transform: uppercase; letter-spacing: .04em;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 9px; padding: 10px 13px;
            color: #f1f5f9; font-family: 'Poppins', sans-serif;
            font-size: .875rem; width: 100%;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder { color: #334155; }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none; border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.12);
        }
        .form-group select option { background: #1e293b; }
        .form-group textarea { resize: vertical; min-height: 80px; }

        .form-footer {
            display: flex; align-items: center; justify-content: flex-end;
            gap: .75rem; padding: 1.25rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.07);
            position: sticky; bottom: 0; background: #1e293b;
        }
        .btn-cancel {
            padding: 9px 20px; border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            background: transparent; color: #64748b;
            font-family: 'Poppins', sans-serif; font-size: .875rem;
            cursor: pointer; transition: all .18s;
        }
        .btn-cancel:hover { background: rgba(255,255,255,0.05); color: #94a3b8; }
        .btn-primary {
            padding: 9px 22px; border-radius: 8px; border: none;
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            color: #fff; font-family: 'Poppins', sans-serif;
            font-size: .875rem; font-weight: 600; cursor: pointer;
            transition: opacity .18s;
        }
        .btn-primary:hover { opacity: .88; }
        .btn-primary:disabled { opacity: .55; cursor: not-allowed; }

        /* ── Upload zone ── */
        .upload-zone {
            border: 2px dashed rgba(255,255,255,0.12); border-radius: 10px;
            padding: 1.5rem; text-align: center; cursor: pointer;
            transition: border-color .2s, background .2s; position: relative;
        }
        .upload-zone:hover, .upload-zone.drag-over {
            border-color: #0ea5e9; background: rgba(14,165,233,0.05);
        }
        .upload-zone input[type=file] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .upload-zone-icon { font-size: 1.8rem; margin-bottom: .5rem; }
        .upload-zone p   { font-size: .8rem; color: #64748b; }
        .upload-zone strong { color: #0ea5e9; }

        /* ── Preview grid ── */
        .img-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
            gap: .6rem; margin-top: .75rem;
        }
        .img-preview-item {
            position: relative; border-radius: 8px; overflow: hidden;
            aspect-ratio: 1; background: #0f172a;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .img-preview-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .img-preview-item .img-remove {
            position: absolute; top: 4px; right: 4px;
            width: 20px; height: 20px; border-radius: 50%;
            background: rgba(239,68,68,0.85); color: #fff;
            border: none; cursor: pointer; font-size: .75rem;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .15s;
        }
        .img-preview-item:hover .img-remove { opacity: 1; }
        .img-preview-item .img-badge {
            position: absolute; bottom: 3px; left: 3px;
            background: rgba(0,0,0,0.6); color: #fff;
            font-size: .55rem; padding: 1px 5px; border-radius: 4px;
        }
        .img-existing-item { border: 1px solid rgba(34,197,94,0.3); }
        .img-existing-item .img-badge { background: rgba(34,197,94,0.7); }

        /* Plano */
        .plano-upload-area {
            border: 2px dashed rgba(139,92,246,0.3); border-radius: 10px;
            padding: 1.25rem; text-align: center; cursor: pointer;
            transition: border-color .2s, background .2s; position: relative;
            background: rgba(139,92,246,0.03);
        }
        .plano-upload-area:hover, .plano-upload-area.drag-over {
            border-color: #8b5cf6; background: rgba(139,92,246,0.07);
        }
        .plano-upload-area input[type=file] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .plano-upload-area p { font-size: .8rem; color: #64748b; margin-top: 4px; }
        .plano-upload-area strong { color: #8b5cf6; }
        .plano-preview { margin-top: .75rem; font-size: .8rem; color: #94a3b8; display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
        .plano-preview a { color: #8b5cf6; text-decoration: none; }
        .plano-preview-img { max-width: 100%; border-radius: 8px; margin-top: .5rem; border: 1px solid rgba(139,92,246,0.2); max-height: 160px; object-fit: contain; }

        /* Toast */
        #toastContainer {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999;
            display: flex; flex-direction: column; gap: .5rem;
        }
        .toast {
            display: flex; align-items: center; gap: 10px;
            padding: .75rem 1.1rem; border-radius: 10px;
            font-size: .85rem; font-weight: 500;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            animation: toastIn .25s ease;
        }
        @keyframes toastIn { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }
        .toast.success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; }
        .toast.error   { background: rgba(239,68,68,0.12);  border: 1px solid rgba(239,68,68,0.3);  color: #f87171; }

        @media (max-width: 768px) {
            .mis-wrapper { padding: 1.5rem 1rem 3rem; }
            .stats-row   { grid-template-columns: 1fr 1fr; }
            .adm-panel-header { flex-direction: column; align-items: flex-start; }
            .page-title  { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<!-- ═══ HEADER ═══ -->
<header class="adm-header">
    <a href="<?php echo SITE_URL; ?>/index.php" class="adm-logo">
        <img src="<?php echo SITE_URL; ?>/assets/img/logo.png" alt="InmoVision 3D">
        <span class="logo-text">InmoVision <span>3D</span></span>
    </a>

    <nav class="adm-header-nav">
        <a href="<?php echo SITE_URL; ?>/index.php">Inicio</a>
        <a href="<?php echo SITE_URL; ?>/views/inmuebles/listar.php">Inmuebles</a>
        <a href="<?php echo SITE_URL; ?>/views/inmuebles/publicar.php">Publicar</a>
    </nav>

    <div class="adm-profile" id="profileBtn">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['nombre'], 0, 2)); ?></div>
        <span class="pname"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        <div class="adm-dropdown" id="profileDropdown">
            <a href="<?php echo SITE_URL; ?>/views/usuario/perfil.php">Mi perfil</a>
<a href="<?php echo SITE_URL; ?>/views/usuario/mis-inmuebles.php" class="active">Mis Inmuebles</a>            <a href="<?php echo SITE_URL; ?>/views/usuario/favoritos.php">Favoritos</a>
            <a href="<?php echo SITE_URL; ?>/views/usuario/solicitudes.php">Solicitudes</a>
            <?php if ($esAdmin): ?>
            <hr>
            <a href="<?php echo SITE_URL; ?>/views/admin/dashboard.php">Panel Admin</a>
            <?php endif; ?>
            <hr>
            <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout">Cerrar sesión</a>
        </div>
    </div>
</header>

<!-- ═══ CONTENIDO ═══ -->
<main class="mis-wrapper">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?php echo SITE_URL; ?>/index.php">Inicio</a>
        <span class="sep">›</span>
        <a href="<?php echo SITE_URL; ?>/views/usuario/perfil.php">Mi cuenta</a>
        <span class="sep">›</span>
        <span class="current">Mis inmuebles</span>
    </div>

    <!-- Título + botón -->
    <div class="page-title">
        <div class="page-title-left">
            <div class="page-title-icon">
                <i class="fas fa-building" style="background:linear-gradient(135deg,#0ea5e9,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i>
            </div>
            <div>
                <h1>Mis inmuebles</h1>
                <p>Administra las propiedades que has publicado</p>
            </div>
        </div>
        <button class="btn-nuevo" onclick="abrirModalCrearInmueble()">
            <i class="fas fa-plus"></i> Nuevo inmueble
        </button>
    </div>

    <?php
    /* ── Cálculo de stats ── */
    $total = count($inmuebles);

$disponibles = count(array_filter($inmuebles, function($i) {
    return isset($i['estado']) && strtolower($i['estado']) === 'disponible';
}));

$vendidos = count(array_filter($inmuebles, function($i) {
    return isset($i['estado']) && strtolower($i['estado']) === 'vendido';
}));

$arrendados = count(array_filter($inmuebles, function($i) {
    return isset($i['estado']) && strtolower($i['estado']) === 'arrendado';
}));

$pausados = count(array_filter($inmuebles, function($i) {
    return isset($i['estado']) && strtolower($i['estado']) === 'pausado';
}));
    ?>

    <!-- Stats rápidos -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-building"></i></div>
            <div><div class="stat-num"><?php echo $total; ?></div><div class="stat-lbl">Total publicados</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
            <div><div class="stat-num"><?php echo $disponibles; ?></div><div class="stat-lbl">Disponibles</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon violet"><i class="fas fa-tag"></i></div>
            <div><div class="stat-num"><?php echo $vendidos; ?></div><div class="stat-lbl">Vendidos</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><i class="fas fa-key"></i></div>
            <div><div class="stat-num"><?php echo $arrendados; ?></div><div class="stat-lbl">Arrendados</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon slate"><i class="fas fa-pause"></i></div>
            <div><div class="stat-num"><?php echo $pausados; ?></div><div class="stat-lbl">Pausados</div></div>
        </div>
    </div>

    <!-- Panel tabla -->
    <div class="adm-panel">
        <div class="adm-panel-header">
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;flex:1">
                <div class="search-wrap">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input type="text" id="searchInmuebles" placeholder="Buscar inmuebles…">
                </div>
                <div class="filter-bar">
                    <select id="filtroTipo" onchange="filtrarTabla()">
                        <option value="">Todos los tipos</option>
                        <option value="casa">Casa</option>
                        <option value="apartamento">Apartamento</option>
                        <option value="local">Local</option>
                        <option value="oficina">Oficina</option>
                        <option value="lote">Lote</option>
                        <option value="bodega">Bodega</option>
                    </select>
                    <select id="filtroOperacion" onchange="filtrarTabla()">
                        <option value="">Venta y arriendo</option>
                        <option value="venta">Venta</option>
                        <option value="arriendo">Arriendo</option>
                    </select>
                    <select id="filtroEstado" onchange="filtrarTabla()">
                        <option value="">Todos los estados</option>
                        <option value="disponible">Disponible</option>
                        <option value="vendido">Vendido</option>
                        <option value="arrendado">Arrendado</option>
                        <option value="pausado">Pausado</option>
                    </select>
                </div>
            </div>
        </div>

        <div style="overflow-x:auto">
            <table class="adm-table" id="tablaInmuebles">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Inmueble</th>
                        <th>Ubicación</th>
                        <th>Tipo</th>
                        <th>Operación</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyInmuebles">
                    <?php if (empty($inmuebles)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">🏠</div>
                                <h3>Aún no tienes inmuebles publicados</h3>
                                <p>Publica tu primera propiedad y empieza a recibir interesados.</p>
                                <button class="btn-nuevo" onclick="abrirModalCrearInmueble()">
                                    <i class="fas fa-plus"></i> Publicar ahora
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($inmuebles as $i): ?>
                    <?php
                        [$chipOp,  $labelOp]  = chipInmueble($i['operacion'] ?? '', 'operacion');
                        [$chipEst, $labelEst] = chipInmueble($i['estado'] ?? 'disponible', 'estado');
                        $imagenes     = $inmuebleModel->obtenerImagenes($i['idInmueble']);
                        $planos       = $inmuebleModel->obtenerPlanos($i['idInmueble']);
                        $primeraImg   = !empty($imagenes) ? $imagenes[0]['urlImagen'] : '';
                        $imagenesJson = htmlspecialchars(json_encode($imagenes, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        $planosJson   = htmlspecialchars(json_encode($planos,   JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr data-tipo="<?php echo htmlspecialchars($i['tipo']); ?>"
                        data-operacion="<?php echo htmlspecialchars($i['operacion'] ?? ''); ?>"
                        data-estado="<?php echo htmlspecialchars($i['estado'] ?? ''); ?>"
                        data-id="<?php echo $i['idInmueble']; ?>"
                        data-titulo="<?php echo htmlspecialchars($i['titulo'], ENT_QUOTES); ?>"
                        data-desc="<?php echo htmlspecialchars($i['descripcion'] ?? '', ENT_QUOTES); ?>"
                        data-precio="<?php echo (float)$i['precio']; ?>"
                        data-ubicacion="<?php echo htmlspecialchars($i['ubicacion'], ENT_QUOTES); ?>"
                        data-hab="<?php echo (int)($i['habitaciones'] ?? 0); ?>"
                        data-banos="<?php echo (int)($i['banos'] ?? 0); ?>"
                        data-area="<?php echo (float)($i['area'] ?? 0); ?>"
                        data-op="<?php echo htmlspecialchars($i['operacion'] ?? '', ENT_QUOTES); ?>"
                        data-estado-val="<?php echo htmlspecialchars($i['estado'] ?? 'disponible', ENT_QUOTES); ?>"
                        data-imagenes="<?php echo $imagenesJson; ?>"
                        data-planos="<?php echo $planosJson; ?>">

                        <td style="color:#64748b;font-size:.8rem">#<?php echo $i['idInmueble']; ?></td>

                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="thumb-placeholder">
                                    <?php if ($primeraImg): ?>
                                        <img src="<?php echo htmlspecialchars($primeraImg); ?>" alt="foto">
                                    <?php else: ?>
                                        🏠
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight:500;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                        <?php echo htmlspecialchars($i['titulo']); ?>
                                    </div>
                                    <div style="font-size:.72rem;color:#64748b">
                                        <?php echo (int)($i['habitaciones'] ?? 0); ?> hab ·
                                        <?php echo (int)($i['banos'] ?? 0); ?> baños ·
                                        <?php echo $i['area'] ?? '—'; ?> m²
                                        <?php if (!empty($imagenes)): ?>
                                            · <span style="color:#0ea5e9"><?php echo count($imagenes); ?> foto<?php echo count($imagenes)>1?'s':''; ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($planos)): ?>
                                            · <span style="color:#8b5cf6">plano</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td style="color:#64748b;font-size:.8rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <?php echo htmlspecialchars($i['ubicacion']); ?>
                        </td>
                        <td><?php echo ucfirst($i['tipo']); ?></td>
                        <td><span class="chip <?php echo $chipOp; ?>"><?php echo $labelOp; ?></span></td>
                        <td class="price-cell">$<?php echo number_format($i['precio'],0,',','.'); ?></td>
                        <td><span class="chip <?php echo $chipEst; ?>"><?php echo $labelEst; ?></span></td>

                        <td>
                            <div class="tbl-actions">
                                <!-- Ver detalle -->
                                <a href="<?php echo SITE_URL; ?>/views/inmuebles/detalle.php?id=<?php echo $i['idInmueble']; ?>"
                                   target="_blank" class="btn-tbl view" title="Ver publicación">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                <!-- Editar -->
                                <button class="btn-tbl" title="Editar"
                                    onclick="abrirModalEditarInmuebleDesdeRow(this.closest('tr'))">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <!-- Eliminar -->
                                <button class="btn-tbl danger" title="Eliminar"
                                    onclick="confirmarEliminarInmueble(
                                        <?php echo $i['idInmueble']; ?>,
                                        '<?php echo htmlspecialchars(addslashes($i['titulo'])); ?>'
                                    )">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6"/><path d="M14 11v6"/>
                                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="tabla-footer">
            <span id="contadorInmuebles">
                <?php echo $total; ?> inmueble<?php echo $total !== 1 ? 's' : ''; ?>
            </span>
            <?php if ($esAdmin): ?>
            <a href="?<?php echo isset($_GET['todos']) ? '' : 'todos=1'; ?>"
               style="color:#0ea5e9;font-size:.78rem;text-decoration:none">
                <?php echo isset($_GET['todos']) ? '← Solo los míos' : 'Ver todos (admin)'; ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

</main>

<!-- ═══ Toast container ═══ -->
<div id="toastContainer"></div>


<!-- ══════════════════════════════════════════════════
     MODAL — CREAR INMUEBLE
══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalCrearInmueble">
    <div class="modal-box" style="max-width:640px">
        <div class="modal-header">
            <h2>Nuevo inmueble</h2>
            <button class="modal-close" onclick="closeModal('modalCrearInmueble')">✕</button>
        </div>

        <form id="formCrearInmueble" onsubmit="submitCrearInmueble(event)" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group span2">
                        <label>Título *</label>
                        <input type="text" name="titulo" placeholder="Ej: Apartamento moderno en el centro" required>
                    </div>
                    <div class="form-group span2">
                        <label>Descripción</label>
                        <textarea name="descripcion" rows="3" placeholder="Describe el inmueble…"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Precio (COP) *</label>
                        <input type="number" name="precio" placeholder="0" min="0" step="1000" required>
                    </div>
                    <div class="form-group">
                        <label>Área (m²)</label>
                        <input type="number" name="area" placeholder="0" min="0" step="0.5">
                    </div>
                    <div class="form-group">
                        <label>Habitaciones</label>
                        <input type="number" name="habitaciones" placeholder="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Baños</label>
                        <input type="number" name="banos" placeholder="0" min="0">
                    </div>
                    <div class="form-group span2">
                        <label>Ubicación *</label>
                        <input type="text" name="ubicacion" placeholder="Ciudad, barrio, dirección…" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select name="tipo" required>
                            <option value="">Seleccionar…</option>
                            <option value="casa">Casa</option>
                            <option value="apartamento">Apartamento</option>
                            <option value="local">Local</option>
                            <option value="oficina">Oficina</option>
                            <option value="lote">Lote</option>
                            <option value="bodega">Bodega</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Operación *</label>
                        <select name="operacion" required>
                            <option value="">Seleccionar…</option>
                            <option value="venta">Venta</option>
                            <option value="arriendo">Arriendo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado">
                            <option value="disponible">Disponible</option>
                            <option value="pausado">Pausado</option>
                        </select>
                    </div>
                    <input type="hidden" name="idPublicador" value="<?php echo $_SESSION['usuario_id']; ?>">
                </div>

                <hr class="modal-divider">

                <!-- Imágenes -->
                <div class="form-group">
                    <label>Imágenes del inmueble</label>
                    <div class="upload-zone" id="uploadZoneCrear"
                         ondragover="onDragOver(event,'uploadZoneCrear')"
                         ondragleave="onDragLeave('uploadZoneCrear')"
                         ondrop="onDrop(event,'crearImgPreview','inputImagenesCrear')">
                        <input type="file" name="imagenes[]" id="inputImagenesCrear"
                               multiple accept="image/jpeg,image/png,image/webp"
                               onchange="previewImagenes(this,'crearImgPreview')">
                        <div class="upload-zone-icon">🖼️</div>
                        <p><strong>Haz clic o arrastra</strong> imágenes aquí</p>
                        <p style="margin-top:4px">JPG, PNG o WEBP · Máx. 5 MB c/u</p>
                    </div>
                    <div class="img-preview-grid" id="crearImgPreview"></div>
                </div>

                <hr class="modal-divider">

                <!-- Plano -->
                <div class="form-group">
                    <label>Plano 2D <span style="color:#64748b;font-weight:400;font-size:.78rem">(opcional)</span></label>
                    <div class="plano-upload-area" id="uploadPlanoCrear"
                         ondragover="onDragOver(event,'uploadPlanoCrear')"
                         ondragleave="onDragLeave('uploadPlanoCrear')"
                         ondrop="onDropPlano(event,'crearPlanoPreview','inputPlanoCrear')">
                        <input type="file" name="plano_2d" id="inputPlanoCrear"
                               accept="image/*,.pdf"
                               onchange="previewPlano(this,'crearPlanoPreview')">
                        <div style="font-size:1.6rem;margin-bottom:.4rem">📐</div>
                        <p><strong>Haz clic o arrastra</strong> el plano aquí</p>
                        <p>JPG, PNG, SVG o PDF</p>
                    </div>
                    <div id="crearPlanoPreview"></div>
                </div>
            </div>

            <div class="form-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalCrearInmueble')">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnCrearInmuebleSubmit">
                    <i class="fas fa-paper-plane" style="margin-right:6px"></i>Publicar inmueble
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════════════
     MODAL — EDITAR INMUEBLE
══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalEditarInmueble">
    <div class="modal-box" style="max-width:640px">
        <div class="modal-header">
            <h2>Editar inmueble</h2>
            <button class="modal-close" onclick="closeModal('modalEditarInmueble')">✕</button>
        </div>

        <form id="formEditarInmueble" onsubmit="submitEditarInmueble(event)" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editInmuebleId">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group span2">
                        <label>Título *</label>
                        <input type="text" name="titulo" id="editInmuebleTitulo" required>
                    </div>
                    <div class="form-group span2">
                        <label>Descripción</label>
                        <textarea name="descripcion" id="editInmuebleDesc" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Precio (COP) *</label>
                        <input type="number" name="precio" id="editInmueblePrecio" min="0" step="1000" required>
                    </div>
                    <div class="form-group">
                        <label>Área (m²)</label>
                        <input type="number" name="area" id="editInmuebleArea" min="0" step="0.5">
                    </div>
                    <div class="form-group">
                        <label>Habitaciones</label>
                        <input type="number" name="habitaciones" id="editInmuebleHab" min="0">
                    </div>
                    <div class="form-group">
                        <label>Baños</label>
                        <input type="number" name="banos" id="editInmuebleBanos" min="0">
                    </div>
                    <div class="form-group span2">
                        <label>Ubicación *</label>
                        <input type="text" name="ubicacion" id="editInmuebleUbicacion" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select name="tipo" id="editInmuebleTipo" required>
                            <option value="casa">Casa</option>
                            <option value="apartamento">Apartamento</option>
                            <option value="local">Local</option>
                            <option value="oficina">Oficina</option>
                            <option value="lote">Lote</option>
                            <option value="bodega">Bodega</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Operación *</label>
                        <select name="operacion" id="editInmuebleOp" required>
                            <option value="venta">Venta</option>
                            <option value="arriendo">Arriendo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" id="editInmuebleEstado">
                            <option value="disponible">Disponible</option>
                            <option value="vendido">Vendido</option>
                            <option value="arrendado">Arrendado</option>
                            <option value="pausado">Pausado</option>
                        </select>
                    </div>
                </div>

                <hr class="modal-divider">

                <!-- Fotos existentes -->
                <div class="form-group" id="seccionImagenesExistentes">
                    <label>Fotos actuales <span id="conteoExistentes" style="color:#64748b;font-weight:400"></span></label>
                    <div class="img-preview-grid" id="editImgExistentes"></div>
                </div>
                <hr class="modal-divider" id="dividerNuevas">

                <!-- Nuevas fotos -->
                <div class="form-group">
                    <label>Agregar nuevas fotos</label>
                    <div class="upload-zone" id="uploadZoneEditar"
                         ondragover="onDragOver(event,'uploadZoneEditar')"
                         ondragleave="onDragLeave('uploadZoneEditar')"
                         ondrop="onDrop(event,'editImgPreview','inputImagenesEditar')">
                        <input type="file" name="imagenes[]" id="inputImagenesEditar"
                               multiple accept="image/jpeg,image/png,image/webp"
                               onchange="previewImagenes(this,'editImgPreview')">
                        <div class="upload-zone-icon">➕</div>
                        <p><strong>Haz clic o arrastra</strong> más imágenes</p>
                        <p style="margin-top:4px">JPG, PNG o WEBP · Máx. 5 MB c/u</p>
                    </div>
                    <div class="img-preview-grid" id="editImgPreview"></div>
                </div>

                <hr class="modal-divider">

                <!-- Plano en edición -->
                <div class="form-group">
                    <label>Plano 2D <span style="color:#64748b;font-weight:400;font-size:.78rem">(reemplaza el actual si subes uno nuevo)</span></label>
                    <div id="editPlanoActual" style="margin-bottom:.75rem"></div>
                    <div class="plano-upload-area" id="uploadPlanoEditar"
                         ondragover="onDragOver(event,'uploadPlanoEditar')"
                         ondragleave="onDragLeave('uploadPlanoEditar')"
                         ondrop="onDropPlano(event,'editPlanoPreview','inputPlanoEditar')">
                        <input type="file" name="plano_2d" id="inputPlanoEditar"
                               accept="image/*,.pdf"
                               onchange="previewPlano(this,'editPlanoPreview')">
                        <div style="font-size:1.6rem;margin-bottom:.4rem">📐</div>
                        <p><strong>Haz clic o arrastra</strong> el plano aquí</p>
                        <p>JPG, PNG, SVG o PDF</p>
                    </div>
                    <div id="editPlanoPreview"></div>
                </div>
            </div>

            <div class="form-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalEditarInmueble')">Cancelar</button>
                <button type="submit" class="btn-primary" id="btnEditarInmuebleSubmit">
                    <i class="fas fa-floppy-disk" style="margin-right:6px"></i>Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ══════════════════════════════════════════════════
     MODAL — CONFIRMAR ELIMINAR
══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalEliminarInmueble">
    <div class="modal-box" style="max-width:420px;text-align:center">
        <div class="modal-body" style="padding-top:2rem;padding-bottom:.5rem">
            <div style="width:52px;height:52px;border-radius:50%;background:rgba(239,68,68,0.12);
                        color:#ef4444;font-size:1.5rem;margin:0 auto 1rem;
                        display:flex;align-items:center;justify-content:center">⚠</div>
            <h2 style="font-size:1.05rem;margin-bottom:.5rem">¿Eliminar inmueble?</h2>
            <p id="eliminarInmuebleMsg" style="color:#64748b;font-size:.875rem">
                Esta acción eliminará el inmueble y sus imágenes.
            </p>
        </div>
        <div class="form-footer" style="justify-content:center">
            <button class="btn-cancel" onclick="closeModal('modalEliminarInmueble')">Cancelar</button>
            <button class="btn-primary" id="btnConfirmarEliminarInmueble"
                    style="background:linear-gradient(135deg,#ef4444,#b91c1c)">
                Sí, eliminar
            </button>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════ -->
<script>
/* ── Dropdown perfil ── */
const profileBtn = document.getElementById('profileBtn');
const dropdown   = document.getElementById('profileDropdown');
profileBtn?.addEventListener('click', e => { e.stopPropagation(); dropdown.classList.toggle('open'); });
document.addEventListener('click', () => dropdown?.classList.remove('open'));

/* ── Toast ── */
function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i> ${msg}`;
    document.getElementById('toastContainer').appendChild(t);
    setTimeout(() => t.remove(), 3500);
}

/* ── Modal helpers ── */
function openModal(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = '';       }

/* ── Filtros ── */
function filtrarTabla() {
    const q   = (document.getElementById('searchInmuebles').value || '').toLowerCase();
    const tip = document.getElementById('filtroTipo').value.toLowerCase();
    const op  = document.getElementById('filtroOperacion').value.toLowerCase();
    const est = document.getElementById('filtroEstado').value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('#tbodyInmuebles tr[data-tipo]').forEach(tr => {
        const ok = (!q   || tr.textContent.toLowerCase().includes(q))
                && (!tip || tr.dataset.tipo      === tip)
                && (!op  || tr.dataset.operacion === op)
                && (!est || tr.dataset.estado    === est);
        tr.style.display = ok ? '' : 'none';
        if (ok) vis++;
    });
    const c = document.getElementById('contadorInmuebles');
    if (c) c.textContent = vis + ' inmueble' + (vis !== 1 ? 's' : '');
}
document.getElementById('searchInmuebles').addEventListener('input', filtrarTabla);

/* ── Drag & drop ── */
function onDragOver(e, zoneId)  { e.preventDefault(); document.getElementById(zoneId).classList.add('drag-over'); }
function onDragLeave(zoneId)    { document.getElementById(zoneId).classList.remove('drag-over'); }
function onDrop(e, gridId, inputId) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    const input = document.getElementById(inputId);
    const dt2 = new DataTransfer();
    if (_dt[gridId]) Array.from(_dt[gridId].files).forEach(f => dt2.items.add(f));
    Array.from(e.dataTransfer.files).forEach(f => dt2.items.add(f));
    input.files = dt2.files;
    previewImagenes(input, gridId);
}
function onDropPlano(e, previewId, inputId) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    const input = document.getElementById(inputId);
    const dt = new DataTransfer();
    if (e.dataTransfer.files[0]) dt.items.add(e.dataTransfer.files[0]);
    input.files = dt.files;
    previewPlano(input, previewId);
}

/* ── Preview imágenes ── */
const _dt = {};
function previewImagenes(input, gridId) {
    const grid = document.getElementById(gridId);
    if (!_dt[gridId]) _dt[gridId] = new DataTransfer();
    Array.from(input.files).forEach(file => {
        if (!file.type.startsWith('image/')) return;
        _dt[gridId].items.add(file);
        const reader = new FileReader();
        reader.onload = e => {
            const item = document.createElement('div');
            item.className = 'img-preview-item';
            item.dataset.name = file.name;
            item.innerHTML = `
                <img src="${e.target.result}" alt="">
                <button type="button" class="img-remove" onclick="quitarNueva(this,'${gridId}','${input.id}')">✕</button>
                <span class="img-badge">nueva</span>`;
            grid.appendChild(item);
        };
        reader.readAsDataURL(file);
    });
    input.files = _dt[gridId].files;
}
function quitarNueva(btn, gridId, inputId) {
    const item = btn.closest('.img-preview-item');
    const name = item.dataset.name;
    const dt = new DataTransfer();
    Array.from(_dt[gridId].files).forEach(f => { if (f.name !== name) dt.items.add(f); });
    _dt[gridId] = dt;
    document.getElementById(inputId).files = dt.files;
    item.remove();
}

/* ── Preview plano ── */
function previewPlano(input, previewId) {
    const prev = document.getElementById(previewId);
    prev.innerHTML = '';
    const file = input.files[0];
    if (!file) return;
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
            prev.innerHTML = `<img src="${e.target.result}" class="plano-preview-img" alt="plano">
                              <p style="font-size:.75rem;color:#64748b;margin-top:.4rem">📐 ${file.name}</p>`;
        };
        reader.readAsDataURL(file);
    } else {
        prev.innerHTML = `<div class="plano-preview">📄 <span>${file.name}</span></div>`;
    }
}

/* ── Imágenes existentes (editar) ── */
let _imagenesAEliminar = [];
const SITE_URL = '<?php echo rtrim(SITE_URL,"/"); ?>';

function renderImagenesExistentes(imagenes) {
    _imagenesAEliminar = [];
    const grid = document.getElementById('editImgExistentes');
    grid.innerHTML = '';
    document.querySelectorAll('#formEditarInmueble input[name="eliminar_imagen[]"]').forEach(el => el.remove());

    const seccion = document.getElementById('seccionImagenesExistentes');
    const divider = document.getElementById('dividerNuevas');
    if (!imagenes || imagenes.length === 0) {
        seccion.style.display = 'none'; divider.style.display = 'none'; return;
    }
    seccion.style.display = ''; divider.style.display = '';
    document.getElementById('conteoExistentes').textContent = '(' + imagenes.length + ')';

    imagenes.forEach(img => {
        const urlImg = (img.urlImagen && img.urlImagen.startsWith('http'))
            ? img.urlImagen
            : SITE_URL + '/assets/uploads/inmuebles/' + img.urlImagen;
        const item = document.createElement('div');
        item.className = 'img-preview-item img-existing-item';
        item.dataset.idImagen = img.idImagen;
        item.innerHTML = `
            <img src="${urlImg}" alt=""
                 onerror="this.parentElement.querySelector('.img-badge').textContent='sin foto'">
            <button type="button" class="img-remove" onclick="marcarEliminarImagen(this,${img.idImagen})">✕</button>
            <span class="img-badge">${img.es_principal == 1 ? 'principal' : 'guardada'}</span>`;
        grid.appendChild(item);
    });
}
function marcarEliminarImagen(btn, idImagen) {
    const item = btn.closest('.img-preview-item');
    if (_imagenesAEliminar.includes(idImagen)) {
        _imagenesAEliminar = _imagenesAEliminar.filter(x => x !== idImagen);
        item.style.opacity = '1'; item.style.border = '1px solid rgba(34,197,94,0.3)';
        document.querySelector(`#formEditarInmueble input[name="eliminar_imagen[]"][value="${idImagen}"]`)?.remove();
    } else {
        _imagenesAEliminar.push(idImagen);
        item.style.opacity = '.35'; item.style.border = '1px solid rgba(239,68,68,0.4)';
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = 'eliminar_imagen[]'; hidden.value = idImagen;
        document.getElementById('formEditarInmueble').appendChild(hidden);
    }
}

/* ── Plano existente ── */
function renderPlanoActual(planos) {
    const div = document.getElementById('editPlanoActual');
    if (!planos || planos.length === 0) { div.innerHTML = ''; return; }
    const p = planos[0];
    const esImg = /\.(jpg|jpeg|png|webp|svg)$/i.test(p.archivo);
    const url   = p.archivo.startsWith('http') ? p.archivo : SITE_URL + '/' + p.archivo;
    div.innerHTML = `
        <div class="plano-preview">
            📐 Plano actual:
            <a href="${url}" target="_blank">${p.nombre || p.archivo}</a>
        </div>
        ${esImg ? `<img src="${url}" class="plano-preview-img" alt="plano actual">` : ''}`;
}

/* ── Abrir modales ── */
function abrirModalCrearInmueble() {
    document.getElementById('formCrearInmueble').reset();
    document.getElementById('crearImgPreview').innerHTML = '';
    document.getElementById('crearPlanoPreview').innerHTML = '';
    _dt['crearImgPreview'] = new DataTransfer();
    openModal('modalCrearInmueble');
}
function abrirModalEditarInmuebleDesdeRow(tr) {
    const d = tr.dataset;
    document.getElementById('editInmuebleId').value        = d.id;
    document.getElementById('editInmuebleTitulo').value    = d.titulo;
    document.getElementById('editInmuebleDesc').value      = d.desc;
    document.getElementById('editInmueblePrecio').value    = d.precio;
    document.getElementById('editInmuebleArea').value      = d.area;
    document.getElementById('editInmuebleHab').value       = d.hab;
    document.getElementById('editInmuebleBanos').value     = d.banos;
    document.getElementById('editInmuebleUbicacion').value = d.ubicacion;
    document.getElementById('editInmuebleTipo').value      = d.tipo;
    document.getElementById('editInmuebleOp').value        = d.op;
    document.getElementById('editInmuebleEstado').value    = d.estadoVal;

    document.getElementById('editImgPreview').innerHTML   = '';
    document.getElementById('editPlanoPreview').innerHTML = '';
    _dt['editImgPreview'] = new DataTransfer();
    document.getElementById('inputImagenesEditar').value  = '';
    document.getElementById('inputPlanoEditar').value     = '';

    let imagenes = [], planos = [];
    try { imagenes = JSON.parse(d.imagenes); } catch(e) {}
    try { planos   = JSON.parse(d.planos);   } catch(e) {}
    renderImagenesExistentes(imagenes);
    renderPlanoActual(planos);
    openModal('modalEditarInmueble');
}

/* ── Eliminar ── */
let _eliminarInmuebleId = null;
function confirmarEliminarInmueble(id, titulo) {
    _eliminarInmuebleId = id;
    document.getElementById('eliminarInmuebleMsg').textContent =
        `Vas a eliminar "${titulo}" y todas sus fotos. Esta acción no se puede deshacer.`;
    openModal('modalEliminarInmueble');
}
document.getElementById('btnConfirmarEliminarInmueble').addEventListener('click', async () => {
    if (!_eliminarInmuebleId) return;
    const btn = document.getElementById('btnConfirmarEliminarInmueble');
    btn.disabled = true; btn.textContent = 'Eliminando…';
    try {
        const res  = await fetch(`${SITE_URL}/Api/InmueblesApi.php?id=${_eliminarInmuebleId}`, { method: 'DELETE' });
        const data = await res.json();
        closeModal('modalEliminarInmueble');
        data.success
            ? (showToast('Inmueble eliminado', 'success'), setTimeout(() => location.reload(), 900))
            : showToast(data.error || 'Error al eliminar', 'error');
    } catch { showToast('Error de conexión', 'error'); }
    finally  { btn.disabled = false; btn.textContent = 'Sí, eliminar'; _eliminarInmuebleId = null; }
});

/* ── Crear ── */
async function submitCrearInmueble(e) {
    e.preventDefault();
    const btn = document.getElementById('btnCrearInmuebleSubmit');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px"></i>Publicando…';
    try {
        const form = new FormData(document.getElementById('formCrearInmueble'));
        const res  = await fetch(`${SITE_URL}/Api/InmueblesApi.php`, { method: 'POST', body: form });
        const data = await res.json();
        if (data.success) {
            closeModal('modalCrearInmueble');
            showToast('Inmueble publicado correctamente', 'success');
            setTimeout(() => location.reload(), 900);
        } else { showToast(data.error || 'Error al crear', 'error'); }
    } catch { showToast('Error de conexión', 'error'); }
    finally  { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane" style="margin-right:6px"></i>Publicar inmueble'; }
}

/* ── Editar ── */
async function submitEditarInmueble(e) {
    e.preventDefault();
    const btn = document.getElementById('btnEditarInmuebleSubmit');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px"></i>Guardando…';
    try {
        const id   = document.getElementById('editInmuebleId').value;
        const form = new FormData(document.getElementById('formEditarInmueble'));
        const res  = await fetch(`${SITE_URL}/Api/InmueblesApi.php?id=${id}`, { method: 'PUT', body: form });
        const data = await res.json();
        if (data.success) {
            closeModal('modalEditarInmueble');
            showToast('Inmueble actualizado correctamente', 'success');
            setTimeout(() => location.reload(), 900);
        } else { showToast(data.error || 'Error al actualizar', 'error'); }
    } catch { showToast('Error de conexión', 'error'); }
    finally  { btn.disabled = false; btn.innerHTML = '<i class="fas fa-floppy-disk" style="margin-right:6px"></i>Guardar cambios'; }
}

/* ── Escape ── */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
        ['modalCrearInmueble','modalEditarInmueble','modalEliminarInmueble'].forEach(closeModal);
});

/* ── Cerrar modal al click en overlay ── */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal(overlay.id);
    });
});
</script>
</body>
</html>