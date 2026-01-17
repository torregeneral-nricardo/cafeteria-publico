<?php
/**
 * Configuración de conexión a la Base de Datos MySQL
 * Ubicación: /config/db.php
 */
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');	

	try {
		// Usamos PDO para mayor seguridad contra inyecciones SQL
		$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
		$options = [
			PDO::ATTR_ERRMODE			=> PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];
		
		$pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
		
	} catch (PDOException $e) {
		// En producción, no mostrar el mensaje detallado del error
		die("Error de conexión: " . $e->getMessage());
}
?>