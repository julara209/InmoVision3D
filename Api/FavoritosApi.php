<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/FavoritoController.php';

header('Content-Type: application/json');

$controller = new FavoritoController();
$metodo     = $_SERVER['REQUEST_METHOD'];

/* Leer body JSON si viene por POST (como en favoritos.php: fetch con JSON.stringify) */
$body = array();
if ($metodo === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $body = $decoded;
    }
}

/* La accion puede venir en el JSON (POST), en $_POST normal, o en $_GET */
$action = '';
if (isset($body['action']))        $action = $body['action'];
elseif (isset($_POST['action']))   $action = $_POST['action'];
elseif (isset($_GET['action']))    $action = $_GET['action'];

/* Si vino inmueble_id dentro del JSON, lo inyectamos a $_POST para que
   el controller (que lee $_POST/$_GET) lo encuentre sin tener que tocarlo */
if (isset($body['inmueble_id'])) {
    $_POST['inmueble_id'] = $body['inmueble_id'];
}

try {
    switch ($action) {
        case 'toggle':
            $resultado = $controller->toggle();
            break;

        case 'listar':
            $resultado = $controller->listar();
            break;

        case 'verificar':
            $inmuebleId = (int)(isset($body['inmueble_id']) ? $body['inmueble_id'] : (isset($_GET['inmueble_id']) ? $_GET['inmueble_id'] : 0));
            $resultado = $controller->verificar($inmuebleId);
            break;

        case 'ids':
            $resultado = $controller->obtenerIds();
            break;

        case 'contar':
            $resultado = $controller->contar();
            break;

        default:
            http_response_code(400);
            $resultado = array('success' => false, 'error' => 'Accion no valida: ' . $action);
            break;
    }

    /* Si el controller indico que falta login, respondemos 401 */
    if (isset($resultado['require_login']) && $resultado['require_login']) {
        http_response_code(401);
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Error interno: ' . $e->getMessage()));
}