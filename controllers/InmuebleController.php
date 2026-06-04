<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Inmueble.php';
require_once __DIR__ . '/../models/Favorito.php';

class InmuebleController {

    private $inmueble;
    private $favorito;

    public function __construct() {
        $this->inmueble = new Inmueble();
        $this->favorito = new Favorito();
    }

    /**
     * Listar inmuebles.
     */
    public function listar() {

        $filtros = [
            'tipo' => sanitize($_GET['tipo'] ?? ''),
            'ubicacion' => sanitize($_GET['ubicacion'] ?? ''),
            'precio_min' => (float)($_GET['precio_min'] ?? 0),
            'precio_max' => (float)($_GET['precio_max'] ?? 0),
            'habitaciones' => (int)($_GET['habitaciones'] ?? 0),
            'busqueda' => sanitize($_GET['q'] ?? ''),
            'estado' => ESTADO_DISPONIBLE
        ];

        $filtros = array_filter($filtros);
        $filtros['estado'] = ESTADO_DISPONIBLE;

        $inmuebles = $this->inmueble->listar($filtros);
        $total = $this->inmueble->contar($filtros);

        if (isLoggedIn()) {

            $favoritosIds = $this->favorito->obtenerIds($_SESSION['usuario_id']);

            foreach ($inmuebles as &$inm) {
                $inm['es_favorito'] = in_array(
                    $inm['idInmueble'],
                    $favoritosIds
                );
            }
        }

        return [
            'success' => true,
            'inmuebles' => $inmuebles,
            'total' => $total
        ];
    }

    /**
     * Detalle de inmueble.
     */
    public function detalle($id) {

        $inmueble = $this->inmueble->obtenerPorId($id);

        if (!$inmueble) {
            return [
                'success' => false,
                'error' => 'Inmueble no encontrado'
            ];
        }

        if (isLoggedIn()) {

            $inmueble['es_favorito'] =
                $this->favorito->existe(
                    $_SESSION['usuario_id'],
                    $id
                );
        }

        $inmueble['total_favoritos'] =
            $this->favorito->contarPorInmueble($id);

        return [
            'success' => true,
            'inmueble' => $inmueble
        ];
    }

    /**
     * Crear inmueble.
     */
    public function crear() {

        requirePublicador();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [
                'success' => false,
                'error' => 'Método no permitido'
            ];
        }

        $datos = [
            'titulo' => sanitize($_POST['titulo'] ?? ''),
            'descripcion' => sanitize($_POST['descripcion'] ?? ''),
            'precio' => (float)($_POST['precio'] ?? 0),
            'ubicacion' => sanitize($_POST['ubicacion'] ?? ''),
            'tipo' => sanitize($_POST['tipo'] ?? ''),
            'habitaciones' => (int)($_POST['habitaciones'] ?? 0),
            'banos' => (int)($_POST['banos'] ?? 0),
            'area' => (float)($_POST['area'] ?? 0),
            'estado' => ESTADO_DISPONIBLE,
            'idPublicador' => $_SESSION['usuario_id']
        ];

        if (
            empty($datos['titulo']) ||
            empty($datos['descripcion']) ||
            $datos['precio'] <= 0 ||
            empty($datos['ubicacion']) ||
            empty($datos['tipo']) ||
            $datos['area'] <= 0
        ) {
            return [
                'success' => false,
                'error' => 'Complete los campos obligatorios'
            ];
        }

        $id = $this->inmueble->crear($datos);

        if (!$id) {
            return [
                'success' => false,
                'error' => 'Error al crear inmueble'
            ];
        }

        if (!empty($_FILES['imagenes']['name'][0])) {
            $this->procesarImagenes($id, $_FILES['imagenes']);
        }

        return [
            'success' => true,
            'id' => $id,
            'mensaje' => 'Inmueble creado correctamente'
        ];
    }

    /**
     * Actualizar inmueble.
     */
    public function actualizar($id) {

        requirePublicador();

        $inmueble = $this->inmueble->obtenerPorId($id);

        if (!$inmueble) {
            return [
                'success' => false,
                'error' => 'Inmueble no encontrado'
            ];
        }

        if (
            !isAdmin() &&
            $inmueble['idPublicador'] != $_SESSION['usuario_id']
        ) {
            return [
                'success' => false,
                'error' => 'No tiene permisos'
            ];
        }

        $datos = [];

        $campos = [
            'titulo',
            'descripcion',
            'precio',
            'ubicacion',
            'tipo',
            'habitaciones',
            'banos',
            'area',
            'estado'
        ];

        foreach ($campos as $campo) {

            if (isset($_POST[$campo])) {

                if (in_array($campo, ['precio', 'area'])) {
                    $datos[$campo] = (float)$_POST[$campo];
                } elseif (in_array($campo, ['habitaciones', 'banos'])) {
                    $datos[$campo] = (int)$_POST[$campo];
                } else {
                    $datos[$campo] = sanitize($_POST[$campo]);
                }
            }
        }

        $this->inmueble->actualizar($id, $datos);

        if (!empty($_FILES['imagenes']['name'][0])) {
            $this->procesarImagenes($id, $_FILES['imagenes']);
        }

        return [
            'success' => true,
            'mensaje' => 'Inmueble actualizado correctamente'
        ];
    }

    /**
     * Eliminar inmueble.
     */
    public function eliminar($id) {

        requirePublicador();

        $inmueble = $this->inmueble->obtenerPorId($id);

        if (!$inmueble) {
            return [
                'success' => false,
                'error' => 'Inmueble no encontrado'
            ];
        }

        if (
            !isAdmin() &&
            $inmueble['idPublicador'] != $_SESSION['usuario_id']
        ) {
            return [
                'success' => false,
                'error' => 'No tiene permisos'
            ];
        }

        $this->inmueble->eliminar($id);

        return [
            'success' => true,
            'mensaje' => 'Inmueble eliminado correctamente'
        ];
    }

    /**
     * Procesar imágenes.
     */
    private function procesarImagenes($inmuebleId, $files) {

        $imagenes = $this->reArrayFiles($files);

        foreach ($imagenes as $imagen) {

            $resultado = uploadFile(
                $imagen,
                'inmuebles'
            );

            if ($resultado['success']) {

                $this->inmueble->agregarImagen(
                    $inmuebleId,
                    $resultado['path']
                );
            }
        }
    }

    /**
     * Reorganizar archivos.
     */
    private function reArrayFiles($files) {

        $fileArray = [];
        $fileCount = count($files['name']);
        $fileKeys = array_keys($files);

        for ($i = 0; $i < $fileCount; $i++) {

            foreach ($fileKeys as $key) {

                $fileArray[$i][$key] =
                    $files[$key][$i];
            }
        }

        return $fileArray;
    }

    /**
     * Eliminar imagen.
     */
    public function eliminarImagen($imagenId) {

        requirePublicador();

        $this->inmueble->eliminarImagen($imagenId);

        return [
            'success' => true
        ];
    }

    /**
     * Mis inmuebles.
     */
    public function misInmuebles() {

        requirePublicador();

        $inmuebles =
            $this->inmueble->obtenerPorUsuario(
                $_SESSION['usuario_id']
            );

        return [
            'success' => true,
            'inmuebles' => $inmuebles
        ];
    }
}
?>
