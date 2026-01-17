<?php
/**
 * Procesa el registro de correos desde la Landing Page.
 * Basado en la estructura de archivos de la Torre General.
 * Archivo   :   controllers/SubController.php
 */
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiamos el email recibido
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            // Verificar si el correo ya existe en la tabla 'suscripciones'
            // (Nombre de tabla verificado según tu plan de base de datos)
            $check = $pdo->prepare("SELECT id FROM suscripciones WHERE email = ?");
            $check->execute([$email]);
            
            if ($check->rowCount() > 0) {
                // Redirigir con estado de "ya existe"
                header("Location: ../index.php?status=existe");
            } else {
                // Insertar el nuevo interesado
                $stmt = $pdo->prepare("INSERT INTO suscripciones (email) VALUES (?)");
                $stmt->execute([$email]);
                
                // Redirigir con éxito
                header("Location: ../index.php?status=success");
            }
        } catch (PDOException $e) {
            // Error de base de datos
            header("Location: ../index.php?status=error");
        }
    } else {
        // Email no válido
        header("Location: ../index.php?status=invalido");
    }
    exit();
}