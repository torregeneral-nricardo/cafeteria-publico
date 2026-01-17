<?php
/**
 * 
 * Dashboard para Gerente
 * archivo   :  /views/dashboard/gerente/index.php
 * 
 */

require_once '../../../includes/seguridad.php';
require_once '../../../config/db.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea gerente
if ($usuario['rol'] !== 'Gerente') {
    header('Location: ../../../index.php?error=no_autorizado');
    exit();
}

// Obtener color del rol
// $pdo = new PDO("mysql:host=localhost;dbname=proyecto_sistema;charset=utf8mb4", "usuario", "password");
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Gerente'");
$stmt->execute();
$rol_color = $stmt->fetchColumn();

// Datos simulados para el dashboard
$metricas_mes = [
    'ventas_totales' => 32500.75,
    'ventas_mes_anterior' => 29850.50,
    'crecimiento' => 8.9,
    'ticket_promedio' => 45.60,
    'clientes_nuevos' => 42,
    'producto_top' => 'Café Especial'
];

$cajeros_rendimiento = [
    ['nombre' => 'María González', 'ventas' => 8500.25, 'clientes' => 186, 'ticket_prom' => 45.70],
    ['nombre' => 'Carlos Rodríguez', 'ventas' => 7800.50, 'clientes' => 172, 'ticket_prom' => 45.35],
    ['nombre' => 'Laura Martínez', 'ventas' => 9200.75, 'clientes' => 198, 'ticket_prom' => 46.47],
    ['nombre' => 'Pedro López', 'ventas' => 7000.25, 'clientes' => 155, 'ticket_prom' => 45.16]
];

$inventario_alerta = [
    ['producto' => 'Café Molido Premium', 'stock' => 5, 'minimo' => 10],
    ['producto' => 'Vasos Desechables 16oz', 'stock' => 12, 'minimo' => 25],
    ['producto' => 'Leche Almendra', 'stock' => 8, 'minimo' => 15],
    ['producto' => 'Azúcar Orgánica', 'stock' => 6, 'minimo' => 12]
];

