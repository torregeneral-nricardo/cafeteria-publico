<?php
/**
 * Modelo Rol
 * Archivo   :   models/Rol.php
 */

class Rol {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    /**
     * Obtiene todos los roles disponibles en el sistema
     */
    public function listarTodo() {
        try {
            // Verificamos que la conexión exista
            if (!$this->db) {
                return [];
            }

            $sql = "SELECT * FROM roles ORDER BY id ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Rol::listarTodo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene la información de un rol específico por su ID
     */
    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM roles WHERE id = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Rol::obtenerPorId: " . $e->getMessage());
            return false;
        }
    }
}