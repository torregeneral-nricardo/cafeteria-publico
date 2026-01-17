<?php
/**
 * Modelo para gestión de permisos y módulos
 * Archivo   :   models/Permisos.php
 */

class Permisos {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Obtener permisos de un usuario por ID
     */
    public function obtenerPermisosPorUsuario($usuario_id) {
        try {
            $sql = "SELECT p.codigo, p.nombre, p.modulo, p.categoria, p.nivel_seguridad
                    FROM usuarios u
                    JOIN roles r ON u.rol_id = r.id
                    JOIN rol_permisos rp ON r.id = rp.rol_id
                    JOIN permisos p ON rp.permiso_id = p.id
                    WHERE u.id = ?
                    ORDER BY p.modulo, p.categoria";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuario_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Permisos::obtenerPermisosPorUsuario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener módulos accesibles para un rol
     */
    public function obtenerModulosPorRol($rol_id) {
        try {
            $sql = "SELECT m.id, m.nombre, m.codigo, m.descripcion, m.icono, m.ruta, m.orden, m.color_hex
                    FROM rol_modulos rm
                    JOIN modulos_sistema m ON rm.modulo_id = m.id
                    WHERE rm.rol_id = ? AND m.visible = TRUE
                    ORDER BY m.orden ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$rol_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Permisos::obtenerModulosPorRol: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un usuario tiene un permiso específico
     */
    public function usuarioTienePermiso($usuario_id, $codigo_permiso) {
        try {
            $sql = "SELECT COUNT(*) as count
                    FROM usuarios u
                    JOIN roles r ON u.rol_id = r.id
                    JOIN rol_permisos rp ON r.id = rp.rol_id
                    JOIN permisos p ON rp.permiso_id = p.id
                    WHERE u.id = ? AND p.codigo = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuario_id, $codigo_permiso]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error en Permisos::usuarioTienePermiso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un usuario tiene acceso a un módulo
     */
    public function usuarioTieneAccesoModulo($usuario_id, $codigo_modulo) {
        try {
            $sql = "SELECT COUNT(*) as count
                    FROM usuarios u
                    JOIN roles r ON u.rol_id = r.id
                    JOIN rol_modulos rm ON r.id = rm.rol_id
                    JOIN modulos_sistema m ON rm.modulo_id = m.id
                    WHERE u.id = ? AND m.codigo = ? AND m.visible = TRUE";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuario_id, $codigo_modulo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error en Permisos::usuarioTieneAccesoModulo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener todos los permisos del sistema
     */
    public function obtenerTodosPermisos() {
        try {
            $sql = "SELECT p.*, COUNT(rp.rol_id) as total_roles
                    FROM permisos p
                    LEFT JOIN rol_permisos rp ON p.id = rp.permiso_id
                    GROUP BY p.id
                    ORDER BY p.modulo, p.categoria, p.nombre";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Permisos::obtenerTodosPermisos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los módulos del sistema
     */
    public function obtenerTodosModulos() {
        try {
            $sql = "SELECT m.*, COUNT(rm.rol_id) as total_roles
                    FROM modulos_sistema m
                    LEFT JOIN rol_modulos rm ON m.id = rm.modulo_id
                    GROUP BY m.id
                    ORDER BY m.orden";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Permisos::obtenerTodosModulos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Asignar permisos a un rol
     */
    public function asignarPermisosARol($rol_id, $permisos_ids) {
        try {
            // Eliminar permisos actuales
            $sql = "DELETE FROM rol_permisos WHERE rol_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$rol_id]);

            // Asignar nuevos permisos
            $sql = "INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($permisos_ids as $permiso_id) {
                $stmt->execute([$rol_id, $permiso_id]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error en Permisos::asignarPermisosARol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Asignar módulos a un rol
     */
    public function asignarModulosARol($rol_id, $modulos_ids) {
        try {
            // Eliminar módulos actuales
            $sql = "DELETE FROM rol_modulos WHERE rol_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$rol_id]);

            // Asignar nuevos módulos
            $sql = "INSERT INTO rol_modulos (rol_id, modulo_id) VALUES (?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($modulos_ids as $modulo_id) {
                $stmt->execute([$rol_id, $modulo_id]);
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Error en Permisos::asignarModulosARol: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de permisos
     */
    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        (SELECT COUNT(*) FROM permisos) as total_permisos,
                        (SELECT COUNT(*) FROM modulos_sistema) as total_modulos,
                        (SELECT COUNT(*) FROM roles) as total_roles,
                        (SELECT COUNT(*) FROM usuarios) as total_usuarios";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Permisos::obtenerEstadisticas: " . $e->getMessage());
            return [];
        }
    }
}