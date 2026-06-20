<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Inmueble.php';
if (file_exists(__DIR__ . '/../../models/Favorito.php')) require_once __DIR__ . '/../../models/Favorito.php';

$inmuebleModel = new Inmueble();
$favoritoModel = class_exists('Favorito') ? new Favorito() : null;

// Obtener filtros
$filtros = [
    'tipo' => sanitize($_GET['tipo'] ?? ''),
    'operacion' => sanitize($_GET['operacion'] ?? ''),
    'ubicacion' => sanitize($_GET['ubicacion'] ?? ''),
    'precio_max' => (float)($_GET['precio_max'] ?? 0),
    'habitaciones' => (int)($_GET['habitaciones'] ?? 0),
    'area_min' => (float)($_GET['area_min'] ?? 0),
    'busqueda' => sanitize($_GET['q'] ?? ''),
    'estado' => ESTADO_DISPONIBLE,
    'limite' => 12,
    'offset' => ((int)($_GET['pagina'] ?? 1) - 1) * 12
];

$filtros = array_filter($filtros);
$filtros['estado'] = ESTADO_DISPONIBLE;

$inmuebles = $inmuebleModel->listar($filtros);
$total = $inmuebleModel->contar(['estado' => ESTADO_DISPONIBLE]);
$totalPaginas = ceil($total / 12);
$paginaActual = (int)($_GET['pagina'] ?? 1);

