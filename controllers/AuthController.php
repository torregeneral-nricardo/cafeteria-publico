<?php
/**
 * AuthController.php
 * Maneja el inicio y cierre de sesión del sistema.
 */

require_once '../config/db.php';
require_once '../models/Usuario.php';

session_start();

// Capturamos la acción desde la URL (ej: AuthController.php?action=login)
$action = $_GET['action'] ?? '';

$authController = new AuthController($pdo);

switch ($action) {
    case 'login':
        $authController->login();
        break;
    case 'logout':
        $authController->logout();
        break;
    default:
        header('Location: ../index.php');
        break;
}

class AuthController {
    private $usuarioModel;

    public function __construct($pdo) {
        // Inicializamos el modelo de Usuario
        $this->usuarioModel = new Usuario($pdo);
    }

    /**
     * Procesa el intento de inicio de sesión
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            if (!$email || !$password) {
                header('Location: ../index.php?error=vacio');
                exit();
            }

            // Buscamos al usuario en la base de datos a través del modelo
            // El método validarCredenciales debe devolver los datos del usuario o false
            $user = $this->usuarioModel->obtenerPorEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                // Login exitoso: Guardamos datos esenciales en la sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_rol'] = $user['nombre_rol']; // Ej: 'SuperAdmin', 'Editor', etc.
                $_SESSION['last_activity'] = time();

                // Redirigimos al panel principal
                header('Location: ../views/dashboard.php');
                exit();
            } else {
                // Login fallido: Redirigimos con error para activar la alerta visual en index.php
                header('Location: ../index.php?error=auth');
                exit();
            }
        }
    }

    /**
     * Cierra la sesión y limpia los datos
     */
    public function logout() {
        session_unset();
        session_destroy();
        header('Location: ../index.php?msg=logged_out');
        exit();
    }
}