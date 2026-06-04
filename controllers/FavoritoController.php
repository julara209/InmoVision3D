<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Favorito.php';

class FavoritoController {
    private $favorito;

    public function __construct() {
        $this->favorito = new Favorito();
    }

    /**
     * Toggle favorito (agregar/quitar)
     */
    public function toggle() {
        if (!isLoggedIn()) {
            return ['success' => false, 'error' => 'Debe iniciar sesión', 'require_login' => true];
        }

        $inmuebleId = (int)($_POST['inmueble_id'] ?? $_GET['inmueble_id'] ?? 0);

        if ($inmuebleId <= 0) {
            return ['success' => false, 'error' => 'Inmueble no válido'];
        }

        return $this->favorito->toggle($_SESSION['usuario_id'], $inmuebleId);
    }

    /**
     * Obtener favoritos del usuario actual
     */
    public function listar() {
        if (!isLoggedIn()) {
            return ['success' => false, 'error' => 'Debe iniciar sesión', 'require_login' => true];
        }

        $favoritos = $this->favorito->obtenerPorUsuario($_SESSION['usuario_id']);
        $total = count($favoritos);

        return [
            'success' => true,
            'favoritos' => $favoritos,
            'total' => $total
        ];
    }

    /**
     * Verificar si un inmueble es favorito
     */
    public function verificar($inmuebleId) {
        if (!isLoggedIn()) {
            return ['is_favorite' => false];
        }

        return [
            'is_favorite' => $this->favorito->existe($_SESSION['usuario_id'], $inmuebleId)
        ];
    }

    /**
     * Obtener IDs de favoritos
     */
    public function obtenerIds() {
        if (!isLoggedIn()) {
            return ['ids' => []];
        }

        return [
            'ids' => $this->favorito->obtenerIds($_SESSION['usuario_id'])
        ];
    }

    /**
     * Contar favoritos del usuario
     */
    public function contar() {
        if (!isLoggedIn()) {
            return ['total' => 0];
        }

        return [
            'total' => $this->favorito->contarPorUsuario($_SESSION['usuario_id'])
        ];
    }
}
?>
