<?php
/**
 * Controlador de Administración
 * InmoVision 3D
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../models/Inmueble.php';
require_once __DIR__ . '/../models/Solicitud.php';

class AdminController {
    private $usuario;
    private $inmueble;
    private $solicitud;

    public function __construct() {
        $this->usuario = new Usuario();
        $this->inmueble = new Inmueble();
        $this->solicitud = new Solicitud();
    }

    /**
     * Obtener estadísticas del dashboard
     */
    public function dashboard() {
        requireAdmin();

        return [
            'success' => true,
            'stats' => [
                'total_usuarios' => $this->usuario->contar(),
                'usuarios_activos' => $this->usuario->contar(['activo' => 1]),
                'total_clientes' => $this->usuario->contar(['rol' => ROL_CLIENTE]),
                'total_publicadores' => $this->usuario->contar(['rol' => ROL_PUBLICADOR]),
                'total_inmuebles' => $this->inmueble->contar(),
                'inmuebles_disponibles' => $this->inmueble->contar(['estado' => ESTADO_DISPONIBLE]),
                'inmuebles_vendidos' => $this->inmueble->contar(['estado' => ESTADO_VENDIDO]),
                'solicitudes_pendientes' => count($this->solicitud->listarTodas(['estado' => SOLICITUD_PENDIENTE]))
            ],
            'ultimos_usuarios' => $this->usuario->listarTodos(['limite' => 5]),
            'ultimos_inmuebles' => $this->inmueble->listar(['limite' => 5]),
            'ultimas_solicitudes' => $this->solicitud->listarTodas(['limite' => 5])
        ];
    }

    /**
     * Listar usuarios
     */
    public function listarUsuarios() {
        requireAdmin();

        $filtros = [
            'rol' => sanitize($_GET['rol'] ?? ''),
            'activo' => isset($_GET['activo']) ? (int)$_GET['activo'] : null,
            'busqueda' => sanitize($_GET['q'] ?? ''),
            'limite' => (int)($_GET['limite'] ?? 20),
            'offset' => (int)($_GET['offset'] ?? 0)
        ];

        $filtros = array_filter($filtros, function($v) { return $v !== '' && $v !== null; });

        return [
            'success' => true,
            'usuarios' => $this->usuario->listarTodos($filtros),
            'total' => $this->usuario->contar($filtros)
        ];
    }

    /**
     * Obtener usuario
     */
    public function obtenerUsuario($id) {
        requireAdmin();

        $usuario = $this->usuario->obtenerPorId($id);
        
        if (!$usuario) {
            return ['success' => false, 'error' => 'Usuario no encontrado'];
        }

        // Obtener estadísticas del usuario
        $usuario['total_inmuebles'] = $this->inmueble->contar(['usuario_id' => $id]);

        return ['success' => true, 'usuario' => $usuario];
    }

    /**
     * Crear usuario (admin)
     */
    public function crearUsuario() {
        requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'error' => 'Método no permitido'];
        }

        $datos = [
            'nombre' => sanitize($_POST['nombre'] ?? ''),
            'apellido' => sanitize($_POST['apellido'] ?? ''),
            'correo' => sanitize($_POST['correo'] ?? ''),
            'contrasena' => $_POST['contrasena'] ?? '',
            'telefono' => sanitize($_POST['telefono'] ?? ''),
            'rol' => sanitize($_POST['rol'] ?? ROL_CLIENTE)
        ];

        // Validaciones
        if (empty($datos['nombre']) || empty($datos['correo']) || empty($datos['contrasena'])) {
            return ['success' => false, 'error' => 'Campos obligatorios incompletos'];
        }

        if ($this->usuario->existeCorreo($datos['correo'])) {
            return ['success' => false, 'error' => 'El correo ya está registrado'];
        }

        $id = $this->usuario->crear($datos);

        return $id 
            ? ['success' => true, 'id' => $id, 'mensaje' => 'Usuario creado exitosamente']
            : ['success' => false, 'error' => 'Error al crear usuario'];
    }

    /**
     * Actualizar usuario (admin)
     */
    public function actualizarUsuario($id) {
        requireAdmin();

        $datos = [];
        $campos = ['nombre', 'apellido', 'telefono', 'rol', 'activo'];

        foreach ($campos as $campo) {
            if (isset($_POST[$campo])) {
                $datos[$campo] = $campo === 'activo' ? (int)$_POST[$campo] : sanitize($_POST[$campo]);
            }
        }

        if (!empty($_POST['contrasena'])) {
            $datos['contrasena'] = $_POST['contrasena'];
        }

        $this->usuario->actualizar($id, $datos);

        return ['success' => true, 'mensaje' => 'Usuario actualizado exitosamente'];
    }

    /**
     * Eliminar usuario
     */
    public function eliminarUsuario($id) {
        requireAdmin();

        // No permitir eliminar el propio usuario
        if ($id == $_SESSION['usuario_id']) {
            return ['success' => false, 'error' => 'No puede eliminarse a sí mismo'];
        }

        $this->usuario->eliminar($id);

        return ['success' => true, 'mensaje' => 'Usuario eliminado exitosamente'];
    }

    /**
     * Activar/Desactivar usuario
     */
    public function toggleUsuario($id) {
        requireAdmin();

        $usuario = $this->usuario->obtenerPorId($id);
        
        if (!$usuario) {
            return ['success' => false, 'error' => 'Usuario no encontrado'];
        }

        $nuevoEstado = $usuario['activo'] ? 0 : 1;
        $this->usuario->actualizar($id, ['activo' => $nuevoEstado]);

        return [
            'success' => true, 
            'activo' => $nuevoEstado,
            'mensaje' => $nuevoEstado ? 'Usuario activado' : 'Usuario desactivado'
        ];
    }

    /**
     * Listar todos los inmuebles (admin)
     */
    public function listarInmuebles() {
        requireAdmin();

        $filtros = [
            'tipo' => sanitize($_GET['tipo'] ?? ''),
            'operacion' => sanitize($_GET['operacion'] ?? ''),
            'estado' => sanitize($_GET['estado'] ?? ''),
            'busqueda' => sanitize($_GET['q'] ?? ''),
            'limite' => (int)($_GET['limite'] ?? 20),
            'offset' => (int)($_GET['offset'] ?? 0)
        ];

        $filtros = array_filter($filtros, function($v) { return $v !== ''; });

        return [
            'success' => true,
            'inmuebles' => $this->inmueble->listar($filtros),
            'total' => $this->inmueble->contar($filtros)
        ];
    }

    /**
     * Destacar/Quitar destacado inmueble
     */
    public function toggleDestacado($id) {
        requireAdmin();

        $inmueble = $this->inmueble->obtenerPorId($id);
        
        if (!$inmueble) {
            return ['success' => false, 'error' => 'Inmueble no encontrado'];
        }

        $nuevoEstado = $inmueble['destacado'] ? 0 : 1;
        $this->inmueble->actualizar($id, ['destacado' => $nuevoEstado]);

        return [
            'success' => true,
            'destacado' => $nuevoEstado,
            'mensaje' => $nuevoEstado ? 'Inmueble destacado' : 'Destacado removido'
        ];
    }

    /**
     * Cambiar estado de inmueble
     */
    public function cambiarEstadoInmueble($id, $estado) {
        requireAdmin();

        $estadosPermitidos = [ESTADO_DISPONIBLE, ESTADO_VENDIDO, ESTADO_ARRENDADO, ESTADO_PAUSADO];
        
        if (!in_array($estado, $estadosPermitidos)) {
            return ['success' => false, 'error' => 'Estado no válido'];
        }

        $this->inmueble->actualizar($id, ['estado' => $estado]);

        return ['success' => true, 'mensaje' => 'Estado actualizado'];
    }

    /**
     * Listar solicitudes (admin)
     */
    public function listarSolicitudes() {
        requireAdmin();

        $filtros = [
            'estado' => sanitize($_GET['estado'] ?? ''),
            'limite' => (int)($_GET['limite'] ?? 50)
        ];

        return [
            'success' => true,
            'solicitudes' => $this->solicitud->listarTodas($filtros)
        ];
    }
}
?>
