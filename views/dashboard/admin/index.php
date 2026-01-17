<?php
/**
 * Dashboard para Administrador del Sistema
 *   archivo : index.php
 * ubicación : views/dashboard/admin/
 */

require_once '../../../includes/seguridad.php';
require_once '../../../config/db.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea administrador
if ($usuario['rol'] !== 'Administrador del Sistema') {
    header('Location: ../../../index.php?error=no_autorizado');
    exit();
}

// Obtener estadísticas del sistema
// $pdo = new PDO("mysql:host=localhost;dbname=;charset=utf8mb4", "usuario", "password");

$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM usuarios) as total_usuarios,
    (SELECT COUNT(*) FROM usuarios WHERE estado = 'activo') as usuarios_activos,
    (SELECT COUNT(*) FROM usuarios WHERE estado = 'pendiente') as usuarios_pendientes,
    (SELECT COUNT(*) FROM actividades_log) as total_actividades,
    (SELECT COUNT(*) FROM notificaciones WHERE leida = FALSE) as notificaciones_pendientes";

$stmt = $pdo->query($stats_sql);
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener últimos usuarios registrados
$usuarios_sql = "SELECT u.nombre, u.email, u.estado, r.nombre_rol, u.fecha_creacion 
                 FROM usuarios u 
                 JOIN roles r ON u.rol_id = r.id 
                 ORDER BY u.fecha_creacion DESC 
                 LIMIT 5";
$usuarios = $pdo->query($usuarios_sql)->fetchAll(PDO::FETCH_ASSOC);

// Obtener últimas actividades
$actividades_sql = "SELECT a.accion, a.modulo, a.descripcion, a.fecha_registro, u.nombre as usuario
                    FROM actividades_log a
                    JOIN usuarios u ON a.usuario_id = u.id
                    ORDER BY a.fecha_registro DESC 
                    LIMIT 10";
