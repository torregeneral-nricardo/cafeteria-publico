<?php
/**
 * Modelo Usuario
 * Archivo   :   models/Usuario.php
 */

class Usuario {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    /**
     * Obtiene todos los usuarios incluyendo el nombre de su rol
     */
    public function obtenerTodos() {
        try {
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
     * Obtiene un usuario por su token de recuperación
     */
    public function obtenerPorTokenRecuperacion($token) {
        try {
            $sql = "SELECT u.*, r.nombre_rol 
                    FROM usuarios u 
                    JOIN roles r ON u.rol_id = r.id 
                    WHERE u.token_recuperacion = :token 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['token' => $token]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Usuario::obtenerPorTokenRecuperacion: " . $e->getMessage());
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
     * Actualiza el token de recuperación de un usuario
     */
    public function actualizarTokenRecuperacion($email, $token) {
        try {
            $sql = "UPDATE usuarios SET token_recuperacion = :token WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'token' => $token,
                'email' => $email
            ]);
        } catch (PDOException $e) {
            error_log("Error en Usuario::actualizarTokenRecuperacion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la contraseña de un usuario por email
     */
    public function actualizarPassword($email, $password) {
        try {
            $sql = "UPDATE usuarios SET password = :password, token_recuperacion = NULL WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'password' => $password,
                'email' => $email
            ]);
        } catch (PDOException $e) {
            error_log("Error en Usuario::actualizarPassword: " . $e->getMessage());
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

    /**
     * Actualiza el último login de un usuario
     */
    public function actualizarUltimoLogin($usuario_id) {
        try {
            $sql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $usuario_id]);
        } catch (PDOException $e) {
            error_log("Error en Usuario::actualizarUltimoLogin: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un email ya está registrado
     */
    public function emailExiste($email) {
        try {
            $sql = "SELECT COUNT(*) as count FROM usuarios WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error en Usuario::emailExiste: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina el token de recuperación de un usuario
     */
    public function limpiarTokenRecuperacion($email) {
        try {
            $sql = "UPDATE usuarios SET token_recuperacion = NULL WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['email' => $email]);
        } catch (PDOException $e) {
            error_log("Error en Usuario::limpiarTokenRecuperacion: " . $e->getMessage());
            return false;
        }
    }
}