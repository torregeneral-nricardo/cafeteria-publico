<?php
/**
 * Dashboard para Seguridad
 */

require_once '../../../includes/seguridad.php';
require_once '../../../config/db.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea seguridad
if ($usuario['rol'] !== 'Seguridad') {
    header('Location: ../../../index.php?error=no_autorizado');
    exit();
}

// Obtener color del rol
// $pdo = new PDO("mysql:host=localhost;dbname=proyecto_sistema;charset=utf8mb4", "usuario", "password");
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Seguridad'");
$stmt->execute();
$rol_color = $stmt->fetchColumn();

// Datos simulados para el dashboard
$estado_edificio = [
    'alarmas' => 'Todas activas',
    'camaras_activas' => 28,
    'camaras_totales' => 32,
    'accesos_abiertos' => 4,
    'accesos_totales' => 12,
    'incidentes_24h' => 2,
    'personal_turno' => 3
];

$accesos_recientes = [
    ['hora' => '14:30', 'persona' => 'Carlos Mendoza', 'tipo' => 'Visitante', 'ubicacion' => 'Entrada Principal', 'estado' => 'Permitido'],
    ['hora' => '14:15', 'persona' => 'Ana Martínez', 'tipo' => 'Gerente', 'ubicacion' => 'Entrada Staff', 'estado' => 'Permitido'],
    ['hora' => '13:45', 'persona' => 'Visitante Sin Registrar', 'tipo' => 'Desconocido', 'ubicacion' => 'Estacionamiento', 'estado' => 'Denegado'],
    ['hora' => '13:20', 'persona' => 'Pedro López', 'tipo' => 'Mantenimiento', 'ubicacion' => 'Entrada Servicio', 'estado' => 'Permitido'],
    ['hora' => '12:50', 'persona' => 'María González', 'tipo' => 'Cajero', 'ubicacion' => 'Entrada Principal', 'estado' => 'Permitido']
];

$rondas_pendientes = [
    ['zona' => 'Pisos 1-3', 'responsable' => 'Roberto Jiménez', 'hora_programada' => '15:00', 'estado' => 'Pendiente'],
    ['zona' => 'Estacionamiento Nivel B', 'responsable' => 'Miguel Rojas', 'hora_programada' => '15:30', 'estado' => 'En progreso'],
    ['zona' => 'Pisos 7-10', 'responsable' => 'Carlos Sánchez', 'hora_programada' => '16:00', 'estado' => 'Pendiente'],
    ['zona' => 'Áreas Comunes', 'responsable' => 'Roberto Jiménez', 'hora_programada' => '16:30', 'estado' => 'Pendiente']
];

$incidentes_recientes = [
    ['id' => 'INC-2024-045', 'tipo' => 'Puerta Forzada', 'ubicacion' => 'Salida Emergencia Piso 2', 'fecha' => '25/10/2024 22:15', 'estado' => 'Investigación'],
    ['id' => 'INC-2024-044', 'tipo' => 'Objeto Sospechoso', 'ubicacion' => 'Estacionamiento Nivel A', 'fecha' => '24/10/2024 14:30', 'estado' => 'Resuelto'],
    ['id' => 'INC-2024-043', 'tipo' => 'Alarma Falsa', 'ubicacion' => 'Sala de Servidores', 'fecha' => '23/10/2024 11:20', 'estado' => 'Cerrado']
];

