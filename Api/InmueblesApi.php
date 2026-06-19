<?php

require_once __DIR__ . '/../controllers/InmuebleController.php';

header('Content-Type: application/json');

$controller = new InmuebleController();

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

try {

    switch ($method) {

        case 'GET':
            if ($id) {
                echo json_encode($controller->detalle($id));
            } else {
                echo json_encode($controller->listar());
            }
            break;

        case 'POST':
            echo json_encode($controller->crear());
            break;

        case 'PUT':
            parse_str(file_get_contents("php://input"), $_POST);
            echo json_encode($controller->actualizar($id));
            break;

        case 'DELETE':
            echo json_encode($controller->eliminar($id));
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno',
        'debug' => $e->getMessage()
    ]);
}