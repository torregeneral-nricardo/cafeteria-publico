<?php
/**
 * Maneja la gestión de usuarios (CRUD) por parte del SuperAdmin.
 * Archivo   :   controllers/UsuarioController.php
 */

require_once '../config/db.php';
require_once '../models/Usuario.php';

session_start();

// Seguridad: Verificar que el usuario esté logueado y sea SuperAdmin
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'SuperAdmin') {
    header('Location: ../index.php?error=acceso_denegado');
    exit();
}

$action = $_GET['action'] ?? '';
$usuarioController = new UsuarioController($pdo);

switch ($action) {
    case 'store':
        $usuarioController->store();
        break;
    default:
        header('Location: ../views/dashboard.php');
        break;
}

class UsuarioController {
    private $usuarioModel;

    public function __construct($pdo) {
        $this->usuarioModel = new Usuario($pdo);
    }

    /**
     * Guarda un nuevo usuario en la base de datos
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitización de entradas
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $rol_id = filter_input(INPUT_POST, 'rol_id', FILTER_SANITIZE_NUMBER_INT);

            // Validación básica
            if (!$nombre || !$email || !$password || !$rol_id) {
                header('Location: ../views/registro.php?error=campos_vacios');
                exit();
            }

            // Encriptación de contraseña
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Llamada al modelo para insertar
            $exito = $this->usuarioModel->crear($nombre, $email, $hashedPassword, $rol_id);

            if ($exito) {
                // Redirigir con éxito
                header('Location: ../views/dashboard.php?msg=usuario_creado');
            } else {
                // Redirigir con error
                header('Location: ../views/registro.php?error=error_guardado');
            }
            exit();
        }
    }
}