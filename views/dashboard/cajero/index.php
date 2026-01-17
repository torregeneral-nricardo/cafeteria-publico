<?php
/**
 * Dashboard para Cajero - CON DATOS REALES
 * Archivo: /views/dashboard/cajero/index.php
 */
 
require_once '../../../includes/seguridad.php';
require_once '../../../includes/conexion.php';
require_once '../../../includes/auditoria.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea cajero
if ($usuario['rol'] !== 'Cajero') {
    header('Location: ../../../index.php?error=no_autorizado');
    exit();
}

// Obtener conexión PDO
$pdo = obtenerConexion();

// Obtener color del rol
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Cajero'");
$stmt->execute();
$rol_color = $stmt->fetchColumn();

// ============================================
// DATOS REALES DE LA BASE DE DATOS
// ============================================

$cajero_id = $usuario['id'];

// 1. ESTADO DE CAJA (datos reales)
$estado_caja = obtenerEstadoCaja($cajero_id);

// Si no hay caja abierta, creamos array vacío
if (!$estado_caja) {
    $estado_caja = [
        'abierta' => false,
        'monto_inicial' => 0.00,
        'ventas_hoy' => 0.00,
        'tarjeta_hoy' => 0.00,
        'pago_movil_hoy' => 0.00,
        'monto_esperado' => 0.00,
        'cajero_nombre' => $usuario['nombre']
    ];
} else {
    $estado_caja['abierta'] = true;
    $estado_caja['total_caja'] = $estado_caja['monto_inicial'] + $estado_caja['ventas_hoy'];
}

