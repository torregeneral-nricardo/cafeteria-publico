<?php
/**
 * Controlador para la gestión específica de Roles
 * Archivo   :   controllers/RolController.php
 */

require_once '../config/db.php';
require_once '../models/Usuario.php';

session_start();

// Solo SuperAdmin puede realizar estas acciones
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'SuperAdmin') {
    header('Location: ../views/dashboard.php?error=no_autorizado');
    exit();
}

$usuarioModel = new Usuario($pdo);
$action = $_GET['action'] ?? '';

if ($action === 'update') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $usuario_id = intval($_POST['usuario_id']);
        $nuevo_rol_id = intval($_POST['nuevo_rol_id']);

        // Evitar que el usuario se cambie el rol a sí mismo (para no perder acceso)
        if ($usuario_id === $_SESSION['user_id']) {
            header('Location: ../views/admin_roles.php?error=autocambio_prohibido');
            exit();
        }

        if ($usuarioModel->actualizarRol($usuario_id, $nuevo_rol_id)) {
            header('Location: ../views/admin_roles.php?msg=rol_actualizado');
        } else {
            header('Location: ../views/admin_roles.php?error=error_db');
        }
        exit();
    }
}