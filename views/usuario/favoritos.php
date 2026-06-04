<?php
/**
 * Vista de Favoritos - InmoVision3D
 * Muestra los inmuebles favoritos del usuario en formato horizontal
 */
session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Favorito.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . 'views/auth/login.php');
    exit;
}

$favoritoModel = new Favorito();
$favoritos = $favoritoModel->obtenerPorUsuario($_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos - InmoVision3D</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
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
                <a href="<?php echo BASE_URL; ?>views/usuario/favoritos.php" class="active">Favoritos</a>
                <?php if ($_SESSION['rol'] === 'publicador' || $_SESSION['rol'] === 'administrador'): ?>
                <a href="<?php echo BASE_URL; ?>views/inmuebles/publicar.php">Publicar</a>
                <?php endif; ?>
                <?php if ($_SESSION['rol'] === 'administrador'): ?>
                <a href="<?php echo BASE_URL; ?>views/admin/dashboard.php">Admin</a>
                <?php endif; ?>
            </nav>
            <div class="header-actions">
                <a href="<?php echo BASE_URL; ?>views/usuario/perfil.php" class="btn-user">
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
                <h1><i class="fas fa-heart"></i> Mis Favoritos</h1>
                <p>Inmuebles que has guardado como favoritos</p>
            </div>

            <?php if (empty($favoritos)): ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <h3>No tienes favoritos aun</h3>
                <p>Explora inmuebles y agrega los que te gusten a tus favoritos</p>
                <a href="<?php echo BASE_URL; ?>views/inmuebles/listar.php" class="btn-primary">
                    <i class="fas fa-search"></i> Explorar Inmuebles
                </a>
            </div>
            <?php else: ?>
            <!-- Favoritos en formato horizontal -->
            <div class="favoritos-horizontal">
                <?php foreach ($favoritos as $inmueble): ?>
                <div class="favorito-card-horizontal" data-id="<?php echo $inmueble['id']; ?>">
                    <div class="favorito-imagen">
                        <?php 
                        $imagen = !empty($inmueble['imagen_principal']) 
                            ? BASE_URL . 'assets/uploads/inmuebles/' . $inmueble['imagen_principal']
                            : BASE_URL . 'assets/img/no-image.jpg';
                        ?>
                        <img src="<?php echo $imagen; ?>" alt="<?php echo htmlspecialchars($inmueble['titulo']); ?>">
                        <div class="favorito-badge">
                            <span class="badge-tipo"><?php echo ucfirst($inmueble['tipo']); ?></span>
                            <span class="badge-estado estado-<?php echo $inmueble['estado']; ?>">
                                <?php echo $inmueble['estado'] === 'venta' ? 'En Venta' : 'En Arriendo'; ?>
                            </span>
                        </div>
                        <?php if ($inmueble['tiene_plano_3d']): ?>
                        <span class="badge-3d"><i class="fas fa-cube"></i> 3D</span>
                        <?php endif; ?>
                    </div>
                    <div class="favorito-info">
                        <div class="favorito-header">
                            <h3><?php echo htmlspecialchars($inmueble['titulo']); ?></h3>
                            <button class="btn-favorito active" 
                                    onclick="toggleFavorito(<?php echo $inmueble['id']; ?>, this)"
                                    title="Quitar de favoritos">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        <p class="favorito-ubicacion">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($inmueble['ciudad'] . ', ' . $inmueble['direccion']); ?>
                        </p>
                        <div class="favorito-caracteristicas">
                            <span><i class="fas fa-bed"></i> <?php echo $inmueble['habitaciones']; ?> Hab.</span>
                            <span><i class="fas fa-bath"></i> <?php echo $inmueble['banos']; ?> Banos</span>
                            <span><i class="fas fa-ruler-combined"></i> <?php echo number_format($inmueble['area']); ?> m2</span>
                            <?php if ($inmueble['parqueadero']): ?>
                            <span><i class="fas fa-car"></i> Parqueadero</span>
                            <?php endif; ?>
                        </div>
                        <p class="favorito-descripcion">
                            <?php echo htmlspecialchars(substr($inmueble['descripcion'], 0, 150)) . '...'; ?>
                        </p>
                        <div class="favorito-footer">
                            <span class="favorito-precio">
                                $<?php echo number_format($inmueble['precio'], 0, ',', '.'); ?>
                                <?php if ($inmueble['estado'] === 'arriendo'): ?>/mes<?php endif; ?>
                            </span>
                            <div class="favorito-actions">
                                <?php if ($inmueble['tiene_plano_3d']): ?>
                                <a href="<?php echo BASE_URL; ?>views/planos/visor3d.php?id=<?php echo $inmueble['id']; ?>" 
                                   class="btn-secondary">
                                    <i class="fas fa-cube"></i> Ver en 3D
                                </a>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>views/inmuebles/detalle.php?id=<?php echo $inmueble['id']; ?>" 
                                   class="btn-primary">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <h3><i class="fas fa-building"></i> InmoVision3D</h3>
                <p>Encuentra tu hogar ideal con tecnologia 3D</p>
            </div>
            <div class="footer-links">
                <h4>Enlaces</h4>
                <a href="<?php echo BASE_URL; ?>index.php">Inicio</a>
                <a href="<?php echo BASE_URL; ?>views/inmuebles/listar.php">Inmuebles</a>
                <a href="#">Contacto</a>
            </div>
            <div class="footer-contact">
                <h4>Contacto</h4>
                <p><i class="fas fa-envelope"></i> info@inmovision3d.com</p>
                <p><i class="fas fa-phone"></i> +57 300 123 4567</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 InmoVision3D. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
    function toggleFavorito(inmuebleId, btn) {
        fetch('<?php echo BASE_URL; ?>api/favoritos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggle',
                inmueble_id: inmuebleId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remover la tarjeta con animación
                const card = btn.closest('.favorito-card-horizontal');
                card.style.animation = 'slideOut 0.3s ease forwards';
                setTimeout(() => {
                    card.remove();
                    // Verificar si no quedan favoritos
                    const container = document.querySelector('.favoritos-horizontal');
                    if (container && container.children.length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        })
        .catch(error => console.error('Error:', error));
    }
    </script>
</body>
</html>
