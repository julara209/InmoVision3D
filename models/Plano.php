<?php
/**
 * Modelo de Plano 2D
 * InmoVision 3D
 */

require_once __DIR__ . '/../config/config.php';

class Plano {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Crear nuevo plano
     */
    public function crear($datos) {
        $sql = "INSERT INTO planos_2d (inmueble_id, nombre, archivo, tipo, datos_json, ancho, alto) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->insert($sql, [
            $datos['inmueble_id'],
            $datos['nombre'],
            $datos['archivo'] ?? null,
            $datos['tipo'] ?? 'imagen',
            $datos['datos_json'] ?? null,
            $datos['ancho'] ?? 800,
            $datos['alto'] ?? 600
        ], "issssii");
    }

    /**
     * Obtener plano por ID
     */
    public function obtenerPorId($id) {
        $sql = "SELECT p.*, i.titulo as inmueble_titulo 
                FROM planos_2d p 
                JOIN inmuebles i ON p.inmueble_id = i.id 
                WHERE p.id = ?";
        $plano = $this->db->selectOne($sql, [$id], "i");
        
        if ($plano) {
            $plano['objetos'] = $this->obtenerObjetos($id);
        }
        
        return $plano;
    }

    /**
     * Obtener objetos de un plano
     */
    public function obtenerObjetos($planoId) {
        $sql = "SELECT * FROM objetos_plano WHERE plano_id = ? ORDER BY id ASC";
        return $this->db->select($sql, [$planoId], "i");
    }

    /**
     * Actualizar plano
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
        if (isset($datos['archivo'])) {
            $campos[] = "archivo = ?";
            $valores[] = $datos['archivo'];
            $tipos .= "s";
        }
        if (isset($datos['tipo'])) {
            $campos[] = "tipo = ?";
            $valores[] = $datos['tipo'];
            $tipos .= "s";
        }
        if (isset($datos['datos_json'])) {
            $campos[] = "datos_json = ?";
            $valores[] = $datos['datos_json'];
            $tipos .= "s";
        }
        if (isset($datos['ancho'])) {
            $campos[] = "ancho = ?";
            $valores[] = $datos['ancho'];
            $tipos .= "i";
        }
        if (isset($datos['alto'])) {
            $campos[] = "alto = ?";
            $valores[] = $datos['alto'];
            $tipos .= "i";
        }

        if (empty($campos)) {
            return false;
        }

        $valores[] = $id;
        $tipos .= "i";

        $sql = "UPDATE planos_2d SET " . implode(", ", $campos) . " WHERE id = ?";
        return $this->db->update($sql, $valores, $tipos);
    }

    /**
     * Guardar objetos del plano
     */
    public function guardarObjetos($planoId, $objetos) {
        // Eliminar objetos existentes
        $sql = "DELETE FROM objetos_plano WHERE plano_id = ?";
        $this->db->delete($sql, [$planoId], "i");

        // Insertar nuevos objetos
        foreach ($objetos as $objeto) {
            $sql = "INSERT INTO objetos_plano (plano_id, tipo, nombre, posicion_x, posicion_y, ancho, alto, rotacion, color, propiedades_json) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->insert($sql, [
                $planoId,
                $objeto['tipo'],
                $objeto['nombre'] ?? '',
                $objeto['posicion_x'],
                $objeto['posicion_y'],
                $objeto['ancho'] ?? 50,
                $objeto['alto'] ?? 50,
                $objeto['rotacion'] ?? 0,
                $objeto['color'] ?? '#333333',
                json_encode($objeto['propiedades'] ?? [])
            ], "issddddss");
        }

        return true;
    }

    /**
     * Agregar objeto al plano
     */
    public function agregarObjeto($planoId, $objeto) {
        $sql = "INSERT INTO objetos_plano (plano_id, tipo, nombre, posicion_x, posicion_y, ancho, alto, rotacion, color, propiedades_json) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->db->insert($sql, [
            $planoId,
            $objeto['tipo'],
            $objeto['nombre'] ?? '',
            $objeto['posicion_x'],
            $objeto['posicion_y'],
            $objeto['ancho'] ?? 50,
            $objeto['alto'] ?? 50,
            $objeto['rotacion'] ?? 0,
            $objeto['color'] ?? '#333333',
            json_encode($objeto['propiedades'] ?? [])
        ], "issddddss");
    }

    /**
     * Actualizar objeto
     */
    public function actualizarObjeto($objetoId, $datos) {
        $campos = [];
        $valores = [];
        $tipos = "";

        $camposPermitidos = ['posicion_x', 'posicion_y', 'ancho', 'alto', 'rotacion', 'color', 'nombre'];
        
        foreach ($camposPermitidos as $campo) {
            if (isset($datos[$campo])) {
                $campos[] = "$campo = ?";
                $valores[] = $datos[$campo];
                $tipos .= in_array($campo, ['posicion_x', 'posicion_y', 'ancho', 'alto', 'rotacion']) ? 'd' : 's';
            }
        }

        if (isset($datos['propiedades'])) {
            $campos[] = "propiedades_json = ?";
            $valores[] = json_encode($datos['propiedades']);
            $tipos .= "s";
        }

        if (empty($campos)) {
            return false;
        }

        $valores[] = $objetoId;
        $tipos .= "i";

        $sql = "UPDATE objetos_plano SET " . implode(", ", $campos) . " WHERE id = ?";
        return $this->db->update($sql, $valores, $tipos);
    }

    /**
     * Eliminar objeto
     */
    public function eliminarObjeto($objetoId) {
        $sql = "DELETE FROM objetos_plano WHERE id = ?";
        return $this->db->delete($sql, [$objetoId], "i");
    }

    /**
     * Eliminar plano
     */
    public function eliminar($id) {
        $sql = "DELETE FROM planos_2d WHERE id = ?";
        return $this->db->delete($sql, [$id], "i");
    }

    /**
     * Obtener planos de un inmueble
     */
    public function obtenerPorInmueble($inmuebleId) {
        $sql = "SELECT * FROM planos_2d WHERE inmueble_id = ? ORDER BY fecha_creacion DESC";
        return $this->db->select($sql, [$inmuebleId], "i");
    }

    /**
     * Generar configuración 3D a partir del plano
     */
    public function generarConfiguracion3D($planoId) {
        $plano = $this->obtenerPorId($planoId);
        
        if (!$plano) {
            return null;
        }

        $config = [
            'plano_id' => $planoId,
            'ancho' => $plano['ancho'],
            'alto' => $plano['alto'],
            'paredes' => [],
            'objetos' => [],
            'camara' => [
                'posicion' => ['x' => 0, 'y' => 10, 'z' => 15],
                'objetivo' => ['x' => 0, 'y' => 0, 'z' => 0]
            ]
        ];

        // Convertir objetos 2D a 3D
        foreach ($plano['objetos'] as $objeto) {
            $objeto3D = [
                'tipo' => $objeto['tipo'],
                'nombre' => $objeto['nombre'],
                'posicion' => [
                    'x' => ($objeto['posicion_x'] - $plano['ancho'] / 2) / 50,
                    'y' => 0,
                    'z' => ($objeto['posicion_y'] - $plano['alto'] / 2) / 50
                ],
                'dimensiones' => [
                    'ancho' => $objeto['ancho'] / 50,
                    'alto' => $this->getAltura3D($objeto['tipo']),
                    'profundidad' => $objeto['alto'] / 50
                ],
                'rotacion' => $objeto['rotacion'],
                'color' => $objeto['color']
            ];

            if ($objeto['tipo'] === 'pared') {
                $config['paredes'][] = $objeto3D;
            } else {
                $config['objetos'][] = $objeto3D;
            }
        }

        return $config;
    }

    /**
     * Obtener altura 3D según tipo de objeto
     */
    private function getAltura3D($tipo) {
        $alturas = [
            'pared' => 2.5,
            'puerta' => 2.0,
            'ventana' => 1.2,
            'cama' => 0.5,
            'sofa' => 0.8,
            'mesa' => 0.75,
            'silla' => 0.9,
            'escritorio' => 0.75,
            'armario' => 2.0,
            'cocina' => 0.9,
            'bano' => 0.5,
            'lavabo' => 0.85,
            'ducha' => 2.0,
            'inodoro' => 0.4
        ];

        return $alturas[$tipo] ?? 1.0;
    }
}
?>
