<?php
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Inmueble.php';
require_once __DIR__ . '/../../models/Plano.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . SITE_URL . 'views/auth/login.php');
    exit;
}
if ($_SESSION['rol'] === 'cliente') {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

$inmuebleModel = new Inmueble();
$planoModel    = new Plano();

$mensaje  = '';
$error    = '';
$editMode = false;
$inmueble = null;

if (isset($_GET['edit'])) {
    $editMode = true;
    $inmueble = $inmuebleModel->obtenerPorId(intval($_GET['edit']));
    if (!$inmueble || ($inmueble['idPublicador'] != $_SESSION['usuario_id'] && $_SESSION['rol'] !== 'admin')) {
        header('Location: ' . SITE_URL . '/views/inmuebles/listar.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'titulo'       => trim($_POST['titulo']),
        'descripcion'  => trim($_POST['descripcion']),
        'tipo'         => $_POST['tipo'],
        'operacion'       => $_POST['operacion'],
        'estado'       => 'disponible',   
        'precio'       => floatval($_POST['precio']),
        'area'         => floatval($_POST['area']),
        'habitaciones' => intval($_POST['habitaciones']),
        'banos'        => intval($_POST['banos']),
        'ubicacion'    => trim($_POST['ubicacion'] ?? ''),
        'idPublicador' => $_SESSION['usuario_id']
    ];

    if (empty($datos['titulo']) || empty($datos['descripcion'])) {
        $error = 'Por favor complete todos los campos obligatorios';
    } elseif ($datos['precio'] <= 0) {
        $error = 'El precio debe ser mayor a 0';
    } else {
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
            $ext       = strtolower(pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $permitidas)) {
                $nombreArchivo = 'inmueble_' . time() . '_' . uniqid() . '.' . $ext;
                $rutaDestino   = __DIR__ . '/../../assets/uploads/inmuebles/' . $nombreArchivo;
                if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $rutaDestino))
                    $datos['imagen_principal'] = $nombreArchivo;
            }
        }

        if ($editMode) {
            if ($inmuebleModel->actualizar($inmueble['idInmueble'], $datos)) {
                $inmuebleId = $inmueble['idInmueble'];
                $mensaje    = 'Inmueble actualizado correctamente';
            } else {
                $error = 'Error al actualizar el inmueble';
            }
        } else {
            $inmuebleId = $inmuebleModel->crear($datos);
            if ($inmuebleId) $mensaje = 'Inmueble publicado correctamente';
            else             $error   = 'Error al publicar el inmueble';
        }

        // Guardar imagen principal en Imagenes_inmueble con es_principal = 1
        if (!$error && isset($inmuebleId) && !empty($datos['imagen_principal'])) {
            $inmuebleModel->agregarImagenPrincipal($inmuebleId, $datos['imagen_principal']);
        }

        if (!$error && isset($inmuebleId) && isset($_FILES['imagenes_adicionales'])) {
            $files = $_FILES['imagenes_adicionales'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $ext        = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    $permitidas = ['jpg','jpeg','png','webp'];
                    if (in_array($ext, $permitidas)) {
                        $nombreArchivo = 'inmueble_' . $inmuebleId . '_' . time() . '_' . $i . '.' . $ext;
                        $rutaDestino   = __DIR__ . '/../../assets/uploads/inmuebles/' . $nombreArchivo;
                        if (move_uploaded_file($files['tmp_name'][$i], $rutaDestino))
                            $inmuebleModel->agregarImagen($inmuebleId, $nombreArchivo);
                    }
                }
            }
        }

        if (!$error && isset($inmuebleId) && isset($_FILES['plano_2d']) && $_FILES['plano_2d']['error'] === UPLOAD_ERR_OK) {
            $ext        = strtolower(pathinfo($_FILES['plano_2d']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg','jpeg','png','svg','pdf','webp'];
            if (in_array($ext, $permitidas)) {
                $nombrePlano = 'plano_' . $inmuebleId . '_' . time() . '.' . $ext;
                $rutaPlano   = __DIR__ . '/../../assets/uploads/planos/' . $nombrePlano;
                if (move_uploaded_file($_FILES['plano_2d']['tmp_name'], $rutaPlano)) {
                    // Si ya había un plano, lo reemplazamos por el nuevo
                    foreach ($planoModel->obtenerPorInmueble($inmuebleId) as $p) {
                        $planoModel->eliminar($p['id']);
                    }
                    $planoModel->crear([
                        'inmueble_id' => $inmuebleId,
                        'nombre'      => 'Plano de ' . $datos['titulo'],
                        'archivo'     => 'assets/uploads/planos/' . $nombrePlano,
                        'tipo'        => 'subido'
                    ]);
                }
            }
        }

        if ($mensaje && !$editMode) {
            header('Location: ' . SITE_URL . 'views/inmuebles/detalle.php?id=' . $inmuebleId . '&nuevo=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Editar' : 'Publicar'; ?> Inmueble — InmoVision3D</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* ════ HEADER (idéntico al dashboard) ════ */
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
        .adm-dropdown hr { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 4px 0; }

        /* ════ LAYOUT PRINCIPAL ════ */
        .pub-wrapper {
            max-width: 820px;
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
        .pub-title {
            display: flex; align-items: center; gap: 1rem;
            margin-bottom: 2rem;
        }
        .pub-title-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: linear-gradient(135deg, rgba(14,165,233,0.2), rgba(249,115,22,0.2));
            border: 1px solid rgba(14,165,233,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem;
            background-image: linear-gradient(135deg, #0ea5e9, #f97316);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .pub-title h1 { font-size: 1.5rem; font-weight: 700; }
        .pub-title p  { font-size: 0.82rem; color: #64748b; margin-top: 2px; }

        /* Toast (error / éxito) */
        .alert {
            display: flex; align-items: center; gap: 10px;
            padding: 1rem 1.25rem; border-radius: 10px;
            font-size: 0.875rem; font-weight: 500;
            margin-bottom: 1.5rem;
        }
        .alert-error   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.25);  color: #f87171; }
        .alert-success { background: rgba(34,197,94,0.1);  border: 1px solid rgba(34,197,94,0.25);  color: #4ade80; }

        /* ════ TARJETA / SECCIÓN ════ */
        .pub-card {
            background: #1e293b;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 1.25rem;
        }
        .pub-card-header {
            display: flex; align-items: center; gap: 10px;
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: rgba(255,255,255,0.02);
        }
        .pub-card-header .card-icon {
            width: 32px; height: 32px; border-radius: 8px;
            background: rgba(14,165,233,0.12);
            display: flex; align-items: center; justify-content: center;
            color: #0ea5e9; font-size: 0.85rem; flex-shrink: 0;
        }
        .pub-card-header h2 {
            font-size: 0.95rem; font-weight: 600; color: #e2e8f0;
        }
        .pub-card-body { padding: 1.5rem; }

        /* ════ GRID DE CAMPOS ════ */
        .fgrid   { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .fgrid-3 { grid-template-columns: 1fr 1fr 1fr; }
        .span2   { grid-column: span 2; }
        .span3   { grid-column: span 3; }

        /* ════ CAMPO ════ */
        .fgroup { display: flex; flex-direction: column; gap: 6px; }
        .fgroup label {
            font-size: 0.78rem; font-weight: 600; color: #64748b;
            text-transform: uppercase; letter-spacing: .04em;
        }
        .fgroup label .req { color: #f97316; }

        .fgroup input,
        .fgroup select,
        .fgroup textarea {
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
        .fgroup input::placeholder,
        .fgroup textarea::placeholder { color: #334155; }
        .fgroup input:focus,
        .fgroup select:focus,
        .fgroup textarea:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14,165,233,0.12);
        }
        .fgroup select option { background: #1e293b; }
        .fgroup textarea { min-height: 110px; resize: vertical; }

        /* Input con prefijo */
        .input-prefix {
            position: relative;
        }
        .input-prefix .prefix {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #475569; font-size: 0.8rem; font-weight: 600; pointer-events: none;
        }
        .input-prefix input { padding-left: 36px; }

        /* ════ TIPO / ESTADO como botones ════ */
        .btn-radio-group {
            display: flex; gap: 8px; flex-wrap: wrap;
        }
        .btn-radio {
            flex: 1; min-width: 90px;
            padding: 9px 14px;
            background: #0f172a;
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 9px;
            color: #64748b;
            font-family: 'Poppins', sans-serif;
            font-size: 0.8rem; font-weight: 500;
            cursor: pointer; text-align: center;
            transition: all .18s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-radio:hover { border-color: rgba(14,165,233,0.4); color: #cbd5e1; }
        .btn-radio.selected {
            background: rgba(14,165,233,0.12);
            border-color: #0ea5e9; color: #0ea5e9;
        }
        .btn-radio.selected-orange {
            background: rgba(249,115,22,0.12);
            border-color: #f97316; color: #f97316;
        }
        /* Hidden selects reales */
        .hidden-select { display: none; }

        /* ════ UPLOAD AREA ════ */
        .upload-area {
            border: 2px dashed rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 2rem 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all .22s;
            background: #0f172a;
            position: relative;
        }
        .upload-area:hover {
            border-color: #0ea5e9;
            background: rgba(14,165,233,0.04);
        }
        .upload-area.dragover {
            border-color: #f97316;
            background: rgba(249,115,22,0.05);
        }
        .upload-area input[type="file"] { display: none; }

        .upload-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(14,165,233,0.1);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 1.3rem; color: #0ea5e9;
            margin-bottom: 0.75rem;
        }
        .upload-area h4 { font-size: 0.875rem; font-weight: 600; color: #e2e8f0; margin-bottom: 4px; }
        .upload-area p  { font-size: 0.75rem; color: #475569; }
        .upload-area .browse-link { color: #0ea5e9; font-weight: 600; }

        /* Preview grid */
        .upload-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(88px, 1fr));
            gap: 8px; margin-top: 1rem;
        }
        .preview-item {
            position: relative; aspect-ratio: 1;
            border-radius: 8px; overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .preview-item .remove-btn {
            position: absolute; top: 4px; right: 4px;
            width: 22px; height: 22px;
            background: rgba(15,23,42,0.85);
            color: #f87171; border: none; border-radius: 50%;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.65rem; transition: background .15s;
        }
        .preview-item .remove-btn:hover { background: rgba(239,68,68,0.8); color: white; }

        /* ════ PLANO OPCIONES ════ */
        .plano-opts { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; }
        .plano-opt {
            padding: 1.25rem;
            background: #0f172a;
            border: 2px solid rgba(255,255,255,0.08);
            border-radius: 12px; cursor: pointer; text-align: center;
            transition: all .2s;
        }
        .plano-opt:hover { border-color: rgba(14,165,233,0.35); }
        .plano-opt.active {
            border-color: #0ea5e9;
            background: rgba(14,165,233,0.06);
        }
        .plano-opt i { font-size: 1.6rem; color: #0ea5e9; margin-bottom: 8px; display: block; }
        .plano-opt h4 { font-size: 0.875rem; font-weight: 600; color: #e2e8f0; margin-bottom: 4px; }
        .plano-opt p  { font-size: 0.75rem; color: #475569; }

        .plano-section { display: none; }
        .plano-section.active { display: block; }

        .plano-draw-placeholder {
            background: #0f172a; border-radius: 10px;
            padding: 1.5rem; text-align: center;
        }
        .plano-draw-placeholder p { color: #475569; font-size: 0.82rem; margin-bottom: 1rem; }
        .checkbox-row {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.82rem; color: #94a3b8; cursor: pointer;
        }
        .checkbox-row input { width: 15px; height: 15px; accent-color: #0ea5e9; }

        /* ════ FOOTER DEL FORM ════ */
        .pub-form-footer {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; margin-top: 2rem;
            flex-wrap: wrap;
        }
        .btn-cancel {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 22px; border-radius: 9px;
            border: 1px solid rgba(255,255,255,0.1);
            background: transparent; color: #64748b;
            font-family: 'Poppins', sans-serif; font-size: 0.875rem; font-weight: 500;
            text-decoration: none; cursor: pointer;
            transition: all .18s;
        }
        .btn-cancel:hover { background: rgba(255,255,255,0.05); color: #94a3b8; }

        .btn-submit {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 12px 28px; border-radius: 9px;
            border: none;
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            color: #fff; font-family: 'Poppins', sans-serif;
            font-size: 0.9rem; font-weight: 600; cursor: pointer;
            transition: opacity .18s, transform .15s;
            box-shadow: 0 4px 20px rgba(14,165,233,0.25);
        }
        .btn-submit:hover { opacity: .88; transform: translateY(-1px); }
        .btn-submit:active { transform: translateY(0); }

        /* ════ RESPONSIVE ════ */
        @media (max-width: 768px) {
            .adm-header { padding: 0 1rem; }
            .adm-header-nav { display: none; }
            .pub-wrapper { padding: 1.5rem 1rem 3rem; }
            .fgrid, .fgrid.fgrid-3, .plano-opts { grid-template-columns: 1fr; }
            .span2, .span3 { grid-column: span 1; }
            .pub-form-footer { flex-direction: column-reverse; }
            .btn-cancel, .btn-submit { width: 100%; justify-content: center; }
        }
        @media (max-width: 500px) {
            .btn-radio-group { flex-direction: column; }
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
        <a href="<?php echo SITE_URL; ?>/views/inmuebles/publicar.php" class="active">Publicar</a>
        <?php if ($_SESSION['rol'] === 'admin'): ?>
        <?php endif; ?>
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
            <?php if ($_SESSION['rol'] === 'admin'): ?>
            <hr>
            <a href="<?php echo SITE_URL; ?>/views/admin/dashboard.php">Panel Admin</a>
            <?php endif; ?>
            <hr>
            <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout">Cerrar sesión</a>
        </div>
    </div>
</header>

<!-- ═══ CONTENIDO ═══ -->
<main class="pub-wrapper">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?php echo SITE_URL; ?>/index.php" class="nav-link">Inicio</a>
        <span class="sep">›</span>
        <a href="<?php echo SITE_URL; ?>/views/inmuebles/listar.php" class="nav-link">Inmuebles</a>
        <span class="sep">›</span>
        <span class="current"><?php echo $editMode ? 'Editar inmueble' : 'Publicar inmueble'; ?></span>
    </div>

    <!-- Título de página -->
    <div class="pub-title">
        <div class="pub-title-icon">
            <i class="fas fa-<?php echo $editMode ? 'pen-to-square' : 'plus-circle'; ?>"
               style="background:linear-gradient(135deg,#0ea5e9,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i>
        </div>
        <div>
            <h1><?php echo $editMode ? 'Editar inmueble' : 'Publicar nuevo inmueble'; ?></h1>
            <p><?php echo $editMode ? 'Actualiza los datos de tu propiedad' : 'Completa la información para que tus clientes encuentren tu propiedad'; ?></p>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    <?php if ($mensaje): ?>
    <div class="alert alert-success">
        <i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <!-- ── Información básica ── -->
        <div class="pub-card">
            <div class="pub-card-header">
                <div class="card-icon"><i class="fas fa-circle-info"></i></div>
                <h2>Información básica</h2>
            </div>
            <div class="pub-card-body">
                <div class="fgrid">

                    <div class="fgroup span2">
                        <label for="titulo">Título del inmueble <span class="req">*</span></label>
                        <input type="text" id="titulo" name="titulo" required
                               value="<?php echo htmlspecialchars($inmueble['titulo'] ?? ''); ?>"
                               placeholder="Ej: Hermoso apartamento en el centro">
                    </div>

                    <!-- TIPO como botones visuales -->
                    <div class="fgroup">
                        <label>Tipo de inmueble <span class="req">*</span></label>
                        <select name="tipo" id="tipo-select" class="hidden-select" required>
                            <option value="casa"        <?php echo ($inmueble['tipo'] ?? '') === 'casa'        ? 'selected' : ''; ?>>Casa</option>
                            <option value="apartamento" <?php echo ($inmueble['tipo'] ?? '') === 'apartamento' ? 'selected' : ''; ?>>Apartamento</option>
                            <option value="local"       <?php echo ($inmueble['tipo'] ?? '') === 'local'       ? 'selected' : ''; ?>>Local</option>
                            <option value="oficina"     <?php echo ($inmueble['tipo'] ?? '') === 'oficina'     ? 'selected' : ''; ?>>Oficina</option>
                            <option value="terreno"     <?php echo ($inmueble['tipo'] ?? '') === 'terreno'     ? 'selected' : ''; ?>>Terreno</option>
                        </select>
                        <div class="btn-radio-group" id="tipo-btns">
                            <button type="button" class="btn-radio" data-val="casa"><i class="fas fa-house"></i> Casa</button>
                            <button type="button" class="btn-radio" data-val="apartamento"><i class="fas fa-building"></i> Apto</button>
                            <button type="button" class="btn-radio" data-val="local"><i class="fas fa-store"></i> Local</button>
                            <button type="button" class="btn-radio" data-val="oficina"><i class="fas fa-briefcase"></i> Oficina</button>
                            <button type="button" class="btn-radio" data-val="terreno"><i class="fas fa-map"></i> Terreno</button>
                        </div>
                    </div>

                    <!-- OPERACION -->
                    <div class="fgroup">
                        <label>Disponibilidad <span class="req">*</span></label>
                        <select name="operacion" id="estado-select" class="hidden-select" required>
                            <option value="venta"   <?php echo ($inmueble['operacion'] ?? '') === 'venta'   ? 'selected' : ''; ?>>En Venta</option>
                            <option value="arriendo"<?php echo ($inmueble['operacion'] ?? '') === 'arriendo'? 'selected' : ''; ?>>En Arriendo</option>
                        </select>
                        <div class="btn-radio-group" id="estado-btns">
                            <button type="button" class="btn-radio" data-val="venta"><i class="fas fa-tag"></i> En venta</button>
                            <button type="button" class="btn-radio" data-val="arriendo"><i class="fas fa-key"></i> En arriendo</button>
                        </div>
                    </div>

                    <div class="fgroup span2">
                        <label for="descripcion">Descripción <span class="req">*</span></label>
                        <textarea id="descripcion" name="descripcion" required
                                  placeholder="Describe las características del inmueble, acabados, entorno, ventajas..."><?php echo htmlspecialchars($inmueble['descripcion'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Características ── -->
        <div class="pub-card">
            <div class="pub-card-header">
                <div class="card-icon"><i class="fas fa-sliders"></i></div>
                <h2>Características</h2>
            </div>
            <div class="pub-card-body">
                <div class="fgrid fgrid-3">
                    <div class="fgroup">
                        <label for="precio">Precio (COP) <span class="req">*</span></label>
                        <div class="input-prefix">
                            <span class="prefix">$</span>
                            <input type="number" id="precio" name="precio" required min="0" step="1000"
                                   value="<?php echo $inmueble['precio'] ?? ''; ?>"
                                   placeholder="150 000 000">
                        </div>
                    </div>
                    <div class="fgroup">
                        <label for="area">Área <span class="req">*</span></label>
                        <div class="input-prefix">
                            <span class="prefix" style="font-size:.7rem;">m²</span>
                            <input type="number" id="area" name="area" required min="0" step="0.01"
                                   value="<?php echo $inmueble['area'] ?? ''; ?>"
                                   placeholder="85">
                        </div>
                    </div>
                    <div class="fgroup">
                        <label for="habitaciones">Habitaciones</label>
                        <input type="number" id="habitaciones" name="habitaciones" min="0"
                               value="<?php echo $inmueble['habitaciones'] ?? 0; ?>">
                    </div>
                    <div class="fgroup">
                        <label for="banos">Baños</label>
                        <input type="number" id="banos" name="banos" min="0"
                               value="<?php echo $inmueble['banos'] ?? 0; ?>">
                    </div>
                    <div class="fgroup span2">
                        <label for="ubicacion">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion"
                               value="<?php echo htmlspecialchars($inmueble['ubicacion'] ?? ''); ?>"
                               placeholder="Ej: Chapinero, Bogotá">
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Imágenes ── -->
        <div class="pub-card">
            <div class="pub-card-header">
                <div class="card-icon"><i class="fas fa-images"></i></div>
                <h2>Imágenes</h2>
            </div>
            <div class="pub-card-body" style="display:flex;flex-direction:column;gap:1.25rem;">

                <div class="fgroup">
                    <label>Imagen principal <span class="req">*</span></label>
                    <div class="upload-area" id="upload-principal"
                         onclick="document.getElementById('imagen_principal').click()">
                        <div class="upload-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                        <h4>Arrastra o <span class="browse-link">selecciona</span> tu imagen principal</h4>
                        <p>JPG, PNG o WEBP · Máx. 5 MB</p>
                        <input type="file" id="imagen_principal" name="imagen_principal"
                               accept="image/*" <?php echo $editMode ? '' : 'required'; ?>>
                    </div>
                    <div class="upload-preview" id="preview-principal"></div>
                </div>

                <div class="fgroup">
                    <label>Imágenes adicionales <span style="color:#334155;font-weight:400;">(opcional)</span></label>
                    <div class="upload-area" id="upload-adicionales"
                         onclick="document.getElementById('imagenes_adicionales').click()">
                        <div class="upload-icon" style="background:rgba(249,115,22,0.1);color:#f97316;">
                            <i class="fas fa-photo-film"></i>
                        </div>
                        <h4>Agrega más fotos del inmueble</h4>
                        <p>Hasta 10 imágenes · JPG, PNG o WEBP</p>
                        <input type="file" id="imagenes_adicionales" name="imagenes_adicionales[]"
                               accept="image/*" multiple>
                    </div>
                    <div class="upload-preview" id="preview-adicionales"></div>
                </div>

            </div>
        </div>

        <!-- ── Plano ── -->
        <div class="pub-card">
            <div class="pub-card-header">
                <div class="card-icon"><i class="fas fa-cube"></i></div>
                <h2>Plano 2D <span class="req">*</span></h2>
            </div>
            <div class="pub-card-body">
                <p style="font-size:.82rem;color:#475569;margin-bottom:1.25rem;">
                    Sube un plano o dibuja uno para que tus clientes puedan recorrer el inmueble.
                </p>

                <div class="plano-opts">
                    <div class="plano-opt" id="opt-upload" onclick="selectPlano('upload')">
                        <i class="fas fa-file-arrow-up"></i>
                        <h4>Subir plano</h4>
                        <p>Imagen o PDF de tu plano arquitectónico</p>
                    </div>
                    <div class="plano-opt" id="opt-draw" onclick="selectPlano('draw')">
                        <i class="fas fa-pencil-ruler"></i>
                        <h4>Dibujar plano</h4>
                        <p>Crea uno con nuestro editor después de publicar</p>
                    </div>
                </div>

                <div class="plano-section" id="plano-upload-section">
                    <div class="upload-area" onclick="document.getElementById('plano_2d').click()">
                        <div class="upload-icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6;">
                            <i class="fas fa-drafting-compass"></i>
                        </div>
                        <h4>Sube tu plano arquitectónico</h4>
                        <p>JPG, PNG, SVG o PDF</p>
                        <input type="file" id="plano_2d" name="plano_2d" accept="image/*,.pdf">
                    </div>
                    <?php if ($editMode && !empty($inmueble['planos'])): ?>
                        <div style="margin-top:.75rem;font-size:.8rem;color:#94a3b8;">
                            Plano actual:
                            <?php foreach ($inmueble['planos'] as $p): ?>
                                <a href="<?php echo SITE_URL . $p['archivo']; ?>" target="_blank" style="color:#0ea5e9;"><?php echo htmlspecialchars($p['nombre']); ?></a>
                            <?php endforeach; ?>
                            <span style="color:#475569;"> — sube uno nuevo para reemplazarlo.</span>
                        </div>
                    <?php endif; ?>
                    <div class="upload-preview" id="preview-plano"></div>
                </div>

                <div class="plano-section" id="plano-draw-section">
                    <div class="plano-draw-placeholder">
                        <p>El editor de planos se abrirá en una nueva ventana después de publicar el inmueble.</p>
                        <label class="checkbox-row">
                            <input type="checkbox" name="crear_plano_despues" value="1">
                            <span>Crear plano después de publicar</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Footer ── -->
        <div class="pub-form-footer">
            <a href="<?php echo SITE_URL; ?>/views/inmuebles/listar.php" class="btn-cancel">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn-submit">
                <i class="fas fa-<?php echo $editMode ? 'floppy-disk' : 'paper-plane'; ?>"></i>
                <?php echo $editMode ? 'Guardar cambios' : 'Publicar inmueble'; ?>
            </button>
        </div>

    </form>
</main>

<script>
// ── Dropdown perfil ──
const profileBtn = document.getElementById('profileBtn');
const dropdown   = document.getElementById('profileDropdown');
profileBtn?.addEventListener('click', e => { e.stopPropagation(); dropdown.classList.toggle('open'); });
document.addEventListener('click', () => dropdown?.classList.remove('open'));

// ── Botones radio: TIPO ──
initBtnRadio('tipo-btns', 'tipo-select', '<?php echo $inmueble['tipo'] ?? 'casa'; ?>');
// ── Botones radio: ESTADO ──
initBtnRadio('estado-btns', 'estado-select', '<?php echo $inmueble['estado'] ?? 'venta'; ?>');

function initBtnRadio(groupId, selectId, initialVal) {
    const group  = document.getElementById(groupId);
    const select = document.getElementById(selectId);
    if (!group || !select) return;

    // Activar inicial
    group.querySelectorAll('.btn-radio').forEach(btn => {
        if (btn.dataset.val === initialVal) btn.classList.add('selected');
    });

    group.addEventListener('click', e => {
        const btn = e.target.closest('.btn-radio');
        if (!btn) return;
        group.querySelectorAll('.btn-radio').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        select.value = btn.dataset.val;
    });
}

// ── Plano opciones ──
function selectPlano(opt) {
    document.querySelectorAll('.plano-opt').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.plano-section').forEach(el => el.classList.remove('active'));
    document.getElementById('opt-' + opt).classList.add('active');
    document.getElementById('plano-' + opt + '-section').classList.add('active');
}

// ── Previews de imagen ──
function setupPreview(inputId, previewId) {
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;

    input.addEventListener('change', () => {
        preview.innerHTML = '';
        Array.from(input.files).forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `<img src="${e.target.result}" alt="">
                    <button type="button" class="remove-btn" onclick="this.closest('.preview-item').remove()">
                        <i class="fas fa-xmark"></i>
                    </button>`;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    });
}

// ── Drag & drop ──
function setupDrop(areaId, inputId) {
    const area  = document.getElementById(areaId);
    const input = document.getElementById(inputId);
    if (!area || !input) return;

    ['dragenter','dragover','dragleave','drop'].forEach(ev =>
        area.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); })
    );
    ['dragenter','dragover'].forEach(ev => area.addEventListener(ev, () => area.classList.add('dragover')));
    ['dragleave','drop'].forEach(ev => area.addEventListener(ev, () => area.classList.remove('dragover')));
    area.addEventListener('drop', e => {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
    });
}

// Inicializar
setupPreview('imagen_principal',    'preview-principal');
setupPreview('imagenes_adicionales','preview-adicionales');
setupPreview('plano_2d',            'preview-plano');
setupDrop('upload-principal',    'imagen_principal');
setupDrop('upload-adicionales',  'imagenes_adicionales');

// Precio: solo números
document.getElementById('precio')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '');
});
</script>
</body>
</html>