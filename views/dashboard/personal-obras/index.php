<?php
/**
 * Dashboard para Personal de Obras
 */

require_once '../../../includes/seguridad.php';
require_once '../../../config/db.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea Personal de Obras
if ($usuario['rol'] !== 'Personal de Obras') {
    header('Location: ../../../index.php?error=no_autorizado');
    exit();
}

// Obtener color del rol
// $pdo = new PDO("mysql:host=localhost;dbname=proyecto_sistema;charset=utf8mb4", "usuario", "password");
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Personal de Obras'");
$stmt->execute();
$rol_color = $stmt->fetchColumn();

// Datos simulados para el dashboard
$tareas_asignadas = [
    ['id' => 'TAR-2024-045', 'descripcion' => 'Mantenimiento preventivo a ascensores norte', 'prioridad' => 'Alta', 'fecha_asignada' => '28/10/2024', 'estado' => 'Pendiente'],
    ['id' => 'TAR-2024-046', 'descripcion' => 'Reparación de fuga en baño del piso 3', 'prioridad' => 'Urgente', 'fecha_asignada' => '27/10/2024', 'estado' => 'En progreso'],
    ['id' => 'TAR-2024-047', 'descripcion' => 'Revisión de sistema eléctrico en sala de servidores', 'prioridad' => 'Media', 'fecha_asignada' => '29/10/2024', 'estado' => 'Pendiente'],
    ['id' => 'TAR-2024-048', 'descripcion' => 'Limpieza de ductos de ventilación en cocina', 'prioridad' => 'Baja', 'fecha_asignada' => '30/10/2024', 'estado' => 'Pendiente'],
    ['id' => 'TAR-2024-049', 'descripcion' => 'Cambio de luminarias en estacionamiento', 'prioridad' => 'Media', 'fecha_asignada' => '26/10/2024', 'estado' => 'Completado'],
];

$incidencias_recientes = [
    ['id' => 'INC-2024-101', 'tipo' => 'Eléctrica', 'descripcion' => 'Fallo en iluminación del pasillo del piso 5', 'fecha_reporte' => '25/10/2024 10:30', 'reportado_por' => 'María González', 'estado' => 'Asignada'],
    ['id' => 'INC-2024-102', 'tipo' => 'Fontanería', 'descripcion' => 'Fuga de agua en lavamanos oficina 502', 'fecha_reporte' => '25/10/2024 14:15', 'reportado_por' => 'Carlos Rodríguez', 'estado' => 'En reparación'],
    ['id' => 'INC-2024-103', 'tipo' => 'Climatización', 'descripcion' => 'Aire acondicionado no funciona en sala de juntas 3', 'fecha_reporte' => '24/10/2024 09:00', 'reportado_por' => 'Ana Martínez', 'estado' => 'Resuelta'],
    ['id' => 'INC-2024-104', 'tipo' => 'Estructural', 'descripcion' => 'Puerta del ascensor sur no cierra correctamente', 'fecha_reporte' => '23/10/2024 16:45', 'reportado_por' => 'Pedro López', 'estado' => 'Pendiente'],
];

$solicitudes_materiales = [
    ['id' => 'MAT-2024-025', 'material' => 'Tubo PVC 1/2"', 'cantidad' => 10, 'unidad' => 'metros', 'solicitado_por' => 'Juan Pérez', 'fecha_solicitud' => '24/10/2024', 'estado' => 'Aprobada'],
    ['id' => 'MAT-2024-026', 'material' => 'Cable eléctrico 12 AWG', 'cantidad' => 50, 'unidad' => 'metros', 'solicitado_por' => 'Luis García', 'fecha_solicitud' => '25/10/2024', 'estado' => 'Pendiente'],
    ['id' => 'MAT-2024-027', 'material' => 'Focos LED 18W', 'cantidad' => 24, 'unidad' => 'unidades', 'solicitado_por' => 'Roberto Jiménez', 'fecha_solicitud' => '25/10/2024', 'estado' => 'Rechazada'],
    ['id' => 'MAT-2024-028', 'material' => 'Juego de herramientas', 'cantidad' => 1, 'unidad' => 'juego', 'solicitado_por' => 'Pedro López', 'fecha_solicitud' => '26/10/2024', 'estado' => 'Aprobada'],
];

