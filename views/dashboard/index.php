<?php
/**
 * Dashboard Principal - Controlador frontal
 * Redirige al dashboard correspondiente según el rol
 *   archivo : index.php
 * ubicación : views/dashboard/
 */

require_once '../../config/db.php';
require_once '../../includes/seguridad.php';

// Iniciar sesión segura
iniciarSesionSegura();

// Verificar autenticación
if (!usuarioAutenticado()) {
    header('Location: ../../index.php?error=no_autenticado');
    exit();
}

// Obtener usuario actual
$usuario = obtenerUsuarioActual();

// Redirigir según rol
redirigirPorRol();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Torre General</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .loading-container {
            text-align: center;
            color: white;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        h1 {
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        p {
            margin-bottom: 10px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <h1>Redirigiendo a su panel de control...</h1>
        <p>Hola, <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></p>
        <p>Rol: <?php echo htmlspecialchars($usuario['rol']); ?></p>
        <p>Por favor espere un momento...</p>
    </div>

    <script>
        // Redirección automática por JavaScript en caso de que PHP falle
        setTimeout(function() {
            window.location.href = obtenerRutaPorRol('<?php echo $usuario['rol']; ?>');
        }, 2000);

        function obtenerRutaPorRol(rol) {
            const rutas = {
                'Administrador del Sistema': 'admin/',
                'Cajero': 'cajero/',
                'Gerente': 'gerente/',
                'Inquilino': 'inquilino/',
                'Visitante-No-Confirmado': 'visitante/?estado=pendiente',
                'Visitante-Confirmado-Admin': 'visitante/',
                'Visitante-Confirmado-Propietario': 'visitante/',
                'Personal de Obras': 'personal-obras/',
                'Seguridad': 'seguridad/'
            };
            
            return rutas[rol] || '';
        }
    </script>
</body>
</html>