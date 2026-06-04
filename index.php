<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Inmueble.php';
require_once __DIR__ . '/models/Favorito.php';

$inmuebleModel = new Inmueble();
$favoritoModel = new Favorito();

// Obtener inmuebles destacados
$inmuebles = $inmuebleModel->listar([
    'estado' => ESTADO_DISPONIBLE,
    'limite' => 6
]);

// Obtener IDs de favoritos si el usuario está logueado
$favoritosIds = [];
if (isLoggedIn()) {
    $favoritosIds = $favoritoModel->obtenerIds($_SESSION['usuario_id']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InmoVision 3D - Tu Hogar Ideal</title>
    <meta name="description" content="Explora propiedades con visualización 3D inmersiva. El futuro de la inmobiliaria está aquí.">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 2L38 14V38H2V14L20 2Z" stroke="currentColor" stroke-width="2" fill="none"/>
                        <path d="M14 38V24H26V38" stroke="currentColor" stroke-width="2"/>
                        <circle cx="20" cy="16" r="4" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <span class="logo-text">InmoVision <span class="highlight">3D</span></span>
            </a>
            <nav class="nav" id="mainNav">
                <a href="views/inmuebles/listar.php" class="nav-link">Ver Inmuebles</a>
                <?php if (isPublicador()): ?>
                    <a href="views/inmuebles/publicar.php" class="nav-link">Publicar</a>
                <?php endif; ?>
                <?php if (isLoggedIn()): ?>
                    <div class="profile-nav" id="profileNav">
                        <div class="profile-box">
                            <img src="<?php echo $_SESSION['avatar'] ? 'assets/uploads/avatars/' . $_SESSION['avatar'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>" alt="Avatar">
                            <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                        </div>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="views/usuario/perfil.php">Mi Perfil</a>
                            <?php if (isPublicador()): ?>
                                <a href="views/usuario/mis-inmuebles.php">Mis Inmuebles</a>
                            <?php endif; ?>
                            <a href="views/usuario/favoritos.php">Favoritos</a>
                            <a href="views/usuario/solicitudes.php">Solicitudes</a>
                            <?php if (isAdmin()): ?>
                                <div class="dropdown-divider"></div>
                                <a href="views/admin/dashboard.php">Panel Admin</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="controllers/AuthController.php?action=logout">Cerrar Sesión</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="views/auth/login.php" class="nav-link btn-login">Iniciar Sesión</a>
                <?php endif; ?>
            </nav>
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div id="canvas-container"></div>
        <div class="hero-content">
            <h1>Encuentra tu <span class="highlight">hogar ideal</span></h1>
            <p>Explora propiedades con visualización 3D inmersiva. El futuro de la inmobiliaria está aquí.</p>
        </div>
        
        <!-- Filtros -->
        <div class="filtros-container">
            <form class="filtros-top" action="views/inmuebles/listar.php" method="GET">
                <select name="tipo">
                    <option value="">Tipo</option>
                    <option value="casa">Casa</option>
                    <option value="apartamento">Apartamento</option>
                    <option value="local">Local</option>
                    <option value="oficina">Oficina</option>
                    <option value="terreno">Terreno</option>
                </select>

                <input type="text" name="ubicacion" placeholder="Ubicación">

                <select name="operacion">
                    <option value="">Operación</option>
                    <option value="venta">Compra</option>
                    <option value="arriendo">Arriendo</option>
                </select>

                <input type="number" name="precio_max" placeholder="Precio máximo">

                <input type="number" name="habitaciones" placeholder="Habitaciones">

                <input type="number" name="area_min" placeholder="m² mínimo">

                <button type="submit" class="btn-filtrar">Filtrar</button>
            </form>
        </div>
    </section>

    <!-- Inmuebles Section -->
    <section class="inmuebles" id="inmuebles">
        <div class="section-container">
            <h2 class="section-title">Inmuebles <span class="highlight">Destacados</span></h2>
            <div class="inmuebles-grid">
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
                                    data-inmueble-id="<?php echo $inmueble['idInmueble']; ?>"
                                    title="<?php echo $esFavorito ? 'Quitar de favoritos' : 'Agregar a favoritos'; ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="card-content">
                            <h3><a href="views/inmuebles/detalle.php?id=<?php echo $inmueble['idInmueble']; ?>"><?php echo htmlspecialchars($inmueble['titulo']); ?></a></h3>
                            <p class="card-location">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <?php echo htmlspecialchars($inmueble['ubicacion']); ?>
                            </p>
                            <div class="card-features">
                                <span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 7v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7"></path>
                                        <path d="M21 7H3V4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3z"></path>
                                    </svg>
                                    <?php echo $inmueble['habitaciones']; ?> Hab.
                                </span>
                                <span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 6H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-4"></path>
                                        <rect x="9" y="3" width="6" height="6" rx="1"></rect>
                                    </svg>
                                    <?php echo $inmueble['banos']; ?> Baños
                                </span>
                                <span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                        <path d="M3 9h18"></path>
                                        <path d="M9 21V9"></path>
                                    </svg>
                                    <?php echo number_format($inmueble['area']); ?> m²
                                </span>
                            </div>
                            <p class="card-price">
                                <?php echo formatPrice($inmueble['precio']); ?>
                                <?php echo $inmueble['operacion'] === 'arriendo' ? '/mes' : ''; ?>
                            </p>
                            <div class="card-buttons">
                                <a href="views/planos/visor.php?inmueble=<?php echo $inmueble['idInmueble']; ?>" class="btn-plano">Ver Plano 2D/3D</a>
                                <a href="views/inmuebles/detalle.php?id=<?php echo $inmueble['idInmueble']; ?>" class="btn-contacto">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 40px;">
                <a href="views/inmuebles/listar.php" class="btn btn-primary btn-lg">Ver Todos los Inmuebles</a>
            </div>
        </div>
    </section>

    <!-- Quienes Somos -->
    <section class="quienes-somos" id="nosotros">
        <div class="section-container">
            <div class="about-content">
                <div class="about-text">
                    <h2 class="section-title">Quiénes <span class="highlight">Somos</span></h2>
                    <p>En <strong>InmoVision 3D</strong> revolucionamos la forma de buscar propiedades. Somos pioneros en integrar tecnología de visualización 3D para que puedas explorar cada rincón de tu futuro hogar desde cualquier lugar.</p>
                    <p>Con más de 10 años de experiencia en el sector inmobiliario, combinamos nuestra expertise con las últimas innovaciones tecnológicas para ofrecerte una experiencia única e inmersiva.</p>
                    <div class="about-stats">
                        <div class="stat">
                            <span class="stat-number">500+</span>
                            <span class="stat-label">Propiedades</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">1,200+</span>
                            <span class="stat-label">Clientes Felices</span>
                        </div>
                        <div class="stat">
                            <span class="stat-number">10+</span>
                            <span class="stat-label">Años de Experiencia</span>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <div class="image-wrapper">
                        <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=500&h=400&fit=crop" alt="Equipo InmoVision">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Servicios -->
    <section class="servicios" id="servicios">
        <div class="section-container">
            <h2 class="section-title">Nuestros <span class="highlight">Servicios</span></h2>
            <div class="servicios-grid">
                <div class="servicio-card">
                    <div class="servicio-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <h3>Búsqueda Personalizada</h3>
                    <p>Te ayudamos a encontrar la propiedad perfecta según tus necesidades, presupuesto y ubicación preferida.</p>
                </div>

                <div class="servicio-card">
                    <div class="servicio-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <h3>Asesoría Legal</h3>
                    <p>Contamos con un equipo legal especializado que te guiará en todo el proceso de compra, venta o arriendo.</p>
                </div>

                <div class="servicio-card">
                    <div class="servicio-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                            <path d="M6 8l4 4 4-4 4 4"/>
                        </svg>
                    </div>
                    <h3>Tours Virtuales 3D</h3>
                    <p>Explora cada propiedad con nuestros tours virtuales inmersivos en 3D, sin salir de tu casa.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <a href="index.php" class="logo">
                    <div class="logo-icon">
                        <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 2L38 14V38H2V14L20 2Z" stroke="currentColor" stroke-width="2" fill="none"/>
                            <path d="M14 38V24H26V38" stroke="currentColor" stroke-width="2"/>
                            <circle cx="20" cy="16" r="4" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <span class="logo-text">InmoVision <span class="highlight">3D</span></span>
                </a>
                <p>El futuro de la inmobiliaria está aquí.</p>
            </div>
            <div class="footer-links">
                <h4>Enlaces</h4>
                <a href="views/inmuebles/listar.php">Ver Inmuebles</a>
                <a href="#nosotros">Quiénes Somos</a>
                <a href="#servicios">Servicios</a>
            </div>
            <div class="footer-contact">
                <h4>Contacto</h4>
                <p>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    info@inmovision3d.com
                </p>
                <p>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    +57 300 123 4567
                </p>
                <p>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    Bogotá, Colombia
                </p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> InmoVision 3D. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/three-background.js"></script>
</body>
</html>
