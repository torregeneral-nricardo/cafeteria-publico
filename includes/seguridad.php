<?php
/**
 * Funciones de seguridad y verificación de sesión
 * Archivo   :   includes/seguridad.php
 */

/**
 * Iniciar o reanudar sesión segura
 */
function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configuración segura de sesión
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        session_start();
        
        // Regenerar ID de sesión periódicamente
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function usuarioAutenticado() {
    iniciarSesionSegura();
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Verificar timeout de sesión (30 minutos)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        cerrarSesion();
        return false;
    }

    // Actualizar timestamp de actividad
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Verificar si el usuario tiene un permiso específico
 */
function tienePermiso($codigo_permiso) {
    if (!usuarioAutenticado()) {
        return false;
    }

    // Administrador tiene todos los permisos
    if ($_SESSION['user_rol'] === 'Administrador del Sistema') {
        return true;
    }

    // Verificar en la sesión
    if (isset($_SESSION['user_permisos'])) {
        foreach ($_SESSION['user_permisos'] as $permiso) {
            if ($permiso['codigo'] === $codigo_permiso) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Verificar si el usuario tiene acceso a un módulo
 */
function tieneAccesoModulo($codigo_modulo) {
    if (!usuarioAutenticado()) {
        return false;
    }

    // Administrador tiene acceso a todos los módulos
    if ($_SESSION['user_rol'] === 'Administrador del Sistema') {
        return true;
    }

    // Verificar en la sesión
    if (isset($_SESSION['user_modulos'])) {
        foreach ($_SESSION['user_modulos'] as $modulo) {
            if ($modulo['codigo'] === $codigo_modulo) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Redirigir si no está autenticado
 */
function requerirAutenticacion() {
    if (!usuarioAutenticado()) {
        header('Location: /index.php?error=no_autenticado');
        exit();
    }
}

/**
 * Redirigir si no tiene permiso
 */
function requerirPermiso($codigo_permiso) {
    requerirAutenticacion();
    
    if (!tienePermiso($codigo_permiso)) {
        header('Location: /dashboard/?error=no_autorizado');
        exit();
    }
}

/**
 * Redirigir si no tiene acceso al módulo
 */
function requerirAccesoModulo($codigo_modulo) {
    requerirAutenticacion();
    
    if (!tieneAccesoModulo($codigo_modulo)) {
        header('Location: /dashboard/?error=acceso_denegado');
        exit();
    }
}

/**
 * Obtener información del usuario actual
 */
function obtenerUsuarioActual() {
    if (usuarioAutenticado()) {
        return [
            'id' => $_SESSION['user_id'],
            'nombre' => $_SESSION['user_nombre'],
            'email' => $_SESSION['user_email'],
            'rol' => $_SESSION['user_rol'],
            'rol_id' => $_SESSION['user_rol_id'],
            'foto' => $_SESSION['user_foto'] ?? null,
            'session_token' => $_SESSION['session_token'] ?? null // Añadido
        ];
    }
    return null;
}

/**
 * Cerrar sesión de manera segura
 */
function cerrarSesion() {
    iniciarSesionSegura();
    
    // Registrar cierre de sesión si hay usuario
    if (isset($_SESSION['user_id'])) {
        // Limpiar token de sesión en la base de datos si es posible
        if (isset($_SESSION['session_token'])) {
            try {
                require_once __DIR__ . '/conexion.php';
                $pdo = obtenerConexion();
                $stmt = $pdo->prepare("UPDATE usuarios SET session_token = NULL WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
            } catch (Exception $e) {
                error_log("Error al limpiar session_token en cerrarSesion(): " . $e->getMessage());
            }
        }
        error_log("Usuario " . ($_SESSION['user_email'] ?? 'Desconocido') . " cerró sesión");
    }
    
    // Destruir sesión
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Generar token CSRF
 */
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validar token CSRF
 */
function validarTokenCSRF($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Sanitizar entrada de datos
 */
function limpiarEntrada($dato) {
    if (is_array($dato)) {
        return array_map('limpiarEntrada', $dato);
    }
    
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    return $dato;
}

/**
 * Redirigir según rol
 */
function redirigirPorRol() {
    if (!usuarioAutenticado()) {
        return;
    }

    $rol = $_SESSION['user_rol'];
    $ruta_base = '/views/dashboard/';
    
    $rutas = [
        'Administrador del Sistema' => $ruta_base . 'admin/',
        'Cajero' => $ruta_base . 'cajero/',
        'Gerente' => $ruta_base . 'gerente/',
        'Inquilino' => $ruta_base . 'inquilino/',
        'Visitante-No-Confirmado' => $ruta_base . 'visitante/?estado=pendiente',
        'Visitante-Confirmado-Admin' => $ruta_base . 'visitante/',
        'Visitante-Confirmado-Propietario' => $ruta_base . 'visitante/',
        'Personal de Obras' => $ruta_base . 'personal-obras/',
        'Seguridad' => $ruta_base . 'seguridad/'
    ];

    if (isset($rutas[$rol])) {
        header('Location: ' . $rutas[$rol]);
        exit();
    }
}

/**
 * Obtener menú de navegación según permisos
 */
function obtenerMenuNavegacion() {
    if (!usuarioAutenticado() || !isset($_SESSION['user_modulos'])) {
        return [];
    }

    // Ordenar módulos por orden
    $modulos = $_SESSION['user_modulos'];
    usort($modulos, function($a, $b) {
        return $a['orden'] <=> $b['orden'];
    });

    return $modulos;
}
?>