<?php
/**
 * Controlador de autenticación con redirección por roles
 * Archivo   :   controllers/AuthController.php
 */

require_once '../config/db.php';
require_once '../models/Usuario.php';
require_once '../models/Permisos.php';

require_once '../includes/seguridad.php';
iniciarSesionSegura();

// Capturar acción desde la URL
$action = $_GET['action'] ?? '';

$authController = new AuthController($pdo);

switch ($action) {
    case 'login':
        $authController->login();
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'registroPublico':
        $authController->registroPublico();
        break;
    case 'solicitarRecuperacion':
        $authController->solicitarRecuperacion();
        break;
    case 'restablecerPassword':
        $authController->restablecerPassword();
        break;
    case 'checkEmail':
        $authController->checkEmail();
        break;
    case 'confirmarUsuario':
        $authController->confirmarUsuario();
        break;
    default:
        header('Location: ../index.php');
        break;
}

class AuthController {
    private $usuarioModel;
    private $permisosModel;
    private $pdo;

    public function __construct($pdo) {
        $this->usuarioModel = new Usuario($pdo);
        $this->permisosModel = new Permisos($pdo);
        $this->pdo = $pdo;
    }

    /**
     * Procesa el inicio de sesión con redirección por rol
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                header('Location: ../index.php?error=csrf');
                exit();
            }

            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            if (!$email || !$password) {
                header('Location: ../index.php?error=vacio');
                exit();
            }

            // Buscar usuario
            $user = $this->usuarioModel->obtenerPorEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                // Verificar estado del usuario
                if ($user['estado'] === 'inactivo') {
                    header('Location: ../index.php?error=inactivo');
                    exit();
                }
                
                if ($user['estado'] === 'suspendido') {
                    header('Location: ../index.php?error=suspendido');
                    exit();
                }

                // Generar token de sesión único
                $session_token = bin2hex(random_bytes(32));

                // Actualizar token de sesión en la base de datos
                try {
                    $stmt = $this->pdo->prepare("UPDATE usuarios SET session_token = ?, ultimo_login = NOW(), ultima_actividad = NOW() WHERE id = ?");
                    $stmt->execute([$session_token, $user['id']]);
                } catch (Exception $e) {
                    error_log("Error al actualizar session_token: " . $e->getMessage());
                }

                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nombre'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_rol'] = $user['nombre_rol'];
                $_SESSION['user_rol_id'] = $user['rol_id'];
                $_SESSION['user_foto'] = $user['foto_perfil'];
                $_SESSION['last_activity'] = time();
                $_SESSION['session_token'] = $session_token; // CRÍTICO: Guardar token en sesión
                
                // Obtener permisos del usuario
                $_SESSION['user_permisos'] = $this->permisosModel->obtenerPermisosPorUsuario($user['id']);
                
                // Obtener módulos accesibles
                $_SESSION['user_modulos'] = $this->permisosModel->obtenerModulosPorRol($user['rol_id']);

                // Actualizar último login
                $this->usuarioModel->actualizarUltimoLogin($user['id']);

                // Registrar actividad
                $this->registrarActividad($user['id'], 'Inicio de sesión', 'Autenticación', 'Usuario inició sesión en el sistema desde IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'Desconocida'));

                // Redirección inteligente según rol
                $this->redirigirPorRol($user['nombre_rol']);
                exit();
                
            } else {
                // Login fallido
                header('Location: ../index.php?error=auth');
                exit();
            }
        }
    }

    /**
     * Redirige al usuario según su rol
     */
    private function redirigirPorRol($rol) {
        switch ($rol) {
            case 'Administrador del Sistema':
                header('Location: ../views/dashboard/admin/');
                break;
            case 'Cajero':
                header('Location: ../views/dashboard/cajero/');
                break;
            case 'Gerente':
                header('Location: ../views/dashboard/gerente/');
                break;
            case 'Inquilino':
                header('Location: ../views/dashboard/inquilino/');
                break;
            case 'Visitante-No-Confirmado':
                header('Location: ../views/dashboard/visitante/?estado=pendiente');
                break;
            case 'Visitante-Confirmado-Admin':
            case 'Visitante-Confirmado-Propietario':
                header('Location: ../views/dashboard/visitante/');
                break;
            case 'Personal de Obras':
                header('Location: ../views/dashboard/personal-obras/');
                break;
            case 'Seguridad':
                header('Location: ../views/dashboard/seguridad/');
                break;
            default:
                header('Location: ../views/dashboard/');
                break;
        }
        exit();
    }

