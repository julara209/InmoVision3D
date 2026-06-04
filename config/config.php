<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración del sitio
define('SITE_NAME', 'InmoVision 3D');
define('SITE_URL', 'http://localhost/InmoVision3D');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Roles de usuario
define('ROL_CLIENTE', 'cliente');
define('ROL_PUBLICADOR', 'publicador');
define('ROL_ADMIN', 'admin');

// Estados de inmuebles
define('ESTADO_DISPONIBLE', 'disponible');
define('ESTADO_VENDIDO', 'vendido');
define('ESTADO_ARRENDADO', 'arrendado');
define('ESTADO_PAUSADO', 'pausado');

// Estados de solicitudes
define('SOLICITUD_PENDIENTE', 'pendiente');
define('SOLICITUD_ACEPTADA', 'aceptada');
define('SOLICITUD_RECHAZADA', 'rechazada');

// Tipos de operación
define('OPERACION_VENTA', 'venta');
define('OPERACION_ARRIENDO', 'arriendo');

// Incluir la base de datos
require_once __DIR__ . '/database.php';

// Funciones de utilidad
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function isAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === ROL_ADMIN;
}

function isPublicador() {
    return isset($_SESSION['rol']) && ($_SESSION['rol'] === ROL_PUBLICADOR || $_SESSION['rol'] === ROL_ADMIN);
}

function isCliente() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === ROL_CLIENTE;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/views/auth/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php?error=acceso_denegado');
        exit();
    }
}

function requirePublicador() {
    requireLogin();
    if (!isPublicador()) {
        header('Location: ' . SITE_URL . '/index.php?error=acceso_denegado');
        exit();
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatPrice($price) {
    return '$' . number_format($price, 0, ',', '.');
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function uploadFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Error al subir el archivo'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'El archivo es demasiado grande'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipo de archivo no permitido'];
    }

    $filename = uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_PATH . $directory . '/' . $filename;

    if (!is_dir(UPLOAD_PATH . $directory)) {
        mkdir(UPLOAD_PATH . $directory, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => 'assets/uploads/' . $directory . '/' . $filename];
    }

    return ['success' => false, 'error' => 'Error al guardar el archivo'];
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header('Location: ' . $url);
    exit();
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}
?>