$camaras_principales = [
    ['id' => 'CAM-001', 'ubicacion' => 'Entrada Principal', 'estado' => 'Activa', 'ultima_revision' => 'Hoy 10:30'],
    ['id' => 'CAM-007', 'ubicacion' => 'Estacionamiento Nivel A', 'estado' => 'Activa', 'ultima_revision' => 'Hoy 09:15'],
    ['id' => 'CAM-012', 'ubicacion' => 'Pasillo Pisos 3-4', 'estado' => 'Mantenimiento', 'ultima_revision' => 'Ayer 16:45'],
    ['id' => 'CAM-018', 'ubicacion' => 'Sala de Cafetería', 'estado' => 'Activa', 'ultima_revision' => 'Hoy 08:00']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Seguridad - Torre General</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-rol: <?php echo $rol_color ?: '#FEF2F2'; ?>;
        }
        body {
            font-family: 'Inter', sans-serif;
            /**background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); **/
            background: linear-gradient(135deg, #f5f5dc 0%, #e6e6cc 100%); /* ← Beige/verde olivo claro */
            min-height: 100vh;
        }
        .seguridad-card {
            background: white;
            border: 1px solid rgba(254, 242, 242, 0.3);
            box-shadow: 0 4px 6px rgba(254, 242, 242, 0.1);
        }
        .status-card {
            background: linear-gradient(135deg, var(--color-rol) 0%, #b8b894 100%); /*fecaca*/
        }
        .menu-item {
            background: rgba(254, 242, 242, 0.1);
            border: 1px solid rgba(254, 242, 242, 0.3);
        }
        .menu-item:hover {
            background: rgba(254, 242, 242, 0.2);
            transform: translateX(5px);
        }
        .status-allowed {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .status-denied {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .status-investigation {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .camera-active {
            border-left: 4px solid #10b981;
        }
        .camera-maintenance {
            border-left: 4px solid #f59e0b;
        }
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior -->
    <nav class="bg-white shadow-md border-b border-red-100">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-rose-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Centro de Control de Seguridad</h1>
                        <p class="text-sm text-gray-600">Torre General - Vigilancia 24/7</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <!-- Indicador de Estado -->
                    <div class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-green-50 text-green-800">
                        <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                        <span class="font-medium">Sistema Activo</span>
                        <span class="text-sm">• <?php echo $estado_edificio['camaras_activas']; ?>/<?php echo $estado_edificio['camaras_totales']; ?> cámaras</span>
                    </div>
                    
                    <!-- Botón Emergencia -->
                    <button id="emergency-btn" class="px-4 py-2 bg-gradient-to-r from-red-600 to-rose-700 text-white rounded-lg hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Alerta
                    </button>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="font-semibold"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                            <p class="text-sm text-gray-600">Oficial de Seguridad</p>
                        </div>
                        <div class="relative">
                            <button id="user-menu" class="w-10 h-10 rounded-full bg-gradient-to-br from-red-500 to-rose-600 text-white flex items-center justify-center">
                                <i class="fas fa-user"></i>
                            </button>
                            <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 z-50 border border-gray-200">
                                <a href="perfil.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Mi Perfil
                                </a>
                                <a href="../reportes.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-file-alt mr-2"></i>Reportes
                                </a>
                                <a href="../configuracion.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
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
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <!-- Encabezado con Estado -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido, <?php echo explode(' ', $usuario['nombre'])[0]; ?></h1>
                    <p class="text-gray-600">Panel de control para vigilancia y seguridad</p>
                </div>
                
                <div class="flex flex-wrap gap-3">
                    <a href="../accesos/control.php" class="px-6 py-3 bg-gradient-to-r from-red-600 to-rose-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-door-closed mr-2"></i>Control de Accesos
                    </a>
                    <a href="../rondas/registrar.php" class="px-6 py-3 bg-gradient-to-r from-amber-600 to-orange-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-walking mr-2"></i>Registrar Ronda
                    </a>
                    <a href="../incidentes/reportar.php" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>Reportar Incidente
                    </a>
                </div>
            </div>

            <!-- Estado del Sistema -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="status-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-video mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Cámaras Activas</p>
                            <p class="text-2xl font-bold"><?php echo $estado_edificio['camaras_activas']; ?>/<?php echo $estado_edificio['camaras_totales']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="status-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-door-open mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Accesos Abiertos</p>
                            <p class="text-2xl font-bold"><?php echo $estado_edificio['accesos_abiertos']; ?>/<?php echo $estado_edificio['accesos_totales']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="status-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Incidentes 24h</p>
                            <p class="text-2xl font-bold"><?php echo $estado_edificio['incidentes_24h']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="status-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-users mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Personal en Turno</p>
                            <p class="text-2xl font-bold"><?php echo $estado_edificio['personal_turno']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Columna Izquierda: Accesos y Rondas -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Accesos Recientes -->
                <div class="seguridad-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">🚪 Accesos Recientes (Últimas 2 horas)</h2>
                        <a href="../accesos/historial.php" class="text-red-600 hover:text-red-800 font-medium text-sm">
                            Ver historial completo →
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 text-gray-600">Hora</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Persona</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Tipo</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Ubicación</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accesos_recientes as $acceso): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium"><?php echo $acceso['hora']; ?></td>
                                    <td class="py-3 px-4"><?php echo $acceso['persona']; ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium 
                                            <?php echo $acceso['tipo'] === 'Visitante' ? 'bg-purple-100 text-purple-800' : 
                                                   ($acceso['tipo'] === 'Gerente' ? 'bg-blue-100 text-blue-800' : 
                                                   ($acceso['tipo'] === 'Cajero' ? 'bg-green-100 text-green-800' : 
                                                   ($acceso['tipo'] === 'Mantenimiento' ? 'bg-amber-100 text-amber-800' : 
                                                   'bg-gray-100 text-gray-800'))); ?>">
                                            <?php echo $acceso['tipo']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4"><?php echo $acceso['ubicacion']; ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium 
                                            <?php echo $acceso['estado'] === 'Permitido' ? 'status-allowed' : 'status-denied'; ?>">
                                            <?php echo $acceso['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 p-4 bg-gradient-to-r from-red-50 to-rose-50 rounded-xl border border-red-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">Acceso denegado detectado</p>
                                <p class="font-medium">Entrada de persona no identificada en estacionamiento</p>
                            </div>
                            <a href="../accesos/investigar.php" class="px-4 py-2 bg-gradient-to-r from-red-600 to-rose-700 text-white rounded-lg hover:shadow-lg transition-all text-sm">
                                Investigar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Rondas de Vigilancia -->
                <div class="seguridad-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">🚶‍♂️ Rondas de Vigilancia Programadas</h2>
                        <a href="../rondas/calendario.php" class="text-red-600 hover:text-red-800 font-medium text-sm">
                            Ver calendario completo →
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($rondas_pendientes as $ronda): ?>
                        <div class="flex items-center justify-between p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full mr-4 flex items-center justify-center
                                    <?php echo $ronda['estado'] === 'En progreso' ? 'bg-green-100 text-green-600' : 
                                           ($ronda['estado'] === 'Pendiente' ? 'bg-amber-100 text-amber-600' : 
                                           'bg-blue-100 text-blue-600'); ?>">
                                    <i class="fas fa-walking"></i>
                                </div>
                                <div>
                                    <p class="font-medium"><?php echo $ronda['zona']; ?></p>
                                    <p class="text-sm text-gray-600">Responsable: <?php echo $ronda['responsable']; ?></p>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <p class="font-medium"><?php echo $ronda['hora_programada']; ?></p>
                                <span class="px-2 py-1 rounded text-xs font-medium 
                                    <?php echo $ronda['estado'] === 'En progreso' ? 'bg-green-100 text-green-800' : 
                                           ($ronda['estado'] === 'Pendiente' ? 'bg-amber-100 text-amber-800' : 
                                           'bg-blue-100 text-blue-800'); ?>">
                                    <?php echo $ronda['estado']; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a href="../rondas/registrar.php" class="block w-full py-3 text-center bg-gradient-to-r from-amber-600 to-orange-700 text-white rounded-xl hover:shadow-lg transition-all font-medium">
                            <i class="fas fa-plus mr-2"></i>Registrar Nueva Ronda
                        </a>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Incidentes y Cámaras -->
            <div class="space-y-6">
                <!-- Incidentes Recientes -->
                <div class="seguridad-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">⚠️ Incidentes Recientes</h2>
                        <a href="../incidentes/historial.php" class="text-red-600 hover:text-red-800 font-medium text-sm">
                            Ver todos →
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($incidentes_recientes as $incidente): ?>
                        <div class="p-4 rounded-lg bg-red-50 border border-red-200">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium text-red-800"><?php echo $incidente['tipo']; ?></p>
                                    <p class="text-sm text-red-600"><?php echo $incidente['id']; ?></p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-medium 
                                    <?php echo $incidente['estado'] === 'Investigación' ? 'status-investigation' : 
                                           ($incidente['estado'] === 'Resuelto' ? 'status-allowed' : 
                                           'status-denied'); ?>">
                                    <?php echo $incidente['estado']; ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 mb-3"><?php echo $incidente['ubicacion']; ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">
                                    <i class="far fa-calendar mr-1"></i><?php echo $incidente['fecha']; ?>
                                </span>
                                <a href="../incidentes/detalle.php?id=<?php echo $incidente['id']; ?>" class="text-xs text-red-600 hover:text-red-800">
                                    <i class="fas fa-search mr-1"></i>Investigar
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a href="../incidentes/reportar.php" class="block w-full py-3 text-center bg-gradient-to-r from-blue-600 to-cyan-700 text-white rounded-xl hover:shadow-lg transition-all font-medium">
                            <i class="fas fa-plus mr-2"></i>Reportar Nuevo Incidente
                        </a>
                    </div>
                </div>

                <!-- Estado de Cámaras -->
                <div class="seguridad-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">📹 Estado de Cámaras Principales</h2>
                    
                    <div class="space-y-4">
                        <?php foreach ($camaras_principales as $camara): ?>
                        <div class="p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors <?php echo $camara['estado'] === 'Activa' ? 'camera-active' : 'camera-maintenance'; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium"><?php echo $camara['ubicacion']; ?></p>
                                    <p class="text-sm text-gray-600"><?php echo $camara['id']; ?></p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-medium 
                                    <?php echo $camara['estado'] === 'Activa' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'; ?>">
                                    <?php echo $camara['estado']; ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">
                                    <i class="far fa-clock mr-1"></i><?php echo $camara['ultima_revision']; ?>
                                </span>
                                <?php if ($camara['estado'] === 'Activa'): ?>
                                <button class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye mr-1"></i>Ver en vivo
                                </button>
                                <?php else: ?>
                                <button class="text-xs text-amber-600 hover:text-amber-800">
                                    <i class="fas fa-tools mr-1"></i>Solicitar reparación
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="seguridad-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">⚡ Acciones Rápidas</h2>
                    <div class="space-y-3">
                        <a href="../accesos/control.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-rose-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-door-closed text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Control de Accesos</p>
                                    <p class="text-sm text-gray-600">Gestionar entradas/salidas</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../visitantes/registrar.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-user-friends text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Registrar Visitante</p>
                                    <p class="text-sm text-gray-600">Acceso temporal</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../emergencias/protocolo.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-first-aid text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Protocolo Emergencias</p>
                                    <p class="text-sm text-gray-600">Procedimientos de emergencia</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../comunicaciones/autoridades.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-phone-alt text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Contactar Autoridades</p>
                                    <p class="text-sm text-gray-600">Bomberos, policía, etc.</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Emergencia -->
    <div id="emergency-modal" class="fixed inset-0 bg-black bg-opacity-70 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">🔴 Activación de Alerta de Emergencia</h3>
                <p class="text-gray-600">Seleccione el tipo de emergencia:</p>
            </div>
            
            <div class="space-y-3 mb-6">
                <button class="w-full p-4 text-left rounded-xl border border-red-200 hover:bg-red-50 transition-colors">
                    <div class="flex items-center">
                        <i class="fas fa-fire text-red-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-medium">Incendio</p>
                            <p class="text-sm text-gray-600">Activará protocolo de evacuación</p>
                        </div>
                    </div>
                </button>
                
                <button class="w-full p-4 text-left rounded-xl border border-blue-200 hover:bg-blue-50 transition-colors">
                    <div class="flex items-center">
                        <i class="fas fa-user-injured text-blue-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-medium">Accidente / Herido</p>
                            <p class="text-sm text-gray-600">Solicitará primeros auxilios</p>
                        </div>
                    </div>
                </button>
                
                <button class="w-full p-4 text-left rounded-xl border border-amber-200 hover:bg-amber-50 transition-colors">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation text-amber-600 text-xl mr-3"></i>
                        <div>
                            <p class="font-medium">Amenaza / Intruso</p>
                            <p class="text-sm text-gray-600">Alertará a las autoridades</p>
                        </div>
                    </div>
                </button>
            </div>
            
            <div class="flex justify-between">
                <button id="cancel-emergency" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Cancelar
                </button>
                <button class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Confirmar Activación
                </button>
            </div>
        </div>
    </div>

    <script>
        // Dropdown menu
        document.getElementById('user-menu').addEventListener('click', function(e) {
            e.stopPropagation();
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

        // Modal de emergencia
        const emergencyBtn = document.getElementById('emergency-btn');
        const emergencyModal = document.getElementById('emergency-modal');
        const cancelEmergency = document.getElementById('cancel-emergency');

        emergencyBtn.addEventListener('click', function() {
            emergencyModal.classList.remove('hidden');
        });

        cancelEmergency.addEventListener('click', function() {
            emergencyModal.classList.add('hidden');
        });

        // Cerrar modal al hacer clic fuera
        emergencyModal.addEventListener('click', function(e) {
            if (e.target === emergencyModal) {
                emergencyModal.classList.add('hidden');
            }
        });

        // Simulación de cámara en vivo
        document.querySelectorAll('button:contains("Ver en vivo")').forEach(button => {
            button.addEventListener('click', function() {
                alert('Función de vista en vivo simulada. En producción se conectaría al sistema de cámaras.');
            });
        });

        // Actualización de estado de ronda
        document.querySelectorAll('.bg-gray-50 .fa-walking').forEach(icon => {
            icon.parentElement.addEventListener('click', function() {
                const card = this.closest('.bg-gray-50');
                const estado = card.querySelector('span');
                if (estado.textContent === 'Pendiente') {
                    estado.textContent = 'En progreso';
                    estado.className = 'px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800';
                    this.className = 'w-10 h-10 rounded-full mr-4 flex items-center justify-center bg-green-100 text-green-600';
                }
            });
        });
    </script>
</body>
</html>