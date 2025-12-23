-- 1. PRIMERO: Asegurarnos de que la columna 'password' tenga el tamaño correcto.
-- Si es muy corta (ej. VARCHAR(50)), el hash se trunca y PHP nunca podrá validarlo.
ALTER TABLE usuarios MODIFY COLUMN password VARCHAR(255) NOT NULL;

-- 2. SEGUNDO: Actualizar con el hash estándar para 'admin123'.
-- Nota: Asegúrate de que no haya espacios en blanco accidentales antes o después del hash.
UPDATE usuarios 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'admin@sistema.com';

-- 3. TERCERO: Verificación de consistencia.
-- Ejecuta esta consulta para confirmar que el usuario existe con ese correo exacto.
SELECT id, email, rol_id FROM usuarios WHERE email = 'admin@sistema.com';

/* PASO DEFINITIVO DE RESCATE (Copia este código en un archivo llamado 'fix.php'):
   Este script actualizará la base de datos usando el mismo motor de PHP que el login,
   eliminando cualquier problema de codificación o truncamiento manual.

   <?php
   require_once 'config/db.php';
   
   $email = 'admin@sistema.com';
   $password = 'admin123';
   $hash = password_hash($password, PASSWORD_BCRYPT);
   
   try {
       // Forzamos la limpieza del email y la actualización del hash
       $stmt = $pdo->prepare("UPDATE usuarios SET password = ?, email = ? WHERE email = ? OR id = 1");
       $stmt->execute([$hash, $email, $email]);
       
       echo "<h3>Resultado del Rescate:</h3>";
       echo "Nuevo Hash generado: " . $hash . "<br>";
       echo "Filas afectadas: " . $stmt->rowCount() . "<br>";
       echo "<b>Intenta loguearte ahora con admin@sistema.com y admin123</b>";
   } catch (Exception $e) {
       echo "Error: " . $e->getMessage();
   }
   ?>
*/