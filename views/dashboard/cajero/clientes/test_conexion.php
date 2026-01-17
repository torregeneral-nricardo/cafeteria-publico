<?php
// test_conexion.php - Solo para verificar la conexión
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ruta relativa a la carpeta actual
require_once '../../../../includes/conexion.php';

echo "<h2>Prueba de conexión a base de datos</h2>";

try {
    $pdo = obtenerConexion();
    
    if ($pdo) {
        echo "✅ Conexión establecida correctamente<br>";
        echo "Versión de MySQL: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br>";
        echo "Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";
        
        // Probar una consulta simple
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
        $result = $stmt->fetch();
        echo "Total de clientes en BD: " . $result['total'] . "<br>";
        
        // Probar la consulta de roles
        $stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Cajero'");
        $stmt->execute();
        $rol_color = $stmt->fetchColumn();
        echo "Color del rol Cajero: " . ($rol_color ? $rol_color : "No encontrado") . "<br>";
    } else {
        echo "❌ Error: No se pudo obtener la conexión<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error de PDO: " . $e->getMessage() . "<br>";
    echo "Código de error: " . $e->getCode() . "<br>";
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "<br>";
}
?>