// Favoritos
$favoritosIds = [];
if (isLoggedIn() && $favoritoModel) {
    $favoritosIds = $favoritoModel->obtenerIds($_SESSION['usuario_id']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../Assets/img/logo.png" type="image/png" />
    <title>Explorar Inmuebles - InmoVision 3D</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
       <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="../../index.php" class="logo">
    <img 
        src="../../assets/img/logo.png" 
        alt="InmoVision 3D logo" 
        class="logo-icon"
    >
    <span class="logo-text">InmoVision <span class="highlight">3D</span></span>
</a>
             <nav class="nav" id="mainNav">
                <a href="<?php echo SITE_URL; ?>/index.php" class="nav-link">Inicio</a>
                <a href="listar.php" class="nav-link" class="active"> Inmuebles</a>
                <?php if (isPublicador()): ?>
                    <a href="publicar.php" class="nav-link">Publicar</a>
                <?php endif; ?>
                <?php if (isLoggedIn()): ?>
                    <div class="profile-nav" id="profileNav">
                        <div class="profile-box">
                            <div class="avatar"><?php echo strtoupper(substr($_SESSION['nombre'], 0, 2)); ?></div>
                            <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                        </div>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="../usuario/perfil.php">Mi Perfil</a>
                            <?php if (isPublicador()): ?>
                                <a href="../usuario/mis-inmuebles.php">Mis Inmuebles</a>
                            <?php endif; ?>
                            <a href="../usuario/favoritos.php">Favoritos</a>
                            <a href="../usuario/solicitudes.php">Solicitudes</a>
                            <?php if (isAdmin()): ?>
                                <div class="dropdown-divider"></div>
                                <a href="../admin/dashboard.php">Panel Admin</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="../../controllers/AuthController.php?action=logout">Cerrar Sesión</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="nav-link btn-login">Iniciar Sesión</a>
                <?php endif; ?>
            </nav>
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <main style="padding-top: 100px;">
        <section class="section-container">
            <h1 class="section-title">Explorar <span class="highlight">Inmuebles</span></h1>
            
            <!-- Filtros -->
            <form class="filtros-top" method="GET" style="margin-bottom: 40px;">
                <select name="tipo">
                    <option value="">Todos los tipos</option>
                    <option value="casa" <?php echo ($_GET['tipo'] ?? '') === 'casa' ? 'selected' : ''; ?>>Casa</option>
                    <option value="apartamento" <?php echo ($_GET['tipo'] ?? '') === 'apartamento' ? 'selected' : ''; ?>>Apartamento</option>
                    <option value="local" <?php echo ($_GET['tipo'] ?? '') === 'local' ? 'selected' : ''; ?>>Local</option>
                    <option value="oficina" <?php echo ($_GET['tipo'] ?? '') === 'oficina' ? 'selected' : ''; ?>>Oficina</option>
                    <option value="terreno" <?php echo ($_GET['tipo'] ?? '') === 'terreno' ? 'selected' : ''; ?>>Terreno</option>
                </select>

                <input type="text" name="ubicacion" placeholder="Ubicación" value="<?php echo htmlspecialchars($_GET['ubicacion'] ?? ''); ?>">

                <select name="operacion">
                    <option value="">Todas las operaciones</option>
                    <option value="venta" <?php echo ($_GET['operacion'] ?? '') === 'venta' ? 'selected' : ''; ?>>Venta</option>
                    <option value="arriendo" <?php echo ($_GET['operacion'] ?? '') === 'arriendo' ? 'selected' : ''; ?>>Arriendo</option>
                </select>

                <input type="number" name="precio_max" placeholder="Precio máximo" value="<?php echo $_GET['precio_max'] ?? ''; ?>">

                <input type="number" name="habitaciones" placeholder="Habitaciones mín." value="<?php echo $_GET['habitaciones'] ?? ''; ?>">

                <button type="submit" class="btn-filtrar">Filtrar</button>
            </form>

            <p style="color: var(--color-gray-light); margin-bottom: 30px;">
                Mostrando <?php echo count($inmuebles); ?> de <?php echo $total; ?> inmuebles
            </p>

            <!-- Grid de inmuebles -->
            <div class="inmuebles-grid">
                <?php if (empty($inmuebles)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                        <h3>No se encontraron inmuebles</h3>
                        <p style="color: var(--color-gray-light);">Intenta con otros filtros de búsqueda</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($inmuebles as $inmueble): ?>
                        <?php 
                        $esFavorito = in_array($inmueble['idInmueble'], $favoritosIds);
                        $imagenPrincipal = $inmueble['imagen_principal'] ?? 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=400&h=300&fit=crop';
                        ?>
                        <div class="inmueble-card">
                            <div class="card-image">
                                <img src="<?php echo htmlspecialchars($imagenPrincipal); ?>" alt="<?php echo htmlspecialchars($inmueble['titulo']); ?>">
                                <span class="card-badge <?php echo $inmueble['operacion'] === 'arriendo' ? 'arriendo' : ''; ?>">
                                    <?php echo ucfirst($inmueble['operacion']); ?>
                                </span>
                                <button class="btn-favorite <?php echo $esFavorito ? 'active' : ''; ?>" 
                                        data-inmueble-id="<?php echo $inmueble['idInmueble']; ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="card-content">
                                <h3><a href="detalle.php?id=<?php echo $inmueble['idInmueble']; ?>"><?php echo htmlspecialchars($inmueble['titulo']); ?></a></h3>
                                <p class="card-location">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <?php echo htmlspecialchars($inmueble['ubicacion']); ?>
                                </p>
                                <div class="card-features">
                                    <span><?php echo $inmueble['habitaciones']; ?> Hab.</span>
                                    <span><?php echo $inmueble['banos']; ?> Baños</span>
                                    <span><?php echo number_format($inmueble['area']); ?> m²</span>
                                </div>
                                <p class="card-price">
                                    <?php echo formatPrice($inmueble['precio']); ?>
                                    <?php echo $inmueble['operacion'] === 'arriendo' ? '/mes' : ''; ?>
                                </p>
                                <div class="card-buttons">
                                    <a href="../planos/visor.php?inmueble=<?php echo $inmueble['idInmueble']; ?>" class="btn-plano">Ver Plano 2D/3D</a>
                                    <a href="detalle.php?id=<?php echo $inmueble['idInmueble']; ?>" class="btn-contacto">Ver Detalles</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Paginación -->
            <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php if ($paginaActual > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $paginaActual - 1])); ?>">Anterior</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <?php if ($i === $paginaActual): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($paginaActual < $totalPaginas): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $paginaActual + 1])); ?>">Siguiente</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer" style="margin-top: 60px;">
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> InmoVision 3D. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="../../assets/js/main.js"></script>
</body>
</html>