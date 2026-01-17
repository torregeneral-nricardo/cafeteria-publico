<?php
/**
 * Helper para obtener conexión PDO compartida
 * Archivo: includes/conexion.php
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Obtener la conexión PDO existente
 * (config/db.php ya creó $pdo)
 */
function obtenerConexion() {
    global $pdo;
    
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        // Reintentar cargar config/db.php
        require_once __DIR__ . '/../config/db.php';
        
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new Exception("No se pudo obtener conexión a la base de datos");
        }
    }
    
    return $pdo;
}
?>