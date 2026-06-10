<?php
require_once __DIR__ . '/AdminController.php';

header('Content-Type: application/json');

$controller = new AdminController();
$action = $_GET['action'] ?? '';

switch ($action) {

    case 'crear':
        echo json_encode($controller->crearUsuario());
        break;

    case 'actualizar':
        $id = $_GET['id'] ?? 0;
        echo json_encode($controller->actualizarUsuario($id));
        break;

    case 'eliminar':
        $id = $_GET['id'] ?? 0;
        echo json_encode($controller->eliminarUsuario($id));
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Acción inválida']);
}

exit;