// 2. VENTAS RECIENTES DEL DÍA (últimas 5 ventas)
$stmt = $pdo->prepare("
    SELECT 
        v.id,
        v.numero_ticket as id_ticket,
        CONCAT(c.nombre, ' ', c.apellido) as cliente,
        COALESCE(CONCAT(c.nombre, ' ', c.apellido), 'Cliente Ocasional') as cliente_nombre,
        v.total,
        v.metodo_pago,
        DATE_FORMAT(v.fecha_venta, '%H:%i') as hora
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.cajero_id = :cajero_id 
        AND DATE(v.fecha_venta) = CURDATE()
        AND v.estado != 'anulada'
    ORDER BY v.fecha_venta DESC
    LIMIT 5
");

$stmt->execute([':cajero_id' => $cajero_id]);
$ventas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. PRODUCTOS MÁS VENDIDOS DEL DÍA (top 5)
$stmt = $pdo->prepare("
    SELECT 
        p.nombre,
        p.stock,
        SUM(dv.cantidad) as vendidos
    FROM detalle_ventas dv
    JOIN ventas v ON dv.venta_id = v.id
    JOIN productos p ON dv.producto_id = p.id
    WHERE v.cajero_id = :cajero_id 
        AND DATE(v.fecha_venta) = CURDATE()
        AND v.estado != 'anulada'
    GROUP BY p.id, p.nombre, p.stock
    ORDER BY vendidos DESC
    LIMIT 5
");

$stmt->execute([':cajero_id' => $cajero_id]);
$productos_populares = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. MÉTRICAS DEL DÍA (datos reales)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_ventas,
        COALESCE(AVG(total), 0) as ticket_promedio,
        COALESCE(SUM(total), 0) as total_ventado
    FROM ventas 
    WHERE cajero_id = :cajero_id 
        AND DATE(fecha_venta) = CURDATE()
        AND estado != 'anulada'
");

$stmt->execute([':cajero_id' => $cajero_id]);
$metricas = $stmt->fetch(PDO::FETCH_ASSOC);

// Calcular porcentaje de meta (supongamos meta diaria de 2000)
$meta_diaria = 2000.00;
$porcentaje_meta = $metricas['total_ventado'] > 0 ? 
    min(100, ($metricas['total_ventado'] / $meta_diaria) * 100) : 0;

// 5. VERIFICAR HORARIO DE TURNO (si está dentro del horario permitido)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as turno_activo
    FROM turnos_caja 
    WHERE cajero_id = :cajero_id 
        AND dia_semana = LOWER(DAYNAME(CURDATE()))
        AND hora_inicio <= TIME(NOW())
        AND hora_fin >= TIME(NOW())
        AND estado = 'activo'
");

$stmt->execute([':cajero_id' => $cajero_id]);
$turno_activo = $stmt->fetchColumn() > 0;

    // Simplemente establecer siempre $turno_activo = true
    $turno_activo = true;
    $tiene_turnos_configurados = false; // Para no mostrar alerta

// Registrar actividad de ingreso al dashboard
registrarActividad('Acceso dashboard', 'Dashboard Cajero', 'Ingresó al panel de control del cajero');

// Obtener hora actual para mostrar
$hora_actual = date('H:i');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cajero - Torre General</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-rol: <?php echo $rol_color ?: '#D1FAE5'; ?>;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }
        .cajero-card {
            background: white;
            border: 1px solid rgba(209, 250, 229, 0.3);
            box-shadow: 0 4px 6px rgba(209, 250, 229, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .cajero-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(209, 250, 229, 0.15);
        }
        .stat-card {
            background: linear-gradient(135deg, var(--color-rol) 0%, #a7f3d0 100%);
        }
        .menu-item {
            background: rgba(209, 250, 229, 0.1);
            border: 1px solid rgba(209, 250, 229, 0.3);
        }
        .menu-item:hover {
            background: rgba(209, 250, 229, 0.2);
            transform: translateX(5px);
        }
        .caja-status {
            border-left: 4px solid;
            border-left-color: <?php echo $estado_caja['abierta'] ? '#10b981' : '#ef4444'; ?>;
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior -->
    <nav class="bg-white shadow-md border-b border-green-100">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center pulse-animation">
                        <i class="fas fa-cash-register text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Punto de Venta - Cafetería</h1>
                        <p class="text-sm text-gray-600">Sistema Torre General | <span id="hora-actual"><?php echo $hora_actual; ?></span></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <!-- Indicador de Caja -->
                    <div class="flex items-center space-x-2 px-4 py-2 rounded-lg <?php echo $estado_caja['abierta'] ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
                        <div class="w-2 h-2 rounded-full <?php echo $estado_caja['abierta'] ? 'bg-green-500' : 'bg-red-500'; ?> animate-pulse"></div>
                        <span class="font-medium"><?php echo $estado_caja['abierta'] ? 'Caja Abierta' : 'Caja Cerrada'; ?></span>
                        <span class="text-sm">• <?php echo number_format($estado_caja['total_caja'] ?? 0, 2); ?></span>
                    </div>
                    
                    <!-- Indicador de Turno -->
                    <div class="flex items-center space-x-2 px-4 py-2 rounded-lg <?php echo $turno_activo ? 'bg-blue-50 text-blue-800' : 'bg-amber-50 text-amber-800'; ?>">
                        <i class="fas <?php echo $turno_activo ? 'fa-clock text-blue-500' : 'fa-ban text-amber-500'; ?>"></i>
                        <span class="font-medium"><?php echo $turno_activo ? 'En Turno' : 'Fuera de Turno'; ?></span>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="font-semibold"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                            <p class="text-sm text-gray-600">Cajero | ID: <?php echo $cajero_id; ?></p>
                        </div>
                        <div class="relative">
                            <button id="user-menu" class="w-10 h-10 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-white flex items-center justify-center hover:shadow-lg transition-shadow">
                                <i class="fas fa-user"></i>
                            </button>
                            <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 z-50 border border-gray-200">
                                <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Mi Perfil
                                </a>
                                <a href="caja/estado.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-cash-register mr-2"></i>Gestión de Caja
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
        <!-- Encabezado con Acciones Rápidas -->
        <div class="mb-8">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Bienvenida, <?php echo explode(' ', $usuario['nombre'])[0]; ?></h1>
                    <p class="text-gray-600">Panel de control para operaciones de venta | <?php echo date('d/m/Y'); ?></p>
                </div>
                
                <div class="flex flex-wrap gap-3">
                    <a href="ventas/nueva.php" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center <?php echo (!$estado_caja['abierta'] || !$turno_activo) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                       <?php echo (!$estado_caja['abierta'] || !$turno_activo) ? 'onclick="event.preventDefault(); alert(\'Debe tener caja abierta y estar en turno activo para realizar ventas.\');"' : ''; ?>>
                        <i class="fas fa-plus mr-2"></i>Nueva Venta
                    </a>
                    
                    <?php if ($estado_caja['abierta']): ?>
                        <a href="caja/cerrar.php" class="px-6 py-3 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                            <i class="fas fa-lock mr-2"></i>Cerrar Caja
                        </a>
                    <?php else: ?>
                        <a href="caja/abrir.php" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center <?php echo !$turno_activo ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                           <?php echo !$turno_activo ? 'onclick="event.preventDefault(); alert(\'Solo puede abrir caja durante su turno asignado.\');"' : ''; ?>>
                            <i class="fas fa-lock-open mr-2"></i>Abrir Caja
                        </a>
                    <?php endif; ?>
                    
                    <a href="ventas/historial.php" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-history mr-2"></i>Historial
                    </a>
                    
                    <a href="inventario/consulta.php" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-boxes mr-2"></i>Inventario
                    </a>
                </div>
            </div>
            
            
            <!-- Alertas importantes -->
            <div class="mt-4">
                <?php if (!$turno_activo): ?>
                    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-amber-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-amber-800">
                                    <strong>Fuera de turno:</strong> Actualmente no está en su horario de trabajo asignado. Algunas funciones pueden estar limitadas.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php elseif (!$estado_caja['abierta']): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mt-2">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-800">
                                    <strong>Caja cerrada:</strong> Debe abrir caja para comenzar a registrar ventas.
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Columna Izquierda: Estado y Acciones -->
            <div class="lg:col-span-2">
                <!-- Estado de Caja -->
                <div class="cajero-card rounded-2xl p-6 mb-6 caja-status">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Estado de Caja</h2>
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $estado_caja['abierta'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $estado_caja['abierta'] ? '● ABIERTA' : '● CERRADA'; ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Ventas Hoy</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo number_format($estado_caja['ventas_hoy'], 2); ?></p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Tarjeta Débito</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo number_format($estado_caja['tarjeta_hoy'], 2); ?></p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Pago Móvil</p>
                            <p class="text-2xl font-bold text-purple-600"><?php echo number_format($estado_caja['pago_movil_hoy'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <p class="text-lg font-semibold text-gray-800">Total Ventas del Día:</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($estado_caja['ventas_hoy'], 2); ?></p>
                            <!-- 
                            p class="text-3xl font-bold text-gray-900">
                            <?php 
                            //echo number_format($estado_caja['total_caja'] ?? 0, 2); 
                            ?></p
                            -->
                        </div>
                        <?php if ($estado_caja['abierta'] && isset($estado_caja['monto_esperado'])): ?>
                        <div class="mt-4 flex justify-between items-center text-sm">
                            <span class="text-gray-600">Monto esperado al cierre:</span>
                            <span class="font-semibold text-emerald-700"><?php echo number_format($estado_caja['monto_esperado'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ventas Recientes -->
                <div class="cajero-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Ventas Recientes</h2>
                        <a href="ventas/historial.php" class="text-green-600 hover:text-green-800 font-medium text-sm flex items-center">
                            Ver todas <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    
                    <?php if (empty($ventas_recientes)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-shopping-cart text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No hay ventas registradas hoy</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-3 px-4 text-gray-600">Ticket #</th>
                                        <th class="text-left py-3 px-4 text-gray-600">Cliente</th>
                                        <th class="text-left py-3 px-4 text-gray-600">Total</th>
                                        <th class="text-left py-3 px-4 text-gray-600">Método</th>
                                        <th class="text-left py-3 px-4 text-gray-600">Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas_recientes as $venta): 
                                        $cliente_display = !empty($venta['cliente_nombre']) ? $venta['cliente_nombre'] : 'Cliente Ocasional';
                                        $metodo_display = $venta['metodo_pago'] === 'tarjeta_debito' ? 'Tarjeta Débito' : 'Pago Móvil';
                                    ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium">
                                            <a href="ventas/detalle.php?id=<?php echo $venta['id']; ?>" class="text-green-600 hover:text-green-800">
                                                <?php echo $venta['id_ticket']; ?>
                                            </a>
                                        </td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($cliente_display); ?></td>
                                        <td class="py-3 px-4 font-bold text-green-600"><?php echo number_format($venta['total'], 2); ?></td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-1 rounded text-xs font-medium <?php echo $venta['metodo_pago'] === 'tarjeta_debito' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                                <?php echo $metodo_display; ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-gray-600"><?php echo $venta['hora']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna Derecha: Productos y Acciones Rápidas -->
            <div class="space-y-6">
                <!-- Productos Populares -->
                <div class="cajero-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Productos Más Vendidos Hoy</h2>
                    <?php if (empty($productos_populares)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar text-gray-300 text-3xl mb-2"></i>
                            <p class="text-gray-500 text-sm">No hay ventas registradas hoy</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($productos_populares as $producto): 
                                $stock_bajo = $producto['stock'] <= 5;
                            ?>
                            <div class="flex items-center justify-between p-3 rounded-lg <?php echo $stock_bajo ? 'bg-red-50 border border-red-100' : 'bg-gray-50 hover:bg-gray-100'; ?>">
                                <div class="flex-1">
                                    <p class="font-medium"><?php echo htmlspecialchars($producto['nombre']); ?></p>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <span class="mr-3">Stock: <?php echo $producto['stock']; ?></span>
                                        <?php if ($stock_bajo): ?>
                                            <span class="text-red-600 font-medium">
                                                <i class="fas fa-exclamation-circle mr-1"></i>Stock bajo
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-green-600"><?php echo $producto['vendidos']; ?></p>
                                    <p class="text-xs text-gray-500">vendidos</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Acciones Rápidas -->
                <div class="cajero-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Acciones Rápidas</h2>
                    <div class="space-y-3">
                        <a href="ventas/nueva.php" 
                           class="menu-item block p-4 rounded-xl transition-all <?php echo (!$estado_caja['abierta'] || !$turno_activo) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                           <?php echo (!$estado_caja['abierta'] || !$turno_activo) ? 'onclick="event.preventDefault();"' : ''; ?>>
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-cash-register text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Nueva Venta</p>
                                    <p class="text-sm text-gray-600">Registrar nueva venta</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="inventario/consulta.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-boxes text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Consultar Inventario</p>
                                    <p class="text-sm text-gray-600">Ver stock disponible</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="clientes/consulta.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-users text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Gestión de Clientes</p>
                                    <p class="text-sm text-gray-600">Registrar/consultar clientes</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Métricas Diarias -->
                <div class="cajero-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Métricas del Día</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">Total Ventas</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $metricas['total_ventas']; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Ticket Promedio</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo number_format($metricas['ticket_promedio'], 2); ?></p>
                            </div>
                        </div>
                        
                        <!-- Barra de progreso de meta -->
                        <div>
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Meta diaria</span>
                                <span><?php echo number_format($metricas['total_ventado'], 2); ?> / <?php echo number_format($meta_diaria, 2); ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full transition-all duration-500" 
                                     style="width: <?php echo $porcentaje_meta; ?>%"></div>
                            </div>
                            <p class="text-sm text-gray-600 text-center mt-2">
                                <?php if ($porcentaje_meta == 0): ?>
                                    Meta diaria: 0% completada
                                <?php else: ?>
                                    Meta diaria: <?php echo number_format($porcentaje_meta, 1); ?>% completada
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <!-- Resumen rápido -->
                        <div class="pt-4 border-t border-gray-200">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center p-2 bg-gray-50 rounded">
                                    <p class="text-xs text-gray-600">Total Vendido</p>
                                    <p class="font-bold text-lg text-green-600"><?php echo number_format($metricas['total_ventado'], 2); ?></p>
                                </div>
                                <div class="text-center p-2 bg-gray-50 rounded">
                                    <p class="text-xs text-gray-600">Última Venta</p>
                                    <p class="font-bold text-lg text-blue-600">
                                        <?php echo !empty($ventas_recientes) ? $ventas_recientes[0]['hora'] : '--:--'; ?>
                                    </p>
                                </div>
                            </div>
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

        // Actualizar hora actual cada minuto
        function actualizarHora() {
            const ahora = new Date();
            const hora = ahora.getHours().toString().padStart(2, '0');
            const minutos = ahora.getMinutes().toString().padStart(2, '0');
            document.getElementById('hora-actual').textContent = `${hora}:${minutos}`;
        }
        
        setInterval(actualizarHora, 60000);
        actualizarHora();

        // Auto-refresh de datos cada 60 segundos (opcional)
        let autoRefreshEnabled = true;
        let refreshInterval = 60000; // 60 segundos
        
        function actualizarDatosDashboard() {
            if (!autoRefreshEnabled) return;
            
            // Aquí podrías agregar una petición AJAX para actualizar datos específicos
            console.log('Dashboard actualizado: ' + new Date().toLocaleTimeString());
            
            // Por ahora solo recargamos la página cada 5 minutos para datos frescos
            // En una implementación real, usarías AJAX para actualizar solo partes específicas
        }
        
        // Iniciar auto-refresh (cada minuto)
        setInterval(actualizarDatosDashboard, refreshInterval);

        // Notificaciones de stock bajo
        document.addEventListener('DOMContentLoaded', function() {
            const productosStockBajo = document.querySelectorAll('.bg-red-50');
            if (productosStockBajo.length > 0) {
                console.log(`⚠️ ${productosStockBajo.length} producto(s) con stock bajo`);
            }
        });
    </script>
</body>
</html>