    /**
     * Registro público de usuarios (automáticamente como Visitante-No-Confirmado)
     */
    public function registroPublico() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                header('Location: ../views/registro_publico.php?error=csrf');
                exit();
            }

            $nombre = trim($_POST['nombre'] ?? '');
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $telefono = $_POST['telefono'] ?? '';
            $direccion = $_POST['direccion'] ?? '';

            // Validaciones
            if (!$nombre || !$email || !$password || !$confirm_password) {
                header('Location: ../views/registro_publico.php?error=campos_vacios');
                exit();
            }

            if ($password !== $confirm_password) {
                header('Location: ../views/registro_publico.php?error=password_no_coincide');
                exit();
            }

            // Validar fortaleza de contraseña
            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
                header('Location: ../views/registro_publico.php?error=password_debil');
                exit();
            }

            // Verificar si el email ya existe
            if ($this->usuarioModel->emailExiste($email)) {
                header('Location: ../views/registro_publico.php?error=email_existente');
                exit();
            }

            // Obtener ID del rol "Visitante-No-Confirmado"
            $stmt = $this->pdo->prepare("SELECT id FROM roles WHERE nombre_rol = 'Visitante-No-Confirmado'");
            $stmt->execute();
            $rol = $stmt->fetch();
            
            if (!$rol) {
                header('Location: ../views/registro_publico.php?error=rol_no_encontrado');
                exit();
            }

            $rol_id = $rol['id'];

            // Encriptar contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insertar nuevo usuario
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios (rol_id, nombre, email, password, telefono, direccion, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            
            $success = $stmt->execute([$rol_id, $nombre, $email, $hashedPassword, $telefono, $direccion]);

            if ($success) {
                // Obtener ID del usuario creado
                $usuario_id = $this->pdo->lastInsertId();
                
                // Registrar actividad
                $this->registrarActividad($usuario_id, 'Registro público', 'Usuarios', 'Nuevo usuario registrado: ' . $email);
                
                // Redirigir a login con mensaje de éxito
                header('Location: ../index.php?success=registrado&email=' . urlencode($email));
                exit();
            } else {
                header('Location: ../views/registro_publico.php?error=bd');
                exit();
            }
        } else {
            header('Location: ../views/registro_publico.php');
            exit();
        }
    }

    /**
     * Confirmar usuario (para administradores e inquilinos)
     */
    public function confirmarUsuario() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../index.php?error=no_autenticado');
            exit();
        }

        // Verificar permisos
        $usuario_actual = $this->usuarioModel->obtenerPorId($_SESSION['user_id']);
        
        if (!in_array($usuario_actual['nombre_rol'], ['Administrador del Sistema', 'Inquilino'])) {
            header('Location: ../index.php?error=no_autorizado');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario_id = $_POST['usuario_id'] ?? '';
            $nuevo_rol = $_POST['nuevo_rol'] ?? '';
            
            // Validar que el usuario a confirmar exista
            $usuario = $this->usuarioModel->obtenerPorId($usuario_id);
            if (!$usuario) {
                header('Location: ../views/usuarios.php?error=usuario_no_encontrado');
                exit();
            }

            // Determinar nuevo rol según quién confirma
            if ($usuario_actual['nombre_rol'] === 'Administrador del Sistema') {
                $rol_confirmado = 'Visitante-Confirmado-Admin';
            } else {
                $rol_confirmado = 'Visitante-Confirmado-Propietario';
            }

            // Obtener ID del rol de confirmación
            $stmt = $this->pdo->prepare("SELECT id FROM roles WHERE nombre_rol = ?");
            $stmt->execute([$rol_confirmado]);
            $rol = $stmt->fetch();
            
            if (!$rol) {
                header('Location: ../views/usuarios.php?error=rol_no_encontrado');
                exit();
            }

            // Actualizar usuario
            $stmt = $this->pdo->prepare("
                UPDATE usuarios 
                SET rol_id = ?, estado = 'activo', fecha_confirmacion = NOW(), confirmado_por = ? 
                WHERE id = ?
            ");
            
            $success = $stmt->execute([$rol['id'], $_SESSION['user_id'], $usuario_id]);

            if ($success) {
                // Registrar actividad
                $this->registrarActividad($_SESSION['user_id'], 'Confirmación usuario', 'Usuarios', 
                    'Confirmó al usuario: ' . $usuario['email']);
                
                // Enviar notificación
                $this->crearNotificacion($usuario_id, 
                    'Cuenta Confirmada', 
                    'Su cuenta ha sido confirmada por ' . $usuario_actual['nombre'] . '. Ahora tiene acceso completo al sistema.',
                    'success');
                
                header('Location: ../views/usuarios.php?success=confirmado');
                exit();
            } else {
                header('Location: ../views/usuarios.php?error=bd');
                exit();
            }
        }
    }

    /**
     * Solicitar recuperación de contraseña
     */
    public function solicitarRecuperacion() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                header('Location: ../views/recuperar.php?paso=1&error=csrf');
                exit();
            }

            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

            if (!$email) {
                header('Location: ../views/recuperar.php?paso=1&error=email_invalido');
                exit();
            }

            // Verificar si el email existe
            $user = $this->usuarioModel->obtenerPorEmail($email);
            if (!$user) {
                // Por seguridad, no revelamos si el email existe
                header('Location: ../views/recuperar.php?paso=1&success=email_enviado');
                exit();
            }

            // Generar token de recuperación
            $token = bin2hex(random_bytes(32));

            // Guardar token en la base de datos (expira en 1 hora)
            $stmt = $this->pdo->prepare("UPDATE usuarios SET token_recuperacion = ? WHERE email = ?");
            $stmt->execute([$token, $email]);

            // Crear enlace de recuperación
            $enlace = "http://" . $_SERVER['HTTP_HOST'] . "/views/recuperar.php?paso=2&token=$token";
            
            // En producción, aquí se enviaría un correo
            // mail($email, "Recuperación de Contraseña", "Haga clic en: $enlace");
            
            // Para desarrollo, redirigimos directamente
            header("Location: ../views/recuperar.php?paso=2&token=$token");
            exit();
        } else {
            header('Location: ../views/recuperar.php?paso=1');
            exit();
        }
    }

    /**
     * Restablecer contraseña con token
     */
    public function restablecerPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar CSRF token
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                header('Location: ../views/recuperar.php?paso=1&error=csrf');
                exit();
            }

            $token = $_POST['token'] ?? '';
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validaciones
            if (!$token || !$email || !$password || !$confirm_password) {
                header('Location: ../views/recuperar.php?paso=1&error=campos_vacios');
                exit();
            }

            if ($password !== $confirm_password) {
                header("Location: ../views/recuperar.php?paso=2&token=$token&error=password_no_coincide");
                exit();
            }

            // Validar fortaleza de contraseña
            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/\d/', $password)) {
                header("Location: ../views/recuperar.php?paso=2&token=$token&error=password_debil");
                exit();
            }

            // Verificar token válido
            $user = $this->usuarioModel->obtenerPorTokenRecuperacion($token);
            
            if (!$user || $user['email'] !== $email) {
                header('Location: ../views/recuperar.php?paso=1&error=token_invalido');
                exit();
            }

            // Encriptar nueva contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Actualizar contraseña y eliminar token
            $stmt = $this->pdo->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL WHERE email = ?");
            $success = $stmt->execute([$hashedPassword, $email]);

            if ($success) {
                // Registrar actividad
                $this->registrarActividad($user['id'], 'Restablecimiento contraseña', 'Autenticación', 
                    'Contraseña restablecida exitosamente');
                
                // Enviar notificación
                $this->crearNotificacion($user['id'], 
                    'Contraseña Actualizada', 
                    'Su contraseña ha sido actualizada exitosamente.',
                    'success');
                
                header('Location: ../index.php?success=password_actualizada');
                exit();
            } else {
                header("Location: ../views/recuperar.php?paso=2&token=$token&error=bd");
                exit();
            }
        } else {
            header('Location: ../views/recuperar.php?paso=1');
            exit();
        }
    }

    /**
     * Verificar disponibilidad de email (AJAX)
     */
    public function checkEmail() {
        $email = $_GET['email'] ?? '';
        
        if (!$email) {
            echo json_encode(['available' => false]);
            exit();
        }

        $user = $this->usuarioModel->obtenerPorEmail($email);
        
        header('Content-Type: application/json');
        echo json_encode(['available' => !$user]);
        exit();
    }

    /**
     * Cierra la sesión y limpia los datos
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            // Limpiar token de sesión en la base de datos
            try {
                $stmt = $this->pdo->prepare("UPDATE usuarios SET session_token = NULL WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
            } catch (Exception $e) {
                error_log("Error al limpiar session_token: " . $e->getMessage());
            }
            
            // Registrar actividad
            $this->registrarActividad($_SESSION['user_id'], 'Cierre de sesión', 'Autenticación', 
                'Usuario cerró sesión en el sistema');
        }
        
        session_unset();
        session_destroy();
        header('Location: ../index.php?msg=logged_out');
        exit();
    }

    /**
     * Registrar actividad en el sistema
     */
    private function registrarActividad($usuario_id, $accion, $modulo, $descripcion) {
        try {
            $sql = "INSERT INTO actividades_log (usuario_id, accion, modulo, descripcion, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)";
                    
            $sql = "INSERT INTO actividades_log (usuario_id, accion, modulo, descripcion, ip_address, user_agent, metadata) 
                    VALUES (?, ?, ?, ?, ?, ?, NULL)";
        
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $usuario_id,
                $accion,
                $modulo,
                $descripcion,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido'
            ]);
        } catch (Exception $e) {
            error_log("Error al registrar actividad: " . $e->getMessage());
        }
    }

    /**
     * Crear notificación para usuario
     */
    private function crearNotificacion($usuario_id, $titulo, $mensaje, $tipo = 'info') {
        try {
            $sql = "INSERT INTO notificaciones (usuario_id, titulo, mensaje, tipo) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$usuario_id, $titulo, $mensaje, $tipo]);
        } catch (Exception $e) {
            error_log("Error al crear notificación: " . $e->getMessage());
        }
    }
}
?>