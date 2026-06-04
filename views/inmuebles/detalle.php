<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Inmueble.php';
require_once __DIR__ . '/../../models/Favorito.php';

$inmuebleModel = new Inmueble();
$favoritoModel = new Favorito();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: listar.php');
    exit();
}

$inmueble = $inmuebleModel->obtenerPorId($id);

if (!$inmueble) {
    header('Location: listar.php');
    exit();
}

// Verificar favorito
$esFavorito = false;
if (isLoggedIn()) {
    $esFavorito = $favoritoModel->existe($_SESSION['usuario_id'], $id);
}

$totalFavoritos = $favoritoModel->contarPorInmueble($id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($inmueble['titulo']); ?> - InmoVision 3D</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .detalle-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
            padding-top: 100px;
        }
        
        .galeria {
            background: var(--color-dark-light);
            border-radius: var(--radius);
            overflow: hidden;
        }
        
        .galeria-principal {
            width: 100%;
            height: 450px;
            object-fit: cover;
        }
        
        .galeria-thumbs {
            display: flex;
            gap: 10px;
            padding: 15px;
            overflow-x: auto;
        }
        
        .galeria-thumb {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            cursor: pointer;
            opacity: 0.6;
            transition: var(--transition);
        }
        
        .galeria-thumb:hover,
        .galeria-thumb.active {
            opacity: 1;
        }
        
        .info-box {
            background: var(--color-dark-light);
            border-radius: var(--radius);
            padding: 30px;
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .info-precio {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 20px;
        }
        
        .info-features {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .info-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--color-gray-light);
        }
        
        .info-feature strong {
            color: var(--color-white);
        }
        
        .publicador-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .publicador-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .publicador-name {
            font-weight: 600;
        }
        
        .publicador-label {
            color: var(--color-gray-light);
            font-size: 0.85rem;
        }
        
        .detalle-content {
            margin-top: 30px;
        }
        
        .detalle-section {
            background: var(--color-dark-light);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .detalle-section h3 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .descripcion-text {
            color: var(--color-gray-light);
            line-height: 1.8;
        }
        
        .caracteristicas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .caracteristica-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: var(--color-dark);
            border-radius: var(--radius-sm);
        }
        
        .btn-favorito-lg {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 15px;
            margin-bottom: 15px;
            background: transparent;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: var(--radius-sm);
            color: var(--color-white);
            font-family: inherit;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-favorito-lg:hover {
            border-color: #ef4444;
            color: #ef4444;
        }
        
        .btn-favorito-lg.active {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
            color: #ef4444;
        }
        
        .btn-favorito-lg svg {
            width: 24px;
            height: 24px;
        }
        
        .favoritos-count {
            color: var(--color-gray-light);
            font-size: 0.85rem;
            text-align: center;
            margin-bottom: 20px;
        }
        
        @media (max-width: 992px) {
            .detalle-container {
                grid-template-columns: 1fr;
            }
            
            .info-box {
                position: static;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="../../index.php" class="logo">
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
                <a href="../../index.php" class="nav-link">Inicio</a>
                <a href="listar.php" class="nav-link">Ver Inmuebles</a>
                <?php if (isLoggedIn()): ?>
                    <div class="profile-nav" id="profileNav">
                        <div class="profile-box">
                            <img src="<?php echo $_SESSION['avatar'] ? '../../assets/uploads/avatars/' . $_SESSION['avatar'] : 'https://cdn-icons-png.flaticon.com/512/149/149071.png'; ?>" alt="Avatar">
                            <span><?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
                        </div>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="../usuario/perfil.php">Mi Perfil</a>
                            <a href="../usuario/favoritos.php">Favoritos</a>
                            <a href="../usuario/solicitudes.php">Solicitudes</a>
                            <div class="dropdown-divider"></div>
                            <a href="../../controllers/AuthController.php?action=logout">Cerrar Sesión</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../auth/login.php" class="nav-link btn-login">Iniciar Sesión</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="section-container">
        <div class="detalle-container">
            <div class="detalle-main">
                <!-- Galería -->
                <div class="galeria">
                    <?php 
                    $imagenPrincipal = !empty($inmueble['imagenes']) 
                        ? $inmueble['imagenes'][0]['urlImagen'] 
                        : 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&h=600&fit=crop';
                    ?>
                    <img src="<?php echo htmlspecialchars($imagenPrincipal); ?>" 
                         alt="<?php echo htmlspecialchars($inmueble['titulo']); ?>" 
                         class="galeria-principal" id="imagenPrincipal">
                    
                    <?php if (!empty($inmueble['imagenes']) && count($inmueble['imagenes']) > 1): ?>
                        <div class="galeria-thumbs">
                            <?php foreach ($inmueble['imagenes'] as $index => $imagen): ?>
                                <img src="<?php echo htmlspecialchars($imagen['urlImagen']); ?>" 
                                     alt="Imagen <?php echo $index + 1; ?>"
                                     class="galeria-thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                                     onclick="cambiarImagen(this, '<?php echo htmlspecialchars($imagen['urlImagen']); ?>')">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <h1 style="margin-top: 30px;"><?php echo htmlspecialchars($inmueble['titulo']); ?></h1>
                <p class="card-location" style="font-size: 1.1rem; margin-top: 10px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php echo htmlspecialchars($inmueble['ubicacion']); ?>
                </p>

                <!-- Descripción -->
                <div class="detalle-section">
                    <h3>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        Descripción
                    </h3>
                    <p class="descripcion-text"><?php echo nl2br(htmlspecialchars($inmueble['descripcion'])); ?></p>
                </div>

                <!-- Características -->
                <div class="detalle-section">
                    <h3>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="9" x2="9" y2="9"/>
                            <line x1="15" y1="9" x2="15" y2="9"/>
                            <line x1="9" y1="15" x2="9" y2="15"/>
                            <line x1="15" y1="15" x2="15" y2="15"/>
                        </svg>
                        Características
                    </h3>
                    <div class="caracteristicas-grid">
                        <div class="caracteristica-item">
                            <span>Tipo:</span>
                            <strong><?php echo ucfirst($inmueble['tipo']); ?></strong>
                        </div>
                        <div class="caracteristica-item">
                            <span>Operación:</span>
                            <strong><?php echo ucfirst($inmueble['operacion']); ?></strong>
                        </div>
                        <div class="caracteristica-item">
                            <span>Habitaciones:</span>
                            <strong><?php echo $inmueble['habitaciones']; ?></strong>
                        </div>
                        <div class="caracteristica-item">
                            <span>Baños:</span>
                            <strong><?php echo $inmueble['banos']; ?></strong>
                        </div>
                        <div class="caracteristica-item">
                            <span>Área:</span>
                            <strong><?php echo number_format($inmueble['area']); ?> m²</strong>
                    </div>
                </div>

                <!-- Ver en 3D -->
                <div class="detalle-section">
                    <h3>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        Visualización 2D/3D
                    </h3>
                    <p style="color: var(--color-gray-light); margin-bottom: 20px;">
                        Explora este inmueble en nuestro visor interactivo de planos 2D y visualización 3D.
                    </p>
                    <a href="../planos/visor.php?inmueble=<?php echo $inmueble['id']; ?>" class="btn btn-primary btn-lg">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Ver Plano 2D/3D
                    </a>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="info-box">
                <span class="badge badge-<?php echo $inmueble['operacion'] === 'venta' ? 'primary' : 'warning'; ?>" style="margin-bottom: 15px;">
                    <?php echo ucfirst($inmueble['operacion']); ?>
                </span>
                
                <p class="info-precio">
                    <?php echo formatPrice($inmueble['precio']); ?>
                    <?php echo $inmueble['operacion'] === 'arriendo' ? '<small>/mes</small>' : ''; ?>
                </p>

                <div class="info-features">
                    <div class="info-feature">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7"/>
                            <path d="M21 7H3V4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3z"/>
                        </svg>
                        <strong><?php echo $inmueble['habitaciones']; ?></strong> Hab.
                    </div>
                    <div class="info-feature">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 6H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-4"/>
                            <rect x="9" y="3" width="6" height="6" rx="1"/>
                        </svg>
                        <strong><?php echo $inmueble['banos']; ?></strong> Baños
                    </div>
                </div>

                <button class="btn-favorito-lg <?php echo $esFavorito ? 'active' : ''; ?>" 
                        data-inmueble-id="<?php echo $inmueble['idInmueble']; ?>" id="btnFavorito">
                    <svg viewBox="0 0 24 24" fill="<?php echo $esFavorito ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span><?php echo $esFavorito ? 'En favoritos' : 'Agregar a favoritos'; ?></span>
                </button>
                
                <p class="favoritos-count"><?php echo $totalFavoritos; ?> personas tienen este inmueble en favoritos</p>

                <!-- Publicador -->
                <div class="publicador-info">
                    <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Publicador">
                    <div>
                        <p class="publicador-name"><?php echo htmlspecialchars($inmueble['publicador_nombre'] . ' ' . $inmueble['publicador_apellido']); ?></p>
                        <p class="publicador-label">Publicador</p>
                    </div>
                </div>

                <?php if (isLoggedIn() && $_SESSION['usuario_id'] != $inmueble['idPublicador']): ?>
                    <a href="../usuario/solicitudes.php?nueva=<?php echo $inmueble['idInmueble']; ?>" class="btn btn-primary" style="width: 100%;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        Contactar
                    </a>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="../auth/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary" style="width: 100%;">
                        Inicia sesión para contactar
                    </a>
                <?php endif; ?>
                
                <p style="color: var(--color-gray-light); font-size: 0.85rem; margin-top: 20px; text-align: center;">
                </p>
            </div>
        </div>
    </main>

    <footer class="footer" style="margin-top: 60px;">
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> InmoVision 3D. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Favorito
        document.getElementById('btnFavorito').addEventListener('click', async function() {
            const inmuebleId = this.dataset.inmuebleId;
            
            try {
                const response = await fetch('../../api/favoritos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle&inmueble_id=${inmuebleId}`
                });

                const data = await response.json();

                if (data.require_login) {
                    window.location.href = '../auth/login.php?redirect=' + encodeURIComponent(window.location.href);
                    return;
                }

                if (data.success) {
                    this.classList.toggle('active', data.is_favorite);
                    const svg = this.querySelector('svg');
                    svg.setAttribute('fill', data.is_favorite ? 'currentColor' : 'none');
                    this.querySelector('span').textContent = data.is_favorite ? 'En favoritos' : 'Agregar a favoritos';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        });
    </script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
