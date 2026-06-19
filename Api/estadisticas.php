<?php
session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$usuario_id = (int) $_SESSION['usuario_id'];
$db = Database::getInstance();

try {
    // Favoritos
    $fav = $db->selectOne(
        "SELECT COUNT(*) as total FROM favoritos WHERE usuario_id = ?",
        [$usuario_id], "i"
    );
    $favoritos = (int) $fav['total'];

    $publicados  = null;
    $solicitudes = null;

    if (in_array($_SESSION['rol'], ['publicador', 'admin'])) {
        $pub = $db->selectOne(
            "SELECT COUNT(*) as total FROM inmuebles WHERE idPublicador = ?",
            [$usuario_id], "i"
        );
        $publicados = (int) $pub['total'];

        $sol = $db->selectOne(
            "SELECT COUNT(*) as total FROM solicitudes WHERE idCliente= ?",
            [$usuario_id], "i"
        );
        $solicitudes = (int) $sol['total'];
    }

    echo json_encode([
        'success'     => true,
        'favoritos'   => $favoritos,
        'publicados'  => $publicados,
        'solicitudes' => $solicitudes,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false]);
}