$tareas_pendientes = [
    ['descripcion' => 'Revisar pedido semanal de insumos', 'prioridad' => 'alta', 'fecha' => 'Hoy'],
    ['descripcion' => 'Evaluar rendimiento cajeros septiembre', 'prioridad' => 'media', 'fecha' => '15/10'],
    ['descripcion' => 'Aprobar nuevo menú temporada', 'prioridad' => 'alta', 'fecha' => '18/10'],
    ['descripcion' => 'Reunión con proveedor café', 'prioridad' => 'baja', 'fecha' => '20/10']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Gerente - Torre General</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-rol: <?php echo $rol_color ?: '#E0E7FF'; ?>;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }
        .gerente-card {
            background: white;
            border: 1px solid rgba(224, 231, 255, 0.3);
            box-shadow: 0 4px 6px rgba(224, 231, 255, 0.1);
        }
        .stat-card {
            background: linear-gradient(135deg, var(--color-rol) 0%, #c7d2fe 100%);
        }
        .menu-item {
            background: rgba(224, 231, 255, 0.1);
            border: 1px solid rgba(224, 231, 255, 0.3);
        }
        .menu-item:hover {
            background: rgba(224, 231, 255, 0.2);
            transform: translateX(5px);
        }
        .priority-high {
            border-left: 4px solid #ef4444;
        }
        .priority-medium {
            border-left: 4px solid #f59e0b;
        }
        .priority-low {
            border-left: 4px solid #10b981;
        }
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior -->
    <nav class="bg-white shadow-md border-b border-blue-100">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Supervisión y Gestión</h1>
                        <p class="text-sm text-gray-600">Panel Gerencial - Torre General</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <!-- Indicador de Rendimiento -->
                    <div class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-blue-50 text-blue-800">
                        <i class="fas fa-arrow-up text-green-500"></i>
                        <span class="font-medium">+<?php echo $metricas_mes['crecimiento']; ?>%</span>
                        <span class="text-sm">vs mes anterior</span>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="font-semibold"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                            <p class="text-sm text-gray-600">Gerente</p>
                        </div>
                        <div class="relative">
                            <button id="user-menu" class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center">
                                <i class="fas fa-user"></i>
                            </button>
                            <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 z-50 border border-gray-200">
                                <a href="perfil.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Mi Perfil
                                </a>
                                <a href="../reportes.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-chart-bar mr-2"></i>Reportes
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
        <!-- Encabezado con KPIs -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido, <?php echo explode(' ', $usuario['nombre'])[0]; ?></h1>
            <p class="text-gray-600">Panel de control para supervisión de operaciones</p>
            
            <!-- KPIs Principales -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="stat-card text-white rounded-xl p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm opacity-90 mb-1">Ventas del Mes</p>
                            <p class="text-2xl font-bold">$<?php echo number_format($metricas_mes['ventas_totales'], 0); ?></p>
                        </div>
                        <div class="text-right">
                            <span class="text-sm px-2 py-1 rounded-full bg-white/20">+<?php echo $metricas_mes['crecimiento']; ?>%</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm opacity-90">vs $<?php echo number_format($metricas_mes['ventas_mes_anterior'], 0); ?> mes anterior</p>
                    </div>
                </div>
                
                <div class="stat-card text-white rounded-xl p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm opacity-90 mb-1">Ticket Promedio</p>
                            <p class="text-2xl font-bold">$<?php echo number_format($metricas_mes['ticket_promedio'], 2); ?></p>
                        </div>
                        <i class="fas fa-shopping-cart text-2xl opacity-50"></i>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm opacity-90">Por transacción</p>
                    </div>
                </div>
                
                <div class="stat-card text-white rounded-xl p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm opacity-90 mb-1">Clientes Nuevos</p>
                            <p class="text-2xl font-bold"><?php echo $metricas_mes['clientes_nuevos']; ?></p>
                        </div>
                        <i class="fas fa-user-plus text-2xl opacity-50"></i>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm opacity-90">Este mes</p>
                    </div>
                </div>
                
                <div class="stat-card text-white rounded-xl p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm opacity-90 mb-1">Producto Top</p>
                            <p class="text-lg font-bold truncate"><?php echo $metricas_mes['producto_top']; ?></p>
                        </div>
                        <i class="fas fa-star text-2xl opacity-50"></i>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm opacity-90">Más vendido</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Columna Izquierda -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Rendimiento de Cajeros -->
                <div class="gerente-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Rendimiento de Cajeros</h2>
                        <a href="../reportes/rendimiento.php" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                            Ver análisis completo →
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 text-gray-600">Cajero</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Ventas</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Clientes</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Ticket Prom.</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Rendimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cajeros_rendimiento as $cajero): 
                                    $rendimiento = ($cajero['ventas'] / 9200.75) * 100;
                                ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium"><?php echo $cajero['nombre']; ?></td>
                                    <td class="py-3 px-4 font-bold text-blue-600">$<?php echo number_format($cajero['ventas'], 2); ?></td>
                                    <td class="py-3 px-4"><?php echo $cajero['clientes']; ?></td>
                                    <td class="py-3 px-4">$<?php echo number_format($cajero['ticket_prom'], 2); ?></td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                                <div class="bg-blue-600 h-2 rounded-full" 
                                                     style="width: <?php echo min($rendimiento, 100); ?>%"></div>
                                            </div>
                                            <span class="text-sm"><?php echo number_format($rendimiento, 1); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Alertas de Inventario -->
                <div class="gerente-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">⚠️ Alertas de Inventario</h2>
                        <a href="../inventario.php" class="text-red-600 hover:text-red-800 font-medium text-sm">
                            Gestionar inventario →
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($inventario_alerta as $alerta): 
                            $porcentaje = ($alerta['stock'] / $alerta['minimo']) * 100;
                        ?>
                        <div class="flex items-center justify-between p-4 rounded-lg bg-red-50 border border-red-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium"><?php echo $alerta['producto']; ?></p>
                                    <p class="text-sm text-red-600">Stock crítico: <?php echo $alerta['stock']; ?> unidades</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="w-32 bg-red-200 rounded-full h-2 mb-2">
                                    <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo min($porcentaje, 100); ?>%"></div>
                                </div>
                                <p class="text-sm text-red-700">Mínimo: <?php echo $alerta['minimo']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha -->
            <div class="space-y-6">
                <!-- Tareas Pendientes -->
                <div class="gerente-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">📋 Tareas Pendientes</h2>
                        <a href="../tareas.php" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                            Ver todas →
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($tareas_pendientes as $tarea): ?>
                        <div class="p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors <?php echo 'priority-' . $tarea['prioridad']; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <p class="font-medium"><?php echo $tarea['descripcion']; ?></p>
                                <span class="px-2 py-1 rounded text-xs font-medium 
                                    <?php echo $tarea['prioridad'] === 'alta' ? 'bg-red-100 text-red-800' : 
                                           ($tarea['prioridad'] === 'media' ? 'bg-amber-100 text-amber-800' : 
                                           'bg-green-100 text-green-800'); ?>">
                                    <?php echo ucfirst($tarea['prioridad']); ?>
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">
                                    <i class="far fa-calendar mr-1"></i><?php echo $tarea['fecha']; ?>
                                </span>
                                <button class="text-sm text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-check mr-1"></i>Marcar
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="gerente-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">⚡ Acciones Rápidas</h2>
                    <div class="space-y-3">
                        <a href="../reportes/ventas.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-chart-bar text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Reporte de Ventas</p>
                                    <p class="text-sm text-gray-600">Análisis detallado</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../inventario.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-boxes text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Gestionar Inventario</p>
                                    <p class="text-sm text-gray-600">Stock y pedidos</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../configuracion/precios.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-tags text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Ajustar Precios</p>
                                    <p class="text-sm text-gray-600">Actualizar menú</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../personal/rendimiento.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Evaluar Personal</p>
                                    <p class="text-sm text-gray-600">Rendimiento equipo</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Resumen Financiero -->
                <div class="gerente-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">💰 Resumen Financiero</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <p class="text-gray-600">Ingresos Mes</p>
                            <p class="font-bold text-green-600">$<?php echo number_format($metricas_mes['ventas_totales'], 2); ?></p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-gray-600">Gastos Operativos</p>
                            <p class="font-bold text-red-600">$8,425.30</p>
                        </div>
                        <div class="flex justify-between items-center">
                            <p class="text-gray-600">Utilidad Neta</p>
                            <p class="font-bold text-blue-600">$24,075.45</p>
                        </div>
                        <div class="pt-4 border-t">
                            <p class="text-sm text-gray-600 text-center">Margen: 74.1%</p>
                        </div>
                    </div>
                </div>
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

        // Marcador de tareas
        document.querySelectorAll('button:contains("Marcar")').forEach(button => {
            button.addEventListener('click', function() {
                const tarea = this.closest('.priority-high, .priority-medium, .priority-low');
                tarea.style.opacity = '0.6';
                this.innerHTML = '<i class="fas fa-check-double mr-1"></i>Hecho';
                this.classList.remove('text-blue-600');
                this.classList.add('text-green-600');
                this.disabled = true;
            });
        });
    </script>
</body>
</html>