$actividades = $pdo->query($actividades_sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - Torre General</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .stat-card {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        .menu-item {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior -->
    <nav class="bg-white/90 backdrop-blur-md shadow-lg">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-crown text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl">Panel de Administración</h1>
                        <p class="text-sm text-gray-600">Sistema Torre General</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        <p class="font-semibold"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                        <p class="text-sm text-gray-600">Administrador del Sistema</p>
                    </div>
                    <div class="relative">
                        <button id="user-menu" class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-600 to-pink-600 text-white flex items-center justify-center">
                            <i class="fas fa-user"></i>
                        </button>
                        <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 z-50">
                            <a href="perfil.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-user mr-2"></i>Mi Perfil
                            </a>
                            <a href="configuracion.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2"></i>Configuración
                            </a>
                            <div class="border-t my-2"></div>
                            <a href="../../../controllers/AuthController.php?action=logout" 
                               class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <!-- Encabezado -->
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold text-white mb-4">Bienvenido, Administrador</h1>
            <p class="text-white/80 text-lg">Gestión completa del sistema Torre General</p>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Total Usuarios</p>
                        <p class="text-3xl font-bold mt-2"><?php echo $estadisticas['total_usuarios']; ?></p>
                    </div>
                    <i class="fas fa-users text-3xl opacity-50"></i>
                </div>
                <div class="mt-4 text-sm">
                    <span class="opacity-90"><?php echo $estadisticas['usuarios_activos']; ?> activos</span>
                </div>
            </div>
            
            <div class="stat-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Pendientes</p>
                        <p class="text-3xl font-bold mt-2"><?php echo $estadisticas['usuarios_pendientes']; ?></p>
                    </div>
                    <i class="fas fa-user-clock text-3xl opacity-50"></i>
                </div>
                <div class="mt-4 text-sm">
                    <span class="opacity-90">Por confirmar</span>
                </div>
            </div>
            
            <div class="stat-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Actividades</p>
                        <p class="text-3xl font-bold mt-2"><?php echo $estadisticas['total_actividades']; ?></p>
                    </div>
                    <i class="fas fa-history text-3xl opacity-50"></i>
                </div>
                <div class="mt-4 text-sm">
                    <span class="opacity-90">Registradas</span>
                </div>
            </div>
            
            <div class="stat-card rounded-2xl p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Notificaciones</p>
                        <p class="text-3xl font-bold mt-2"><?php echo $estadisticas['notificaciones_pendientes']; ?></p>
                    </div>
                    <i class="fas fa-bell text-3xl opacity-50"></i>
                </div>
                <div class="mt-4 text-sm">
                    <span class="opacity-90">Sin leer</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Menú de Acciones Rápidas -->
            <div class="lg:col-span-1">
                <div class="admin-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">Acciones Rápidas</h2>
                    <div class="space-y-4">
                        <a href="../usuarios.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Gestionar Usuarios</p>
                                    <p class="text-sm text-gray-600">Ver, crear y editar usuarios</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../roles.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-user-shield text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Gestionar Roles</p>
                                    <p class="text-sm text-gray-600">Configurar permisos y acceso</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../configuracion.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-cogs text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Configuración</p>
                                    <p class="text-sm text-gray-600">Ajustes del sistema</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../reportes.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-500 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-chart-bar text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Reportes</p>
                                    <p class="text-sm text-gray-600">Estadísticas y análisis</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Últimos Usuarios Registrados -->
            <div class="lg:col-span-2">
                <div class="admin-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">Últimos Usuarios Registrados</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 text-gray-600">Usuario</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Rol</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Estado</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="font-medium"><?php echo htmlspecialchars($user['nombre']); ?></div>
                                        <div class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                                            <?php echo $user['nombre_rol'] === 'Administrador del Sistema' ? 'bg-purple-100 text-purple-800' : 
                                                   ($user['nombre_rol'] === 'Cajero' ? 'bg-green-100 text-green-800' : 
                                                   ($user['nombre_rol'] === 'Gerente' ? 'bg-blue-100 text-blue-800' : 
                                                   ($user['nombre_rol'] === 'Inquilino' ? 'bg-pink-100 text-pink-800' : 
                                                   'bg-gray-100 text-gray-800'))); ?>">
                                            <?php echo htmlspecialchars($user['nombre_rol']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium 
                                            <?php echo $user['estado'] === 'activo' ? 'bg-green-100 text-green-800' : 
                                                   ($user['estado'] === 'pendiente' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($user['estado'] === 'inactivo' ? 'bg-red-100 text-red-800' : 
                                                   'bg-gray-100 text-gray-800')); ?>">
                                            <?php echo htmlspecialchars($user['estado']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-gray-600">
                                        <?php echo date('d/m/Y', strtotime($user['fecha_creacion'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="../usuarios.php" class="text-purple-600 hover:text-purple-800 font-medium">
                            Ver todos los usuarios →
                        </a>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="admin-card rounded-2xl p-6 mt-6">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">Actividad Reciente del Sistema</h2>
                    <div class="space-y-4">
                        <?php foreach ($actividades as $actividad): ?>
                        <div class="flex items-start p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="mr-4 mt-1">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                                    <i class="fas fa-history text-white text-sm"></i>
                                </div>
                            </div>
                            <div class="flex-1">
                                <div class="flex justify-between">
                                    <p class="font-medium"><?php echo htmlspecialchars($actividad['accion']); ?></p>
                                    <span class="text-sm text-gray-500">
                                        <?php echo date('H:i', strtotime($actividad['fecha_registro'])); ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($actividad['descripcion']); ?></p>
                                <div class="flex items-center mt-2">
                                    <span class="text-xs px-2 py-1 rounded bg-gray-200 text-gray-700">
                                        <?php echo htmlspecialchars($actividad['modulo']); ?>
                                    </span>
                                    <span class="text-xs text-gray-500 ml-2">
                                        <?php echo htmlspecialchars($actividad['usuario']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dropdown menu
        document.getElementById('user-menu').addEventListener('click', function() {
            const menu = document.getElementById('dropdown-menu');
            menu.classList.toggle('hidden');
        });

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('dropdown-menu');
            const button = document.getElementById('user-menu');
            
            if (!button.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>