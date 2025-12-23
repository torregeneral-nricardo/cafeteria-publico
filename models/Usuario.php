<?php
/**
* Modelo Usuario
* Ubicación: /models/Usuario.php
*/

class Usuario { private $db;

public function __construct($pdo) {
    $this->db = $pdo;
}

/**
 * Obtiene todos los usuarios incluyendo el nombre de su rol
 * IMPORTANTE: Este método resuelve el error "Call to undefined method Usuario::obtenerTodos()"
 */
public function obtenerTodos() {
    try {
        // Hacemos un JOIN para obtener el nombre del rol desde la tabla roles
        $sql = "SELECT u.id, u.nombre, u.email, u.rol_id, u.fecha_creacion, r.nombre_rol 
                FROM usuarios u 
                INNER JOIN roles r ON u.rol_id = r.id 
                ORDER BY u.fecha_creacion DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en Usuario::obtenerTodos: " . $e->getMessage());
        return [];
    }
}

/**
 * Busca un usuario por su email incluyendo el nombre del rol
 */
public function obtenerPorEmail($email) {
    try {
        $sql = "SELECT u.*, r.nombre_rol 
                FROM usuarios u 
                JOIN roles r ON u.rol_id = r.id 
                WHERE u.email = :email 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en Usuario::obtenerPorEmail: " . $e->getMessage());
        return false;
    }
}

/**
 * Crea un nuevo usuario
 */
public function crear($nombre, $email, $password, $rol_id) {
    try {
        $sql = "INSERT INTO usuarios (nombre, email, password, rol_id) VALUES (:nombre, :email, :password, :rol_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'nombre'   => $nombre,
            'email'    => $email,
            'password' => $password,
            'rol_id'   => $rol_id
        ]);
    } catch (PDOException $e) {
        error_log("Error en Usuario::crear: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza el rol de un usuario específico
 */
public function actualizarRol($usuario_id, $nuevo_rol_id) {
    try {
        $sql = "UPDATE usuarios SET rol_id = :rol_id WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'rol_id' => $nuevo_rol_id,
            'id' => $usuario_id
        ]);
    } catch (PDOException $e) {
        error_log("Error en Usuario::actualizarRol: " . $e->getMessage());
        return false;
    }
}
}