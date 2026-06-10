<?php
require_once __DIR__ . '/../config/config.php';

class Usuario {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear nuevo usuario
     */
    public function crear($datos) {

        $sql = "INSERT INTO usuarios
                (nombre, apellido, correo, contrasena, telefono, rol)
                VALUES (?, ?, ?, ?, ?, ?)";

        $contrasenaHash = password_hash(
            $datos['contrasena'],
            PASSWORD_DEFAULT
        );

        return $this->db->insert(
            $sql,
            [
                $datos['nombre'],
                $datos['apellido'],
                $datos['correo'],
                $contrasenaHash,
                $datos['telefono'] ?? '',
                $datos['rol'] ?? ROL_CLIENTE
            ],
            "ssssss"
        );
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($id) {

        $sql = "SELECT
                    idUsuario,
                    nombre,
                    apellido,
                    correo,
                    telefono,
                    rol
                FROM usuarios
                WHERE idUsuario = ?";

        return $this->db->selectOne($sql, [$id], "i");
    }

    /**
     * Obtener usuario por correo
     */
    public function obtenerPorCorreo($correo) {

        $sql = "SELECT *
                FROM usuarios
                WHERE correo = ?";

        return $this->db->selectOne($sql, [$correo], "s");
    }

    /**
     * Validar login
     */
    public function validarLogin($correo, $contrasena) {

        $usuario = $this->obtenerPorCorreo($correo);

        if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {

            return [
                'success' => true,
                'usuario' => $usuario
            ];
        }

        return [
            'success' => false,
            'error' => 'Credenciales incorrectas'
        ];
    }

    /**
     * Actualizar usuario
     */
    public function actualizar($id, $datos) {

        $campos = [];
        $valores = [];
        $tipos = "";

        if (isset($datos['nombre'])) {
            $campos[] = "nombre = ?";
            $valores[] = $datos['nombre'];
            $tipos .= "s";
        }

        if (isset($datos['apellido'])) {
            $campos[] = "apellido = ?";
            $valores[] = $datos['apellido'];
            $tipos .= "s";
        }

        if (isset($datos['correo'])) {
            $campos[] = "correo = ?";
            $valores[] = $datos['correo'];
            $tipos .= "s";
        }

        if (isset($datos['telefono'])) {
            $campos[] = "telefono = ?";
            $valores[] = $datos['telefono'];
            $tipos .= "s";
        }

        if (isset($datos['rol'])) {
            $campos[] = "rol = ?";
            $valores[] = $datos['rol'];
            $tipos .= "s";
        }

        if (!empty($datos['contrasena'])) {
            $campos[] = "contrasena = ?";
            $valores[] = password_hash(
                $datos['contrasena'],
                PASSWORD_DEFAULT
            );
            $tipos .= "s";
        }

        if (empty($campos)) {
            return false;
        }

        $valores[] = $id;
        $tipos .= "i";

        $sql = "UPDATE usuarios
                SET " . implode(", ", $campos) . "
                WHERE idUsuario = ?";

        return $this->db->update($sql, $valores, $tipos);
    }

    /**
     * Eliminar usuario
     */
    public function eliminar($id) {

        $sql = "DELETE FROM usuarios
                WHERE idUsuario = ?";

        return $this->db->delete($sql, [$id], "i");
    }

    /**
     * Listar todos los usuarios
     */
    public function listarTodos($filtros = []) {

        $sql = "SELECT
                    idUsuario,
                    nombre,
                    apellido,
                    correo,
                    telefono,
                    rol
                FROM usuarios
                WHERE 1=1";

        $params = [];
        $tipos = "";

        if (!empty($filtros['rol'])) {
            $sql .= " AND rol = ?";
            $params[] = $filtros['rol'];
            $tipos .= "s";
        }

        if (!empty($filtros['busqueda'])) {

            $sql .= " AND (
                        nombre LIKE ?
                        OR apellido LIKE ?
                        OR correo LIKE ?
                     )";

            $busqueda = "%" . $filtros['busqueda'] . "%";

            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;

            $tipos .= "sss";
        }

        $sql .= " ORDER BY idUsuario DESC";

        if (!empty($filtros['limite'])) {

            $sql .= " LIMIT " . (int)$filtros['limite'];

            if (!empty($filtros['offset'])) {
                $sql .= " OFFSET " . (int)$filtros['offset'];
            }
        }

        return $this->db->select($sql, $params, $tipos);
    }

    /**
     * Contar usuarios
     */
    public function contar($filtros = []) {

        $sql = "SELECT COUNT(*) AS total
                FROM usuarios
                WHERE 1=1";

        $params = [];
        $tipos = "";

        if (!empty($filtros['rol'])) {
            $sql .= " AND rol = ?";
            $params[] = $filtros['rol'];
            $tipos .= "s";
        }

        $resultado = $this->db->selectOne(
            $sql,
            $params,
            $tipos
        );

        return $resultado['total'] ?? 0;
    }

    /**
     * Verificar si el correo ya existe
     */
    public function existeCorreo($correo, $excluirId = null) {

        $sql = "SELECT idUsuario
                FROM usuarios
                WHERE correo = ?";

        $params = [$correo];
        $tipos = "s";

        if ($excluirId !== null) {

            $sql .= " AND idUsuario != ?";

            $params[] = $excluirId;
            $tipos .= "i";
        }

        $resultado = $this->db->selectOne(
            $sql,
            $params,
            $tipos
        );

        return $resultado !== null;
    }
}
?>
