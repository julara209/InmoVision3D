<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private $usuario;

    public function __construct() {
        $this->usuario = new Usuario();
    }

    /**
     * Procesar login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'error' => 'Método no permitido'];
        }

        $correo = sanitize($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if (empty($correo) || empty($contrasena)) {
            return ['success' => false, 'error' => 'Todos los campos son obligatorios'];
        }

        $resultado = $this->usuario->validarLogin($correo, $contrasena);

        if ($resultado['success']) {
            $usuario = $resultado['usuario'];
            
            $_SESSION['usuario_id'] = $usuario['idUsuario'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['apellido'] = $usuario['apellido'];
            $_SESSION['correo'] = $usuario['correo'];
            $_SESSION['telefono']= $usuario['telefono'];
            $_SESSION['rol'] = $usuario['rol'];
            return ['success' => true, 'redirect' => $this->getRedirectUrl($usuario['rol'])];
        }

        return $resultado;
    }

    /**
     * Procesar registro
     */
    public function registro() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'error' => 'Método no permitido'];
        }

        $datos = [
            'nombre' => sanitize($_POST['nombre'] ?? ''),
            'apellido' => sanitize($_POST['apellido'] ?? ''),
            'correo' => sanitize($_POST['correo'] ?? ''),
            'contrasena' => $_POST['contrasena'] ?? '',
            'telefono' => sanitize($_POST['telefono'] ?? ''),
            'rol' => sanitize($_POST['rol'] ?? ROL_CLIENTE)
        ];

        // Validaciones
        if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['correo']) || empty($datos['contrasena'])) {
            return ['success' => false, 'error' => 'Todos los campos obligatorios deben ser completados'];
        }

        if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'El correo electrónico no es válido'];
        }

        if (strlen($datos['contrasena']) < 6) {
            return ['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres'];
        }

        if ($this->usuario->existeCorreo($datos['correo'])) {
            return ['success' => false, 'error' => 'El correo electrónico ya está registrado'];
        }

        // Solo permitir roles cliente o publicador en registro público
        if (!in_array($datos['rol'], [ROL_CLIENTE, ROL_PUBLICADOR])) {
            $datos['rol'] = ROL_CLIENTE;
        }

        $id = $this->usuario->crear($datos);

        if ($id) {
            // Auto login después del registro
            $_SESSION['usuario_id'] = $id;
            $_SESSION['nombre'] = $datos['nombre'];
            $_SESSION['apellido'] = $datos['apellido'];
            $_SESSION['correo'] = $datos['correo'];
            $_SESSION['rol'] = $datos['rol'];
            return ['success' => true, 'redirect' => $this->getRedirectUrl($datos['rol'])];
        }

        return ['success' => false, 'error' => 'Error al crear el usuario'];
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        session_destroy();
        return ['success' => true, 'redirect' => SITE_URL . '/index.php'];
    }

    /**
     * Obtener URL de redirección según rol
     */
    private function getRedirectUrl($rol) {
        switch ($rol) {
            case ROL_ADMIN:
                return SITE_URL . '/views/admin/dashboard.php';
            case ROL_PUBLICADOR:
                return SITE_URL . '/views/usuario/perfil.php';
            default:
                return SITE_URL . '/index.php';
        }
    }

    /**
     * Verificar si está autenticado (API)
     */
    public function verificarSesion() {
        if (isLoggedIn()) {
            return [
                'authenticated' => true,
                'user' => [
                    'id' => $_SESSION['usuario_id'],
                    'nombre' => $_SESSION['nombre'],
                    'apellido' => $_SESSION['apellido'],
                    'correo' => $_SESSION['correo'],
                    'rol' => $_SESSION['rol'],
                ]
            ];
        }
        return ['authenticated' => false];
    }
}

// Procesar acciones si se llama directamente
if (basename($_SERVER['PHP_SELF']) === 'AuthController.php') {
    $controller = new AuthController();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'login':
            $resultado = $controller->login();
            if ($resultado['success']) {
                header('Location: ' . $resultado['redirect']);
                exit();
            } else {
                $_SESSION['error'] = $resultado['error'];
                header('Location: ' . SITE_URL . '/views/auth/login.php');
                exit();
            }
            break;

        case 'registro':
            $resultado = $controller->registro();
            if ($resultado['success']) {
                header('Location: ' . $resultado['redirect']);
                exit();
            } else {
                $_SESSION['error'] = $resultado['error'];
                header('Location: ' . SITE_URL . '/views/auth/registro.php');
                exit();
            }
            break;

        case 'logout':
            $controller->logout();
            header('Location: ' . SITE_URL . '/index.php');
            exit();
            break;
    }
}
?>
