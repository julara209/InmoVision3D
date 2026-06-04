<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inmovision');

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Error al conectar con la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = [], $types = "") {
        $stmt = $this->connection->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("Error en la consulta: " . $this->connection->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
    }

    public function select($sql, $params = [], $types = "") {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function selectOne($sql, $params = [], $types = "") {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function insert($sql, $params = [], $types = "") {
        $this->query($sql, $params, $types);
        return $this->connection->insert_id;
    }

    public function update($sql, $params = [], $types = "") {
        $stmt = $this->query($sql, $params, $types);
        return $stmt->affected_rows;
    }

    public function delete($sql, $params = [], $types = "") {
        $stmt = $this->query($sql, $params, $types);
        return $stmt->affected_rows;
    }

    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }

    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>
