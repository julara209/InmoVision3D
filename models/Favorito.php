<?php
/**
 * Modelo de Favorito
 * InmoVision 3D
 */

require_once __DIR__ . '/../config/config.php';

class Favorito {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Agregar a favoritos
     */
    public function agregar($usuarioId, $inmuebleId) {
        // Verificar si ya existe
        if ($this->existe($usuarioId, $inmuebleId)) {
            return ['success' => false, 'error' => 'Ya está en favoritos'];
        }

        $sql = "INSERT INTO favoritos (usuario_id, inmueble_id) VALUES (?, ?)";
        $id = $this->db->insert($sql, [$usuarioId, $inmuebleId], "ii");

        return ['success' => true, 'id' => $id];
    }

    /**
     * Quitar de favoritos
     */
    public function quitar($usuarioId, $inmuebleId) {
        $sql = "DELETE FROM favoritos WHERE usuario_id = ? AND inmueble_id = ?";
        $affected = $this->db->delete($sql, [$usuarioId, $inmuebleId], "ii");

        return ['success' => $affected > 0];
    }

    /**
     * Verificar si existe
     */
    public function existe($usuarioId, $inmuebleId) {
        $sql = "SELECT id FROM favoritos WHERE usuario_id = ? AND inmueble_id = ?";
        $resultado = $this->db->selectOne($sql, [$usuarioId, $inmuebleId], "ii");
        return $resultado !== null;
    }

    /**
     * Toggle favorito
     */
    public function toggle($usuarioId, $inmuebleId) {
        if ($this->existe($usuarioId, $inmuebleId)) {
            $this->quitar($usuarioId, $inmuebleId);
            return ['success' => true, 'action' => 'removed', 'is_favorite' => false];
        } else {
            $this->agregar($usuarioId, $inmuebleId);
            return ['success' => true, 'action' => 'added', 'is_favorite' => true];
        }
    }

    /**
     * Obtener favoritos de un usuario
     * Ajustado a las columnas reales de Inmuebles (idInmueble, idPublicador, ubicacion, operacion...)
     */
    public function obtenerPorUsuario($usuarioId) {
        $sql = "SELECT f.id as favorito_id,
                i.idInmueble, i.titulo, i.descripcion, i.precio, i.ubicacion,
                i.tipo, i.operacion, i.habitaciones, i.banos, i.area, i.estado,
                u.nombre as publicador_nombre, u.apellido as publicador_apellido
                FROM favoritos f
                JOIN Inmuebles i ON f.inmueble_id = i.idInmueble
                JOIN Usuarios u ON i.idPublicador = u.idUsuario
                WHERE f.usuario_id = ?
                ORDER BY f.id DESC";

        return $this->db->select($sql, [$usuarioId], "i");
    }

    /**
     * Contar favoritos de un usuario
     */
    public function contarPorUsuario($usuarioId) {
        $sql = "SELECT COUNT(*) as total FROM favoritos WHERE usuario_id = ?";
        $resultado = $this->db->selectOne($sql, [$usuarioId], "i");
        return $resultado['total'];
    }

    /**
     * Contar favoritos de un inmueble
     */
    public function contarPorInmueble($inmuebleId) {
        $sql = "SELECT COUNT(*) as total FROM favoritos WHERE inmueble_id = ?";
        $resultado = $this->db->selectOne($sql, [$inmuebleId], "i");
        return $resultado['total'];
    }

    /**
     * Obtener IDs de favoritos de un usuario
     */
    public function obtenerIds($usuarioId) {
        $sql = "SELECT inmueble_id FROM favoritos WHERE usuario_id = ?";
        $resultados = $this->db->select($sql, [$usuarioId], "i");
        return array_column($resultados, 'inmueble_id');
    }
}