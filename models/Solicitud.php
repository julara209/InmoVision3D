<?php
/**
 * Modelo de Solicitud
 * InmoVision 3D
 */

require_once __DIR__ . '/../config/config.php';

class Solicitud {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear nueva solicitud
     */
    public function crear($datos) {
        $sql = "INSERT INTO solicitudes (cliente_id, inmueble_id, mensaje, tipo_solicitud, fecha_cita, hora_cita) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        return $this->db->insert($sql, [
            $datos['cliente_id'],
            $datos['inmueble_id'],
            $datos['mensaje'],
            $datos['tipo_solicitud'] ?? 'informacion',
            $datos['fecha_cita'] ?? null,
            $datos['hora_cita'] ?? null
        ], "iissss");
    }

    /**
     * Obtener solicitud por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT s.*, 
                i.titulo as inmueble_titulo, i.ubicacion as inmueble_ubicacion, i.precio as inmueble_precio,
                (SELECT url_imagen FROM imagenes_inmueble WHERE inmueble_id = i.id AND es_principal = 1 LIMIT 1) as inmueble_imagen,
                c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.correo as cliente_correo, c.telefono as cliente_telefono,
                p.nombre as publicador_nombre, p.apellido as publicador_apellido
                FROM solicitudes s
                JOIN inmuebles i ON s.inmueble_id = i.id
                JOIN usuarios c ON s.cliente_id = c.id
                JOIN usuarios p ON i.usuario_id = p.id
                WHERE s.id = ?";
        return $this->db->selectOne($sql, [$id], "i");
    }

    /**
     * Actualizar estado de solicitud
     */
    public function actualizarEstado($id, $estado, $respuesta = null) {
        $sql = "UPDATE solicitudes SET estado = ?, respuesta = ?, fecha_respuesta = NOW() WHERE id = ?";
        return $this->db->update($sql, [$estado, $respuesta, $id], "ssi");
    }

    /**
     * Obtener solicitudes enviadas por un usuario (cliente)
     */
    public function obtenerEnviadas($usuarioId) {
        $sql = "SELECT s.*, 
                i.titulo as inmueble_titulo, i.ubicacion as inmueble_ubicacion,
                (SELECT url_imagen FROM imagenes_inmueble WHERE inmueble_id = i.id AND es_principal = 1 LIMIT 1) as inmueble_imagen,
                p.nombre as publicador_nombre, p.apellido as publicador_apellido
                FROM solicitudes s
                JOIN inmuebles i ON s.inmueble_id = i.id
                JOIN usuarios p ON i.usuario_id = p.id
                WHERE s.cliente_id = ?
                ORDER BY s.fecha_solicitud DESC";
        return $this->db->select($sql, [$usuarioId], "i");
    }

    /**
     * Obtener solicitudes recibidas (para publicador)
     */
    public function obtenerRecibidas($usuarioId) {
        $sql = "SELECT s.*, 
                i.titulo as inmueble_titulo, i.ubicacion as inmueble_ubicacion,
                (SELECT url_imagen FROM imagenes_inmueble WHERE inmueble_id = i.id AND es_principal = 1 LIMIT 1) as inmueble_imagen,
                c.nombre as cliente_nombre, c.apellido as cliente_apellido, c.correo as cliente_correo, c.telefono as cliente_telefono
                FROM solicitudes s
                JOIN inmuebles i ON s.inmueble_id = i.id
                JOIN usuarios c ON s.cliente_id = c.id
                WHERE i.usuario_id = ?
                ORDER BY s.fecha_solicitud DESC";
        return $this->db->select($sql, [$usuarioId], "i");
    }

    /**
 * Obtener solicitudes pendientes (admin)
 */
public function obtenerPendientes() {

    $sql = "SELECT s.*,
                   u.nombre AS cliente_nombre,
                   u.apellido AS cliente_apellido,
                   i.titulo AS inmueble_titulo
            FROM Solicitudes s
            INNER JOIN Usuarios u
                ON s.idCliente = u.idUsuario
            INNER JOIN Inmuebles i
                ON s.idInmueble = i.idInmueble
            WHERE s.estado = 'pendiente'
            ORDER BY s.fecha DESC";

    return $this->db->select($sql);
}
    /**
     * Contar solicitudes pendientes recibidas
     */
    public function contarPendientes($usuarioId) {
        $sql = "SELECT COUNT(*) as total 
                FROM solicitudes s
                JOIN inmuebles i ON s.inmueble_id = i.id
                WHERE i.usuario_id = ? AND s.estado = 'pendiente'";
        $resultado = $this->db->selectOne($sql, [$usuarioId], "i");
        return $resultado['total'];
    }

    /**
     * Listar todas las solicitudes (admin)
     */
    public function listarTodas($filtros = []) {

    $sql = "SELECT s.*,
                   i.titulo AS inmueble_titulo,
                   c.nombre AS cliente_nombre,
                   c.apellido AS cliente_apellido,
                   p.nombre AS publicador_nombre,
                   p.apellido AS publicador_apellido
            FROM Solicitudes s
            INNER JOIN Inmuebles i
                ON s.idInmueble = i.idInmueble
            INNER JOIN Usuarios c
                ON s.idCliente = c.idUsuario
            INNER JOIN Usuarios p
                ON i.idPublicador = p.idUsuario
            WHERE 1=1";

    $params = [];
    $tipos = "";

    if (!empty($filtros['estado'])) {
        $sql .= " AND s.estado = ?";
        $params[] = $filtros['estado'];
        $tipos .= "s";
    }

    $sql .= " ORDER BY s.fecha DESC";

    if (!empty($filtros['limite'])) {
        $sql .= " LIMIT " . (int)$filtros['limite'];
    }

    return $this->db->select($sql, $params, $tipos);
}

    /**
     * Eliminar solicitud
     */
    public function eliminar($id) {
        $sql = "DELETE FROM solicitudes WHERE id = ?";
        return $this->db->delete($sql, [$id], "i");
    }
}
?>
