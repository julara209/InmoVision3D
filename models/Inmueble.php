<?php

require_once __DIR__ . '/../config/config.php';

class Inmueble {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear un nuevo inmueble.
     */
    public function crear($datos) {
        $sql = "INSERT INTO Inmuebles
                (titulo, descripcion, precio, ubicacion, tipo, operacion,
                 habitaciones, banos, area, estado, idPublicador)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $this->db->insert(
            $sql,
            [
                $datos['titulo'],
                $datos['descripcion'],
                $datos['precio'],
                $datos['ubicacion'],
                $datos['tipo'],
                $datos['operacion'],
                $datos['habitaciones'],
                $datos['banos'],
                $datos['area'],
                $datos['estado'] ?? ESTADO_DISPONIBLE,
                $datos['idPublicador']
            ],
            "ssdsssiidsi"
        );
    }

    /**
     * Obtener inmueble por ID.
     */
    public function obtenerPorId($id) {
        $sql = "SELECT i.*,
                u.nombre AS publicador_nombre,
                u.apellido AS publicador_apellido,
                u.correo AS publicador_correo,
                u.telefono AS publicador_telefono
                FROM Inmuebles i
                INNER JOIN Usuarios u
                ON i.idPublicador = u.idUsuario
                WHERE i.idInmueble = ?";

        $inmueble = $this->db->selectOne($sql, [$id], "i");

        if ($inmueble) {
            $inmueble['imagenes'] = $this->obtenerImagenes($id);
            $inmueble['planos'] = $this->obtenerPlanos($id);
        }

        return $inmueble;
    }

    /**
     * Obtener imágenes asociadas a un inmueble.
     */
    public function obtenerImagenes($idInmueble) {
        $sql = "SELECT * FROM Imagenes_inmueble
                WHERE idInmueble = ?";

        return $this->db->select($sql, [$idInmueble], "i");
    }

    /**
     * Agregar imagen.
     */
    public function agregarImagen($idInmueble, $urlImagen) {
        $sql = "INSERT INTO Imagenes_inmueble
                (urlImagen, idInmueble)
                VALUES (?, ?)";

        return $this->db->insert(
            $sql,
            [$urlImagen, $idInmueble],
            "si"
        );
    }

    public function agregarImagenPrincipal($idInmueble, $urlImagen)
{
    // Quitar principal anterior
    $sql = "UPDATE Imagenes_inmueble
            SET es_principal = 0
            WHERE idInmueble = ?";

    $this->db->update($sql, [$idInmueble], "i");

    // Insertar nueva principal
    $sql = "INSERT INTO Imagenes_inmueble
            (urlImagen, es_principal, idInmueble)
            VALUES (?, 1, ?)";

    return $this->db->insert(
        $sql,
        [$urlImagen, $idInmueble],
        "si"
    );
}
    /**
     * Eliminar imagen.
     */
    public function eliminarImagen($idImagen) {
        $sql = "DELETE FROM Imagenes_inmueble
                WHERE idImagen = ?";

        return $this->db->delete($sql, [$idImagen], "i");
    }

    /**
     * Obtener planos del inmueble.
     */
    public function obtenerPlanos($idInmueble) {
        $sql = "SELECT *
                FROM Planos_2d
                WHERE idInmueble = ?";

        return $this->db->select($sql, [$idInmueble], "i");
    }

