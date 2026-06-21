<?php
/* Vista de Favoritos - InmoVision3D */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Favorito.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . SITE_URL . '/views/auth/login.php');
    exit;
}

$favoritoModel = new Favorito();
$favoritos     = $favoritoModel->obtenerPorUsuario($_SESSION['usuario_id']);
$total         = count($favoritos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../Assets/img/logo.png" type="image/png" />
    <title>Mis Favoritos — InmoVision3D</title>
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

        /* ── HEADER ── */
        .adm-header {
            position: sticky; top: 0; z-index: 100;
            background: rgba(15,23,42,0.97);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 3rem; height: 70px;
        }
        .adm-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
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

        /* ── WRAPPER ── */
        .mis-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 4rem;
        }

        /* ── BREADCRUMB ── */
        .breadcrumb {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.8rem; color: #475569; margin-bottom: 1.5rem;
        }
        .breadcrumb a { color: #475569; text-decoration: none; transition: color .15s; }
        .breadcrumb a:hover { color: #0ea5e9; }
        .breadcrumb .sep { color: #334155; }
        .breadcrumb .current { color: #94a3b8; }

        /* ── PAGE TITLE ── */
        .page-title {
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;
        }
        .page-title-left { display: flex; align-items: center; gap: 1rem; }
        .page-title-icon {
            width: 48px; height: 48px; border-radius: 12px;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .page-title h1 { font-size: 1.5rem; font-weight: 700; }
        .page-title p  { font-size: 0.82rem; color: #64748b; margin-top: 2px; }

        /* ── PANEL ── */
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

        /* ── SEARCH ── */
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

        /* ── FAVORITO CARD HORIZONTAL ── */
        .favorito-card-horizontal {
            display: flex;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background .15s;
        }
        .favorito-card-horizontal:last-child { border-bottom: none; }
        .favorito-card-horizontal:hover { background: rgba(255,255,255,0.02); }

        .favorito-imagen {
            width: 160px; min-width: 160px; height: 130px;
            flex-shrink: 0; position: relative; overflow: hidden;
            margin: 12px 0 12px 14px;
            border-radius: 10px;
        }
        .favorito-imagen img {
            width: 100%; height: 100%; object-fit: cover; display: block;
        }
        .favorito-badge {
            position: absolute; top: 7px; left: 7px;
            display: flex; flex-direction: column; gap: 4px;
        }
        .badge-tipo, .badge-estado {
            display: inline-block; padding: 2px 8px; border-radius: 20px;
            font-size: 0.65rem; font-weight: 600;
        }
        .badge-tipo { background: rgba(0,0,0,0.6); color: #f1f5f9; }
        .estado-venta    { background: rgba(14,165,233,0.85); color: #fff; }
        .estado-arriendo { background: rgba(245,158,11,0.85); color: #fff; }

        .favorito-info {
            flex: 1; padding: 14px 14px 14px 16px;
            display: flex; flex-direction: column; gap: 6px; min-width: 0;
        }
        .favorito-header {
            display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
        }
        .favorito-header h3 {
            font-size: 0.9rem; font-weight: 600; color: #e2e8f0;
            margin: 0; flex: 1;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .btn-favorito {
            background: none; border: none; cursor: pointer;
            font-size: 1rem; color: #ef4444; padding: 2px 4px;
            transition: transform .2s; flex-shrink: 0;
        }
        .btn-favorito:hover { transform: scale(1.2); }

        .favorito-ubicacion {
            font-size: 0.75rem; color: #64748b;
            display: flex; align-items: center; gap: 5px; margin: 0;
        }
        .favorito-caracteristicas {
            display: flex; gap: 12px; flex-wrap: wrap;
        }
        .favorito-caracteristicas span {
            font-size: 0.75rem; color: #94a3b8;
            display: flex; align-items: center; gap: 4px;
        }
        .favorito-caracteristicas i { color: #475569; font-size: 0.7rem; }
        .favorito-descripcion {
            font-size: 0.78rem; color: #64748b; margin: 0;
            line-height: 1.5;
            display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden;
        }
        .favorito-footer {
            display: flex; align-items: center;
            justify-content: space-between; gap: 10px;
            margin-top: auto; padding-top: 6px;
        }
        .favorito-precio {
            font-size: 0.95rem; font-weight: 700; color: #0ea5e9;
        }
        .favorito-precio small { font-size: 0.68rem; color: #64748b; font-weight: 400; }

        .btn-ver-detalle {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 7px;
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            color: #fff; font-family: 'Poppins', sans-serif;
            font-size: 0.75rem; font-weight: 600;
            text-decoration: none; transition: opacity .18s;
            white-space: nowrap;
        }
        .btn-ver-detalle:hover { opacity: .85; }

        /* ── PANEL FOOTER ── */
        .tabla-footer {
            padding: .75rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.05);
            font-size: .78rem; color: #64748b;
        }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 4rem 2rem; }
        .empty-state .empty-icon {
            width: 64px; height: 64px; border-radius: 16px;
            background: rgba(239,68,68,0.08);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; margin: 0 auto 1rem; color: #f87171;
        }
        .empty-state h3 { font-size: 1rem; font-weight: 600; margin-bottom: .4rem; }
        .empty-state p  { font-size: .82rem; color: #64748b; margin-bottom: 1.25rem; }
        .btn-explorar {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 9px;
            background: linear-gradient(135deg, #0ea5e9, #f97316);
            color: #fff; font-family: 'Poppins', sans-serif;
            font-size: 0.875rem; font-weight: 600;
            text-decoration: none; transition: opacity .18s;
        }
        .btn-explorar:hover { opacity: .88; }

        @keyframes slideOut {
            to { opacity: 0; transform: translateX(20px); }
        }

        @media (max-width: 640px) {
            .favorito-imagen { width: 110px; min-width: 110px; height: 100px; }
            .favorito-descripcion { display: none; }
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
        <?php if (in_array($_SESSION['rol'], ['publicador','admin'])): ?>
        <a href="<?php echo SITE_URL; ?>/views/inmuebles/publicar.php">Publicar</a>
        <?php endif; ?>
    </nav>

    <div class="adm-profile" id="profileBtn">
        <div class="avatar"><?php echo strtoupper(substr($_SESSION['nombre'], 0, 2)); ?></div>
        <span class="pname"><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
        <div class="adm-dropdown" id="profileDropdown">
            <a href="<?php echo SITE_URL; ?>/views/usuario/perfil.php">Mi perfil</a>
            <?php if (in_array($_SESSION['rol'], ['publicador','admin'])): ?>
            <a href="<?php echo SITE_URL; ?>/views/usuario/mis-inmuebles.php">Mis Inmuebles</a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/views/usuario/favoritos.php" class="active">Favoritos</a>
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
<main class="mis-wrapper">

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?php echo SITE_URL; ?>/index.php">Inicio</a>
        <span class="sep">›</span>
        <a href="<?php echo SITE_URL; ?>/views/usuario/perfil.php">Mi perfil</a>
        <span class="sep">›</span>
        <span class="current">Mis favoritos</span>
    </div>

    <!-- Título -->
    <div class="page-title">
        <div class="page-title-left">
            <div class="page-title-icon">
                <i class="fas fa-heart" style="background:linear-gradient(135deg,#ef4444,#f97316);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"></i>
            </div>
            <div>
                <h1>Mis favoritos</h1>
                <p><?php echo $total; ?> inmueble<?php echo $total !== 1 ? 's' : ''; ?> guardado<?php echo $total !== 1 ? 's' : ''; ?></p>
            </div>
        </div>
    </div>

    <!-- Panel -->
    <div class="adm-panel">

        <?php if (!empty($favoritos)): ?>
        <!-- Buscador -->
        <div class="adm-panel-header">
            <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="searchFavoritos" placeholder="Buscar favoritos…">
            </div>
        </div>
        <?php endif; ?>

        <div id="listaFavoritos">
        <?php if (empty($favoritos)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-heart-crack"></i></div>
                <h3>No tienes favoritos aún</h3>
                <p>Explora inmuebles y agrega los que te gusten a tus favoritos.</p>
                <a href="<?php echo SITE_URL; ?>/views/inmuebles/listar.php" class="btn-explorar">
                    <i class="fas fa-search"></i> Explorar inmuebles
                </a>
            </div>

        <?php else: ?>
            <?php foreach ($favoritos as $inmueble): ?>
            <div class="favorito-card-horizontal" data-titulo="<?php echo htmlspecialchars(strtolower($inmueble['titulo'])); ?>">

                <div class="favorito-imagen">
                    <?php 
                    $imagenPrincipal = !empty($inmueble['imagenes']) ? SITE_URL . '/assets/uploads/inmuebles/' . $inmueble['imagenes'][0]['urlImagen']: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800';
                    ?>
                    <img src="<?php echo htmlspecialchars($imagenPrincipal); ?>" 
                         alt="<?php echo htmlspecialchars($inmueble['titulo']); ?>">
                    <div class="favorito-badge">
                        <span class="badge-tipo"><?php echo ucfirst($inmueble['tipo']); ?></span>
                        <span class="badge-estado estado-<?php echo $inmueble['operacion']; ?>">
                            <?php echo $inmueble['operacion'] === 'venta' ? 'En Venta' : 'En Arriendo'; ?>
                        </span>
                    </div>
                </div>

                <div class="favorito-info">
                    <div class="favorito-header">
                        <h3><?php echo htmlspecialchars($inmueble['titulo']); ?></h3>
                        <button class="btn-favorito"
                                onclick="toggleFavorito(<?php echo $inmueble['idInmueble']; ?>, this)"
                                title="Quitar de favoritos">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>

                    <p class="favorito-ubicacion">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($inmueble['ubicacion']); ?>
                    </p>

                    <div class="favorito-caracteristicas">
                        <span><i class="fas fa-bed"></i> <?php echo $inmueble['habitaciones']; ?> hab.</span>
                        <span><i class="fas fa-bath"></i> <?php echo $inmueble['banos']; ?> baños</span>
                        <span><i class="fas fa-ruler-combined"></i> <?php echo number_format($inmueble['area']); ?> m²</span>
                    </div>

                    <p class="favorito-descripcion">
                        <?php echo htmlspecialchars(substr($inmueble['descripcion'], 0, 150)) . '...'; ?>
                    </p>

                    <div class="favorito-footer">
                        <span class="favorito-precio">
                            $<?php echo number_format($inmueble['precio'], 0, ',', '.'); ?>
                            <?php if ($inmueble['operacion'] === 'arriendo'): ?>
                            <small>/mes</small>
                            <?php endif; ?>
                        </span>
                        <a href="<?php echo SITE_URL; ?>/views/inmuebles/detalle.php?id=<?php echo $inmueble['idInmueble']; ?>"
                           class="btn-ver-detalle">
                            <i class="fas fa-eye"></i> Ver detalle
                        </a>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>

        <?php if (!empty($favoritos)): ?>
        <div class="tabla-footer">
            <span id="contadorFavoritos"><?php echo $total; ?> favorito<?php echo $total !== 1 ? 's' : ''; ?></span>
        </div>
        <?php endif; ?>

    </div>

</main>

<!-- Footer -->
<footer class="footer">
    <div class="footer-bottom">
        <p>&copy; 2026 InmoVision3D. Todos los derechos reservados.</p>
    </div>
</footer>

<script>
/* Dropdown */
const profileBtn = document.getElementById('profileBtn');
const dropdown   = document.getElementById('profileDropdown');
profileBtn?.addEventListener('click', e => { e.stopPropagation(); dropdown.classList.toggle('open'); });
document.addEventListener('click', () => dropdown?.classList.remove('open'));

/* Búsqueda */
document.getElementById('searchFavoritos')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('.favorito-card-horizontal').forEach(card => {
        const match = !q || card.dataset.titulo.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) vis++;
    });
    const c = document.getElementById('contadorFavoritos');
    if (c) c.textContent = vis + ' favorito' + (vis !== 1 ? 's' : '');
});

/* Toggle favorito */
function toggleFavorito(inmuebleId, btn) {
    fetch('<?php echo SITE_URL; ?>/Api/FavoritosApi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle', inmueble_id: inmuebleId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = btn.closest('.favorito-card-horizontal');
            card.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => {
                card.remove();
                const lista = document.getElementById('listaFavoritos');
                if (lista && !lista.querySelector('.favorito-card-horizontal')) location.reload();
            }, 300);
        }
    })
    .catch(err => console.error('Error:', err));
}
</script>
</body>
</html>