<?php
require_once __DIR__ . '/InmuebleController.php';

header('Content-Type: application/json');

$controller = new InmuebleController();
$action = $_GET['action'] ?? '';

switch ($action) {

    case 'crear':
        echo json_encode($controller->crearInmueble());
        break;

    case 'actualizar':
        $id = $_GET['id'] ?? 0;
        echo json_encode($controller->actualizarInmueble($id));
        break;

    case 'eliminar':
        $id = $_GET['id'] ?? 0;
        echo json_encode($controller->eliminarInmueble($id));
        break;

    default:
        echo json_encode([
            'success' => false,
            'error' => 'Acción inválida'
        ]);
}

exit;