    /**
     * Actualizar inmueble.
     */
    public function actualizar($id, $datos) {

        $campos = [];
        $valores = [];
        $tipos = "";

        $permitidos = [
            'titulo','descripcion','precio','ubicacion',
            'tipo', 'operacion','habitaciones','banos','area','estado'
        ];

        $tiposCampos = [
            'titulo' => 's',
            'descripcion' => 's',
            'precio' => 'd',
            'ubicacion' => 's',
            'tipo' => 's',
            'operacion' => 's',
            'habitaciones' => 'i',
            'banos' => 'i',
            'area' => 'd',
            'estado' => 's'
        ];

        foreach ($permitidos as $campo) {
            if (isset($datos[$campo])) {
                $campos[] = "$campo = ?";
                $valores[] = $datos[$campo];
                $tipos .= $tiposCampos[$campo];
            }
        }

        if (empty($campos)) {
            return false;
        }

        $valores[] = $id;
        $tipos .= "i";

        $sql = "UPDATE Inmuebles
                SET " . implode(", ", $campos) . "
                WHERE idInmueble = ?";

        return $this->db->update($sql, $valores, $tipos);
    }

    /**
     * Eliminar inmueble.
     */
    public function eliminar($id) {
        $sql = "DELETE FROM Inmuebles
                WHERE idInmueble = ?";

        return $this->db->delete($sql, [$id], "i");
    }

    /**
     * Listar inmuebles con filtros.
     */
    public function listar($filtros = []) {

        $sql = "SELECT i.*,
                u.nombre AS publicador_nombre,
                u.apellido AS publicador_apellido,
                (SELECT img.urlImagen FROM Imagenes_inmueble img
                 WHERE img.idInmueble = i.idInmueble
                 ORDER BY img.es_principal DESC, img.idImagen ASC
                 LIMIT 1) AS imagen_principal
                FROM Inmuebles i
                INNER JOIN Usuarios u
                ON i.idPublicador = u.idUsuario
                WHERE 1=1";

        $params = [];
        $tipos = "";

        if (!empty($filtros['tipo'])) {
            $sql .= " AND i.tipo = ?";
            $params[] = $filtros['tipo'];
            $tipos .= "s";
        }

        if (!empty($filtros['operacion'])) {
            $sql .= " AND i.operacion = ?";
            $params[] = $filtros['operacion'];
            $tipos .= "s";
        }

        if (!empty($filtros['ubicacion'])) {
            $sql .= " AND i.ubicacion LIKE ?";
            $params[] = "%" . $filtros['ubicacion'] . "%";
            $tipos .= "s";
        }

        if (!empty($filtros['precio_min'])) {
            $sql .= " AND i.precio >= ?";
            $params[] = $filtros['precio_min'];
            $tipos .= "d";
        }

        if (!empty($filtros['precio_max'])) {
            $sql .= " AND i.precio <= ?";
            $params[] = $filtros['precio_max'];
            $tipos .= "d";
        }

        if (!empty($filtros['habitaciones'])) {
            $sql .= " AND i.habitaciones >= ?";
            $params[] = $filtros['habitaciones'];
            $tipos .= "i";
        }

        if (!empty($filtros['idPublicador'])) {
            $sql .= " AND i.idPublicador = ?";
            $params[] = $filtros['idPublicador'];
            $tipos .= "i";
        }

        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (i.titulo LIKE ? OR i.descripcion LIKE ?)";
            $busqueda = "%" . $filtros['busqueda'] . "%";
            $params[] = $busqueda;
            $params[] = $busqueda;
            $tipos .= "ss";
        }

        $sql .= " ORDER BY i.idInmueble DESC";

        return $this->db->select($sql, $params, $tipos);
    }

    /**
     * Contar inmuebles.
     */
    public function contar($filtros = []) {

        $sql = "SELECT COUNT(*) AS total
                FROM Inmuebles
                WHERE 1=1";

        $params = [];
        $tipos = "";

        if (!empty($filtros['operacion'])) {
            $sql .= " AND operacion = ?";
            $params[] = $filtros['operacion'];
            $tipos .= "s";
        }

        $resultado = $this->db->selectOne($sql, $params, $tipos);

        return $resultado['total'] ?? 0;
    }

    /**
     * Obtener inmuebles de un publicador.
     */
    public function obtenerPorUsuario($idPublicador) {

        return $this->listar([
            'idPublicador' => $idPublicador
        ]);
    }
}

?>