<?php
/**
 * Página de Login
 * InmoVision 3D
 */

require_once __DIR__ . '/../../config/config.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    header('Location: ../../index.php');
    exit();
}

$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - InmoVision 3D</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .auth-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 100px 20px 40px;
        }
        
        .auth-box {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(15px);
            padding: 40px;
            border-radius: var(--radius);
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .auth-box h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .auth-divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
        }
        
        .auth-divider::before,
        .auth-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .auth-divider span {
            margin: 0 15px;
            color: var(--color-gray-light);
            font-size: 0.9rem;
        }
        
        .btn-google {
            width: 100%;
            padding: 14px;
            background: var(--color-white);
            border: none;
            border-radius: var(--radius-sm);
            color: #333;
            font-family: inherit;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
        }
        
        .btn-google:hover {
            background: #f1f1f1;
            transform: translateY(-2px);
        }
        
        .btn-google img {
            width: 20px;
            height: 20px;
        }
        
        .auth-extra {
            text-align: center;
            margin-top: 25px;
            color: var(--color-gray-light);
        }
        
        .auth-extra a {
            color: var(--color-primary);
            text-decoration: none;
        }
        
        .auth-extra a:hover {
            text-decoration: underline;
        }
        
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-option {
            flex: 1;
            padding: 12px;
            background: var(--color-dark);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-sm);
            cursor: pointer;
            text-align: center;
            transition: var(--transition);
        }
        
        .role-option:hover {
            border-color: var(--color-primary);
        }
        
        .role-option.active {
            border-color: var(--color-primary);
            background: rgba(14, 165, 233, 0.1);
        }
        
        .role-option input {
            display: none;
        }
        
        .role-option span {
            display: block;
            font-weight: 500;
        }
        
        .role-option small {
            color: var(--color-gray-light);
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="auth-page">
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
            <nav class="nav">
                <a href="../../index.php" class="nav-link">Inicio</a>
                <a href="../inmuebles/listar.php" class="nav-link">Explorar</a>
            </nav>
        </div>
    </header>

    <!-- Login Form -->
    <div class="auth-container">
        <div class="auth-box">
            <h2>Iniciar Sesión</h2>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="../../controllers/AuthController.php" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" class="form-control" 
                           placeholder="ejemplo@gmail.com" required>
                </div>

                <div class="form-group">
                    <label for="contrasena">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" class="form-control" 
                           placeholder="********" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Ingresar</button>
            </form>

            <div class="auth-divider">
                <span>o</span>
            </div>

            <button class="btn-google" type="button">
                <img src="https://cdn-icons-png.flaticon.com/512/281/281764.png" alt="Google">
                Ingresar con Google
            </button>

            <div class="auth-extra">
                <p>¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
            </div>
        </div>
    </div>

    <script src="../../assets/js/main.js"></script>
</body>
</html>
