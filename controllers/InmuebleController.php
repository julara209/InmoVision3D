<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Inmueble.php';
// Favorito puede no existir aún — cargamos solo si existe
if (file_exists(__DIR__ . '/../models/Favorito.php')) {
    require_once __DIR__ . '/../models/Favorito.php';
}

class InmuebleController {

    private $inmueble;
    private $favorito;

    public function __construct() {
        $this->inmueble = new Inmueble();
        $this->favorito = class_exists('Favorito') ? new Favorito() : null;
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

        if (isLoggedIn() && $this->favorito) {

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

        if (isLoggedIn() && $this->favorito) {

            $inmueble['es_favorito'] =
                $this->favorito->existe(
                    $_SESSION['usuario_id'],
                    $id
                );
        }

        $inmueble['total_favoritos'] =
            $this->favorito
                ? $this->favorito->contarPorInmueble($id)
                : 0;

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

        // PHP no parsea multipart en PUT automáticamente.
        // Si el método es PUT, parseamos el stream manualmente.
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $this->parsePutMultipart();
        }

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

        // Eliminar imágenes marcadas desde el formulario de edición
        if (!empty($_POST['eliminar_imagen']) && is_array($_POST['eliminar_imagen'])) {
            foreach ($_POST['eliminar_imagen'] as $idImagen) {
                $this->inmueble->eliminarImagen((int)$idImagen);
            }
        }

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
    /**
     * Parsea multipart/form-data para peticiones PUT.
     * PHP solo parsea $_POST y $_FILES en POST; en PUT hay que hacerlo manual.
     */
    private function parsePutMultipart(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'multipart/form-data') === false) {
            // JSON o urlencoded
            $body = file_get_contents('php://input');
            if ($body) {
                parse_str($body, $parsed);
                foreach ($parsed as $k => $v) {
                    $_POST[$k] = $v;
                }
            }
            return;
        }

        // Extraer boundary
        preg_match('/boundary=(.+)$/', $contentType, $m);
        if (empty($m[1])) return;
        $boundary = '--' . trim($m[1]);

        $raw   = file_get_contents('php://input');
        $parts = array_slice(explode($boundary, $raw), 1);

        foreach ($parts as $part) {
            if (trim($part) === '--') continue;

            [$headerBlock, $body] = explode("\r\n\r\n", $part, 2);
            $body = rtrim($body, "\r\n");

            $headers = [];
            foreach (explode("\r\n", $headerBlock) as $line) {
                if (strpos($line, ':') !== false) {
                    [$hk, $hv] = explode(':', $line, 2);
                    $headers[strtolower(trim($hk))] = trim($hv);
                }
            }

            $disp = $headers['content-disposition'] ?? '';
            preg_match('/name="([^"]+)"/', $disp, $nameMatch);
            $name = $nameMatch[1] ?? '';

            if (preg_match('/filename="([^"]+)"/', $disp, $fnMatch)) {
                // Archivo
                $filename = $fnMatch[1];
                $tmp = tempnam(sys_get_temp_dir(), 'put_upload_');
                file_put_contents($tmp, $body);
                $type = $headers['content-type'] ?? 'application/octet-stream';

                // Soporte para arrays: imagenes[]
                $cleanName = rtrim($name, '[]');
                if (substr($name, -2) === '[]') {
                    $_FILES[$cleanName]['name'][]     = $filename;
                    $_FILES[$cleanName]['tmp_name'][] = $tmp;
                    $_FILES[$cleanName]['type'][]     = $type;
                    $_FILES[$cleanName]['size'][]     = strlen($body);
                    $_FILES[$cleanName]['error'][]    = UPLOAD_ERR_OK;
                } else {
                    $_FILES[$name] = [
                        'name'     => $filename,
                        'tmp_name' => $tmp,
                        'type'     => $type,
                        'size'     => strlen($body),
                        'error'    => UPLOAD_ERR_OK,
                    ];
                }
            } else {
                // Campo de texto — soporta arrays (eliminar_imagen[])
                if (substr($name, -2) === '[]') {
                    $cleanName = rtrim($name, '[]');
                    $_POST[$cleanName][] = $body;
                } else {
                    $_POST[$name] = $body;
                }
            }
        }
    }
}
?>