$metricas_mes = [
    'tareas_completadas' => 18,
    'tareas_pendientes' => 7,
    'incidencias_resueltas' => 24,
    'tiempo_promedio_resolucion' => '4.5 horas',
    'materiales_solicitados' => 42,
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Personal de Obras - Torre General</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-rol: <?php echo $rol_color ?: '#FEFCE8'; ?>;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
            min-height: 100vh;
        }
        .personal-card {
            background: white;
            border: 1px solid rgba(254, 252, 232, 0.3);
            box-shadow: 0 4px 6px rgba(254, 252, 232, 0.1);
        }
        .stat-card {
            background: linear-gradient(135deg, var(--color-rol) 0%, #fde68a 100%);
        }
        .menu-item {
            background: rgba(254, 252, 232, 0.1);
            border: 1px solid rgba(254, 252, 232, 0.3);
        }
        .menu-item:hover {
            background: rgba(254, 252, 232, 0.2);
            transform: translateX(5px);
        }
        .priority-urgente {
            border-left: 4px solid #ef4444;
        }
        .priority-alta {
            border-left: 4px solid #f97316;
        }
        .priority-media {
            border-left: 4px solid #eab308;
        }
        .priority-baja {
            border-left: 4px solid #22c55e;
        }
        .estado-pendiente {
            background-color: #fef3c7;
            color: #d97706;
        }
        .estado-progreso {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        .estado-completado {
            background-color: #dcfce7;
            color: #16a34a;
        }
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior -->
    <nav class="bg-white shadow-md border-b border-amber-100">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-tools text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Mantenimiento y Obras</h1>
                        <p class="text-sm text-gray-600">Personal de Obras - Torre General</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <!-- Indicador de Tareas -->
                    <div class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-amber-50 text-amber-800">
                        <i class="fas fa-clipboard-list"></i>
                        <span class="font-medium"><?php echo $metricas_mes['tareas_pendientes']; ?> tareas pendientes</span>
                        <span class="text-sm">• <?php echo $metricas_mes['tareas_completadas']; ?> completadas</span>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="font-semibold"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                            <p class="text-sm text-gray-600">Personal de Obras</p>
                        </div>
                        <div class="relative">
                            <button id="user-menu" class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-500 to-yellow-600 text-white flex items-center justify-center">
                                <i class="fas fa-user"></i>
                            </button>
                            <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 z-50 border border-gray-200">
                                <a href="perfil.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Mi Perfil
                                </a>
                                <a href="../tareas.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-tasks mr-2"></i>Mis Tareas
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
        <!-- Encabezado con Métricas -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido, <?php echo explode(' ', $usuario['nombre'])[0]; ?></h1>
            <p class="text-gray-600">Panel de control para mantenimiento y obras del edificio</p>
            
            <!-- Métricas Principales -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="stat-card text-gray-800 rounded-xl p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm opacity-90 mb-1">Tareas Completadas</p>
                            <p class="text-2xl font-bold"><?php echo $metricas_mes['tareas_completadas']; ?></p>
                        </div>
                        <i class="fas fa-check-circle text-2xl opacity-50"></i>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm opacity-90">Este mes</p>
                    </div>
                </div>
                
                <div class="stat-card text-gray-800 rounded-xl p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm opacity-90 mb-1">Incidencias Resueltas</p>
                            <p class="text-2xl font-bold"><?php echo $metricas_mes['incidencias_resueltas']; ?></p>
                        </div>
                        <i class="fas fa-wrench text-2xl opacity-50"></i>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm opacity-90">Tiempo promedio: <?php echo $metricas_mes['tiempo_promedio_resolucion']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card text-gray-800 rounded-xl p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm opacity-90 mb-1">Materiales Solicitados</p>
                            <p class="text-2xl font-bold"><?php echo $metricas_mes['materiales_solicitados']; ?></p>
                        </div>
                        <i class="fas fa-box-open text-2xl opacity-50"></i>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm opacity-90">Unidades este mes</p>
                    </div>
                </div>
                
                <div class="stat-card text-gray-800 rounded-xl p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm opacity-90 mb-1">Tareas Pendientes</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo $metricas_mes['tareas_pendientes']; ?></p>
                        </div>
                        <i class="fas fa-exclamation-triangle text-2xl opacity-50"></i>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm opacity-90">Por realizar</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Columna Izquierda: Tareas Asignadas -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Tareas Asignadas -->
                <div class="personal-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">📋 Tareas Asignadas</h2>
                        <a href="../tareas.php" class="text-amber-600 hover:text-amber-800 font-medium text-sm">
                            Ver todas las tareas →
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($tareas_asignadas as $tarea): 
                            $clase_prioridad = 'priority-' . strtolower($tarea['prioridad']);
                            $clase_estado = 'estado-' . strtolower(str_replace(' ', '-', $tarea['estado']));
                        ?>
                        <div class="p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors <?php echo $clase_prioridad; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium"><?php echo $tarea['descripcion']; ?></p>
                                    <p class="text-sm text-gray-600"><?php echo $tarea['id']; ?></p>
                                </div>
                                <div class="flex flex-col items-end space-y-2">
                                    <span class="px-2 py-1 rounded text-xs font-medium 
                                        <?php echo $tarea['prioridad'] === 'Urgente' ? 'bg-red-100 text-red-800' : 
                                               ($tarea['prioridad'] === 'Alta' ? 'bg-orange-100 text-orange-800' : 
                                               ($tarea['prioridad'] === 'Media' ? 'bg-yellow-100 text-yellow-800' : 
                                               'bg-green-100 text-green-800')); ?>">
                                        <?php echo $tarea['prioridad']; ?>
                                    </span>
                                    <span class="px-2 py-1 rounded text-xs font-medium <?php echo $clase_estado; ?>">
                                        <?php echo $tarea['estado']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center mt-3">
                                <span class="text-sm text-gray-600">
                                    <i class="far fa-calendar mr-1"></i>Asignada: <?php echo $tarea['fecha_asignada']; ?>
                                </span>
                                <div class="flex space-x-2">
                                    <?php if ($tarea['estado'] !== 'Completado'): ?>
                                    <button class="text-xs text-amber-600 hover:text-amber-800" onclick="cambiarEstadoTarea('<?php echo $tarea['id']; ?>', 'progreso')">
                                        <i class="fas fa-play mr-1"></i>Iniciar
                                    </button>
                                    <button class="text-xs text-green-600 hover:text-green-800" onclick="cambiarEstadoTarea('<?php echo $tarea['id']; ?>', 'completado')">
                                        <i class="fas fa-check mr-1"></i>Completar
                                    </button>
                                    <?php endif; ?>
                                    <button class="text-xs text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-info-circle mr-1"></i>Detalles
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a href="../tareas/nueva.php" class="block w-full py-3 text-center bg-gradient-to-r from-amber-600 to-yellow-700 text-white rounded-xl hover:shadow-lg transition-all font-medium">
                            <i class="fas fa-plus mr-2"></i>Registrar Nueva Tarea
                        </a>
                    </div>
                </div>

                <!-- Incidencias Recientes -->
                <div class="personal-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">⚠️ Incidencias Reportadas</h2>
                        <a href="../incidencias.php" class="text-amber-600 hover:text-amber-800 font-medium text-sm">
                            Ver todas las incidencias →
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 text-gray-600">ID</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Tipo</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Descripción</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Reportado por</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incidencias_recientes as $incidencia): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium"><?php echo $incidencia['id']; ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium 
                                            <?php echo $incidencia['tipo'] === 'Eléctrica' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($incidencia['tipo'] === 'Fontanería' ? 'bg-blue-100 text-blue-800' : 
                                                   ($incidencia['tipo'] === 'Climatización' ? 'bg-cyan-100 text-cyan-800' : 
                                                   'bg-gray-100 text-gray-800')); ?>">
                                            <?php echo $incidencia['tipo']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <p class="text-sm"><?php echo $incidencia['descripcion']; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo $incidencia['fecha_reporte']; ?></p>
                                    </td>
                                    <td class="py-3 px-4"><?php echo $incidencia['reportado_por']; ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium 
                                            <?php echo $incidencia['estado'] === 'Resuelta' ? 'bg-green-100 text-green-800' : 
                                                   ($incidencia['estado'] === 'En reparación' ? 'bg-blue-100 text-blue-800' : 
                                                   ($incidencia['estado'] === 'Asignada' ? 'bg-yellow-100 text-yellow-800' : 
                                                   'bg-red-100 text-red-800')); ?>">
                                            <?php echo $incidencia['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Materiales y Acciones Rápidas -->
            <div class="space-y-6">
                <!-- Solicitudes de Materiales -->
                <div class="personal-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">📦 Solicitudes de Materiales</h2>
                        <a href="../materiales.php" class="text-amber-600 hover:text-amber-800 font-medium text-sm">
                            Ver todas →
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($solicitudes_materiales as $material): ?>
                        <div class="p-4 rounded-lg bg-gray-50 hover:bg-gray-100">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium"><?php echo $material['material']; ?></p>
                                    <p class="text-sm text-gray-600"><?php echo $material['id']; ?></p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-medium 
                                    <?php echo $material['estado'] === 'Aprobada' ? 'bg-green-100 text-green-800' : 
                                           ($material['estado'] === 'Pendiente' ? 'bg-yellow-100 text-yellow-800' : 
                                           'bg-red-100 text-red-800'); ?>">
                                    <?php echo $material['estado']; ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        <i class="fas fa-box mr-1"></i>
                                        <?php echo $material['cantidad']; ?> <?php echo $material['unidad']; ?>
                                    </p>
                                    <p class="text-xs text-gray-500">Solicitado por: <?php echo $material['solicitado_por']; ?></p>
                                </div>
                                <p class="text-xs text-gray-600"><?php echo $material['fecha_solicitud']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6">
                        <a href="../materiales/solicitar.php" class="block w-full py-3 text-center bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-xl hover:shadow-lg transition-all font-medium">
                            <i class="fas fa-plus mr-2"></i>Solicitar Nuevos Materiales
                        </a>
                    </div>
                </div>

                <!-- Reporte Rápido -->
                <div class="personal-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">🚨 Reporte Rápido</h2>
                    
                    <div class="space-y-4">
                        <button id="btn-reportar-incidencia" class="w-full p-4 text-left rounded-xl bg-red-50 border border-red-200 hover:bg-red-100 transition-colors">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Reportar Incidencia</p>
                                    <p class="text-sm text-gray-600">Problema o falla detectada</p>
                                </div>
                            </div>
                        </button>
                        
                        <button id="btn-registrar-actividad" class="w-full p-4 text-left rounded-xl bg-blue-50 border border-blue-200 hover:bg-blue-100 transition-colors">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-clipboard-check text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Registrar Actividad</p>
                                    <p class="text-sm text-gray-600">Tarea completada o en progreso</p>
                                </div>
                            </div>
                        </button>
                        
                        <button id="btn-solicitar-material" class="w-full p-4 text-left rounded-xl bg-green-50 border border-green-200 hover:bg-green-100 transition-colors">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-box-open text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Solicitar Material</p>
                                    <p class="text-sm text-gray-600">Nuevos insumos o herramientas</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="personal-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">⚡ Acciones Rápidas</h2>
                    <div class="space-y-3">
                        <a href="../tareas.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-tasks text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Mis Tareas</p>
                                    <p class="text-sm text-gray-600">Ver todas las tareas asignadas</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../incidencias.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-exclamation-triangle text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Incidencias</p>
                                    <p class="text-sm text-gray-600">Reportes y seguimiento</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../materiales.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-boxes text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Inventario</p>
                                    <p class="text-sm text-gray-600">Materiales y herramientas</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../reportes.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-chart-bar text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Reportes</p>
                                    <p class="text-sm text-gray-600">Estadísticas y métricas</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Reportar Incidencia -->
    <div id="modal-incidencia" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-6 max-w-md w-full">
            <h3 class="text-lg font-bold mb-4">Reportar Nueva Incidencia</h3>
            <form id="form-incidencia">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Incidencia</label>
                        <select class="w-full border rounded-lg p-3" required>
                            <option value="">Seleccionar tipo</option>
                            <option value="electricidad">Eléctrica</option>
                            <option value="fontaneria">Fontanería</option>
                            <option value="climatizacion">Climatización</option>
                            <option value="estructura">Estructural</option>
                            <option value="seguridad">Seguridad</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
                        <input type="text" class="w-full border rounded-lg p-3" placeholder="Ej: Piso 5, Oficina 502" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea class="w-full border rounded-lg p-3" rows="3" placeholder="Describa el problema..." required></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                        <div class="flex space-x-2">
                            <label class="flex-1">
                                <input type="radio" name="prioridad" value="baja" class="mr-2">
                                <span class="text-sm">Baja</span>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="prioridad" value="media" class="mr-2">
                                <span class="text-sm">Media</span>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="prioridad" value="alta" class="mr-2">
                                <span class="text-sm">Alta</span>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="prioridad" value="urgente" class="mr-2" checked>
                                <span class="text-sm">Urgente</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" class="px-4 py-2 text-gray-600 hover:text-gray-800" onclick="cerrarModal('modal-incidencia')">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Reportar Incidencia</button>
                </div>
            </form>
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

        // Funciones para modales
        function abrirModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function cerrarModal(id) {
            document.getElementById(id).classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Botones para abrir modales
        document.getElementById('btn-reportar-incidencia').addEventListener('click', () => abrirModal('modal-incidencia'));
        document.getElementById('btn-registrar-actividad').addEventListener('click', () => alert('Funcionalidad de registrar actividad - En desarrollo'));
        document.getElementById('btn-solicitar-material').addEventListener('click', () => alert('Funcionalidad de solicitar material - En desarrollo'));

        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('#modal-incidencia').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    cerrarModal('modal-incidencia');
                }
            });
        });

        // Enviar formulario de incidencia
        document.getElementById('form-incidencia').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Incidencia reportada exitosamente');
            cerrarModal('modal-incidencia');
            this.reset();
        });

        // Cambiar estado de tarea
        function cambiarEstadoTarea(id, estado) {
            if (confirm(`¿Cambiar estado de la tarea ${id} a ${estado}?`)) {
                alert(`Tarea ${id} marcada como ${estado}`);
                // Aquí iría la lógica para actualizar en la base de datos
                // Por ahora recargamos la página para simular el cambio
                setTimeout(() => location.reload(), 1000);
            }
        }
    </script>
</body>
</html>