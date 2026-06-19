<?php
require_once __DIR__ . '/../controllers/AdminController.php';

header('Content-Type: application/json');

$controller = new AdminController();

$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {

    case 'GET':
        if ($id) {
            echo json_encode($controller->obtenerUsuario($id));
        } else {
            echo json_encode($controller->listarUsuarios());
        }
        break;

    case 'POST':
        echo json_encode($controller->crearUsuario());
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $_PUT);
        echo json_encode($controller->actualizarUsuario($id));
        break;

    case 'DELETE':
        echo json_encode($controller->eliminarUsuario($id));
        break;

    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Método no permitido'
        ]);
}