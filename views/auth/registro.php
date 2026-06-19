<?php
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
    <title>Crear Cuenta - InmoVision 3D</title>
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
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
            max-width: 580px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .auth-box h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-option {
            flex: 1;
            padding: 15px;
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
            margin-bottom: 5px;
        }
        
        .role-option small {
            color: var(--color-gray-light);
            font-size: 0.8rem;
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
        
        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="auth-page">
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
    <img 
        src="../../assets/img/logo.png" 
        alt="InmoVision 3D logo" 
        class="logo-icon"
    >
    <span class="logo-text">InmoVision <span class="highlight">3D</span></span>
</a>
            <nav class="nav">
                <a href="../../index.php" class="nav-link">Inicio</a>
                <a href="../inmuebles/listar.php" class="nav-link">Explorar</a>
            </nav>
        </div>
    </header>

    <!-- Register Form -->
    <div class="auth-container">
        <div class="auth-box">
            <h2>Crear Cuenta</h2>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="../../controllers/AuthController.php" method="POST">
                <input type="hidden" name="action" value="registro">
                
                <div class="form-group">
                    <label>Tipo de cuenta</label>
                    <div class="role-selector">
                        <label class="role-option active" id="roleCliente">
                            <input type="radio" name="rol" value="cliente" checked>
                            <span>Cliente</span>
                            <small>Buscar inmuebles</small>
                        </label>
                        <label class="role-option" id="rolePublicador">
                            <input type="radio" name="rol" value="publicador">
                            <span>Publicador</span>
                            <small>Publicar inmuebles</small>
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" 
                               placeholder="Tu nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido">Apellido</label>
                        <input type="text" id="apellido" name="apellido" class="form-control" 
                               placeholder="Tu apellido" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" class="form-control" 
                           placeholder="ejemplo@gmail.com" required>
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control" 
                           placeholder="300 123 4567">
                </div>

                <div class="form-group">
    <label for="contrasena">Contraseña</label>
    <div style="position: relative;">
        <input type="password" id="contrasena" name="contrasena" class="form-control" 
               placeholder="Mínimo 6 caracteres" required minlength="6" style="padding-right: 44px;">
        <button type="button" onclick="togglePw('contrasena','eye1')"
                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;display:flex;align-items:center;padding:4px;"
                aria-label="Mostrar u ocultar contraseña">
            <i class="ti ti-eye" id="eye1"></i>
        </button>
    </div>
</div>

<div class="form-group">
    <label for="confirmar_contrasena">Confirmar contraseña</label>
    <div style="position: relative;">
        <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" class="form-control" 
               placeholder="Repite tu contraseña" required minlength="6" style="padding-right: 44px;">
        <button type="button" onclick="togglePw('confirmar_contrasena','eye2')"
                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;display:flex;align-items:center;padding:4px;"
                aria-label="Mostrar u ocultar contraseña">
            <i class="ti ti-eye" id="eye2"></i>
        </button>
    </div>
    <small id="pw-msg" style="display:none;margin-top:6px;font-size:0.82rem;"></small>
</div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Crear Cuenta</button>
            </form>

            <div class="auth-divider">
                <span>o</span>
            </div>

            <button class="btn-google" type="button">
                <img src="https://cdn-icons-png.flaticon.com/512/281/281764.png" alt="Google">
                Registrarse con Google
            </button>

            <div class="auth-extra">
                <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
            </div>
        </div>
    </div>

    <script>
    // Role selector
    const roleOptions = document.querySelectorAll('.role-option');
    roleOptions.forEach(option => {
        option.addEventListener('click', () => {
            roleOptions.forEach(o => o.classList.remove('active'));
            option.classList.add('active');
        });
    });

    // Ojito
    function togglePw(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'ti ti-eye-off';
        } else {
            input.type = 'password';
            icon.className = 'ti ti-eye';
        }
    }

    // Validar que las contraseñas coincidan
    const pw = document.getElementById('contrasena');
    const pw2 = document.getElementById('confirmar_contrasena');
    const msg = document.getElementById('pw-msg');

    function validarContrasenas() {
        if (pw2.value === '') {
            msg.style.display = 'none';
            pw2.style.borderColor = '';
            return;
        }
        if (pw.value === pw2.value) {
            msg.textContent = '✓ Las contraseñas coinciden';
            msg.style.color = '#22c55e';
            pw2.style.borderColor = '#22c55e';
        } else {
            msg.textContent = '✗ Las contraseñas no coinciden';
            msg.style.color = '#ef4444';
            pw2.style.borderColor = '#ef4444';
        }
        msg.style.display = 'block';
    }

    pw.addEventListener('input', validarContrasenas);
    pw2.addEventListener('input', validarContrasenas);

    // Bloquear envío si no coinciden
    document.querySelector('form').addEventListener('submit', function(e) {
        if (pw.value !== pw2.value) {
            e.preventDefault();
            msg.textContent = '✗ Las contraseñas no coinciden';
            msg.style.color = '#ef4444';
            msg.style.display = 'block';
            pw2.focus();
        }
    });
</script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
