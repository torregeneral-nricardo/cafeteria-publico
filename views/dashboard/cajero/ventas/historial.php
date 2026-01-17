<?php
/**
 * Historial de Ventas - Módulo Cajero (Versión Mejorada)
 * Archivo: views/dashboard/cajero/ventas/historial.php
 */

require_once '../../../../includes/seguridad.php';
require_once '../../../../includes/conexion.php';
require_once '../../../../includes/auditoria.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea cajero
if ($usuario['rol'] !== 'Cajero') {
    header('Location: ../../../../index.php?error=no_autorizado');
    exit();
}

// Obtener conexión PDO
$pdo = obtenerConexion();

// Obtener color del rol
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Cajero'");
$stmt->execute();
$rol_color = $stmt->fetchColumn();

$cajero_id = $usuario['id'];

// Obtener parámetros de filtro
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01'); // Primer día del mes
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d'); // Hoy
$cliente_busqueda = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
$ticket_busqueda = isset($_GET['ticket']) ? trim($_GET['ticket']) : '';
$metodo_pago = isset($_GET['metodo_pago']) ? $_GET['metodo_pago'] : '';

// Validar fechas
if (!empty($fecha_desde) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde)) {
    $fecha_desde = date('Y-m-01');
}
if (!empty($fecha_hasta) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta)) {
    $fecha_hasta = date('Y-m-d');
}

// Configurar paginación
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$por_pagina = 15; // Número de ventas por página
$offset = ($pagina_actual - 1) * $por_pagina;

// Construir consulta con filtros
$where_conditions = ["v.cajero_id = :cajero_id", "v.estado != 'anulada'"];
$params = [':cajero_id' => $cajero_id];

// Filtro por fecha
if (!empty($fecha_desde) && !empty($fecha_hasta)) {
    $where_conditions[] = "DATE(v.fecha_venta) BETWEEN :fecha_desde AND :fecha_hasta";
    $params[':fecha_desde'] = $fecha_desde;
    $params[':fecha_hasta'] = $fecha_hasta;
}

// Filtro por cliente
if (!empty($cliente_busqueda)) {
    $where_conditions[] = "(c.nombre LIKE :cliente OR c.apellido LIKE :cliente OR CONCAT(c.nombre, ' ', c.apellido) LIKE :cliente OR c.cedula LIKE :cliente)";
    $params[':cliente'] = "%{$cliente_busqueda}%";
}

// Filtro por número de ticket
if (!empty($ticket_busqueda)) {
    $where_conditions[] = "v.numero_ticket LIKE :ticket";
    $params[':ticket'] = "%{$ticket_busqueda}%";
}

// Filtro por método de pago
if (!empty($metodo_pago) && in_array($metodo_pago, ['tarjeta_debito', 'pago_movil'])) {
    $where_conditions[] = "v.metodo_pago = :metodo_pago";
    $params[':metodo_pago'] = $metodo_pago;
}

// Unir condiciones WHERE
$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Consulta para contar total de ventas (para paginación)
$sql_count = "
    SELECT COUNT(*) as total
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    {$where_sql}
";

$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_ventas = $stmt_count->fetchColumn();
$total_paginas = ceil($total_ventas / $por_pagina);

// Consulta para obtener ventas con paginación
$sql = "
    SELECT 
        v.id,
        v.numero_ticket,
        v.fecha_venta,
        v.total,
        v.metodo_pago,
        v.referencia_pago,
        v.estado,
        COALESCE(CONCAT(c.nombre, ' ', c.apellido), 'Cliente Ocasional') as cliente_nombre,
        COALESCE(CONCAT(c.prefijo_cedula, '-', c.cedula), 'N/A') as cliente_cedula,
        (SELECT COUNT(*) FROM detalle_ventas dv WHERE dv.venta_id = v.id) as total_productos
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    {$where_sql}
    ORDER BY v.fecha_venta DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

// Agregar parámetros adicionales para LIMIT y OFFSET
$params[':limit'] = $por_pagina;
$params[':offset'] = $offset;

// Bind parameters (especialmente para LIMIT y OFFSET que necesitan PDO::PARAM_INT)
foreach ($params as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}

$stmt->execute();
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas
$sql_stats = "
    SELECT 
        COUNT(*) as total_ventas,
        COALESCE(SUM(total), 0) as total_monto,
        COALESCE(AVG(total), 0) as ticket_promedio,
        SUM(CASE WHEN metodo_pago = 'tarjeta_debito' THEN total ELSE 0 END) as total_tarjeta,
        SUM(CASE WHEN metodo_pago = 'pago_movil' THEN total ELSE 0 END) as total_pago_movil
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    {$where_sql}
";

$stmt_stats = $pdo->prepare($sql_stats);
// Reusar params sin limit/offset
unset($params[':limit'], $params[':offset']);
$stmt_stats->execute($params);
$estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Obtener últimos clientes para el filtro de autocompletado
$stmt_clientes = $pdo->prepare("
    SELECT DISTINCT 
        CONCAT(c.nombre, ' ', c.apellido) as nombre_completo,
        c.cedula
    FROM clientes c
    JOIN ventas v ON c.id = v.cliente_id
    WHERE v.cajero_id = :cajero_id
    ORDER BY c.nombre
    LIMIT 50
");
$stmt_clientes->execute([':cajero_id' => $cajero_id]);
$clientes_recientes = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

// Registrar actividad
registrarActividad('Consulta historial ventas', 'Ventas', 'Consultó el historial de ventas con filtros');

// Si se solicita exportación a CSV
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=historial_ventas_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, [
        'Ticket #', 'Fecha', 'Hora', 'Cliente', 'Cédula', 'Método Pago', 
        'Referencia', 'Productos', 'Total', 'Estado'
    ], ';');
    
    // Datos
    foreach ($ventas as $venta) {
        fputcsv($output, [
            $venta['numero_ticket'],
            date('d/m/Y', strtotime($venta['fecha_venta'])),
            date('H:i', strtotime($venta['fecha_venta'])),
            $venta['cliente_nombre'],
            $venta['cliente_cedula'],
            $venta['metodo_pago'] == 'tarjeta_debito' ? 'Tarjeta Débito' : 'Pago Móvil',
            $venta['referencia_pago'],
            $venta['total_productos'],
            number_format($venta['total'], 2),
            $venta['estado']
        ], ';');
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ventas - Torre General</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --color-rol: <?php echo $rol_color ?: '#D1FAE5'; ?>;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
        }
        .historial-card {
            background: white;
            border: 1px solid rgba(209, 250, 229, 0.3);
            box-shadow: 0 4px 6px rgba(209, 250, 229, 0.1);
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: #d1fae5 transparent;
        }
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: #d1fae5;
            border-radius: 3px;
        }
        .metodo-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .metodo-tarjeta {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .metodo-movil {
            background-color: #f3e8ff;
            color: #7c3aed;
        }
        .pagina-activa {
            background-color: #10b981;
            color: white;
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
                        <i class="fas fa-history text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Historial de Ventas</h1>
                        <p class="text-sm text-gray-600">Sistema Torre General | Cajero: <?php echo htmlspecialchars($usuario['nombre']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Volver al Dashboard
                    </a>
                    <a href="nueva.php" class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-lg hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-plus mr-2"></i> Nueva Venta
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container mx-auto px-6 py-6">
        <!-- Estadísticas Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Ventas</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($estadisticas['total_ventas'] ?? 0); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Monto Total</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($estadisticas['total_monto'] ?? 0, 2); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Ticket Promedio</p>
                        <p class="text-2xl font-bold text-purple-600"><?php echo number_format($estadisticas['ticket_promedio'] ?? 0, 2); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-line text-purple-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Ventas Filtradas</p>
                        <p class="text-2xl font-bold text-amber-600"><?php echo number_format($total_ventas); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-filter text-amber-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Panel de Filtros -->
            <div class="lg:col-span-1">
                <div class="historial-card rounded-xl p-6 sticky top-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-filter mr-2"></i> Filtros Avanzados
                    </h3>
                    
                    <form method="GET" action="historial.php" id="filtros-form">
                        <!-- Rango de Fechas -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rango de Fechas</label>
                            <div class="space-y-2">
                                <input type="text" 
                                       name="fecha_desde" 
                                       value="<?php echo htmlspecialchars($fecha_desde); ?>" 
                                       placeholder="Desde" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 flatpickr"
                                       data-date-format="Y-m-d">
                                <input type="text" 
                                       name="fecha_hasta" 
                                       value="<?php echo htmlspecialchars($fecha_hasta); ?>" 
                                       placeholder="Hasta" 
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 flatpickr"
                                       data-date-format="Y-m-d">
                            </div>
                        </div>
                        
                        <!-- Búsqueda por Cliente -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Cliente</label>
                            <div class="relative">
                                <input type="text" 
                                       name="cliente" 
                                       value="<?php echo htmlspecialchars($cliente_busqueda); ?>" 
                                       placeholder="Nombre, apellido o cédula..." 
                                       class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                       list="clientes-lista">
                                <i class="fas fa-user absolute left-3 top-3 text-gray-400"></i>
                            </div>
                            <datalist id="clientes-lista">
                                <?php foreach ($clientes_recientes as $cliente): ?>
                                    <option value="<?php echo htmlspecialchars($cliente['nombre_completo']); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        
                        <!-- Búsqueda por Ticket -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Ticket</label>
                            <div class="relative">
                                <input type="text" 
                                       name="ticket" 
                                       value="<?php echo htmlspecialchars($ticket_busqueda); ?>" 
                                       placeholder="Número de ticket..." 
                                       class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <i class="fas fa-receipt absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Método de Pago -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago</label>
                            <select name="metodo_pago" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Todos los métodos</option>
                                <option value="tarjeta_debito" <?php echo $metodo_pago == 'tarjeta_debito' ? 'selected' : ''; ?>>Tarjeta Débito</option>
                                <option value="pago_movil" <?php echo $metodo_pago == 'pago_movil' ? 'selected' : ''; ?>>Pago Móvil</option>
                            </select>
                        </div>
                        
                        <!-- Botones -->
                        <div class="space-y-3">
                            <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-lg hover:shadow-lg transition-all flex items-center justify-center">
                                <i class="fas fa-search mr-2"></i> Aplicar Filtros
                            </button>
                            
                            <?php if (!empty($fecha_desde) || !empty($fecha_hasta) || !empty($cliente_busqueda) || !empty($ticket_busqueda) || !empty($metodo_pago)): ?>
                            <a href="historial.php" class="w-full px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all flex items-center justify-center">
                                <i class="fas fa-times mr-2"></i> Limpiar Filtros
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Información del Filtro -->
                        <?php if (!empty($fecha_desde) || !empty($fecha_hasta) || !empty($cliente_busqueda) || !empty($ticket_busqueda) || !empty($metodo_pago)): ?>
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-600 mb-2">Filtros activos:</p>
                            <div class="space-y-1">
                                <?php if (!empty($fecha_desde) && !empty($fecha_hasta)): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                    <i class="fas fa-calendar mr-1"></i> 
                                    <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> - <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($cliente_busqueda)): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                    <i class="fas fa-user mr-1"></i> Cliente: "<?php echo htmlspecialchars($cliente_busqueda); ?>"
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($ticket_busqueda)): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800">
                                    <i class="fas fa-receipt mr-1"></i> Ticket: "<?php echo htmlspecialchars($ticket_busqueda); ?>"
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($metodo_pago)): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-800">
                                    <i class="fas fa-credit-card mr-1"></i> 
                                    <?php echo $metodo_pago == 'tarjeta_debito' ? 'Tarjeta Débito' : 'Pago Móvil'; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                    
                    <!-- Acciones Rápidas -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Acciones Rápidas</h4>
                        <div class="space-y-2">
                            <a href="historial.php?exportar=csv&<?php echo http_build_query($_GET); ?>" 
                               class="block text-center px-3 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white text-sm font-medium rounded-lg hover:shadow-lg transition-all">
                                <i class="fas fa-download mr-1"></i> Exportar a CSV
                            </a>
                            <a href="historial.php?fecha_desde=<?php echo date('Y-m-d'); ?>&fecha_hasta=<?php echo date('Y-m-d'); ?>" 
                               class="block text-center px-3 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-medium rounded-lg hover:shadow-lg transition-all">
                                <i class="fas fa-calendar-day mr-1"></i> Ver solo hoy
                            </a>
                            <a href="historial.php?fecha_desde=<?php echo date('Y-m-01'); ?>&fecha_hasta=<?php echo date('Y-m-d'); ?>" 
                               class="block text-center px-3 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-medium rounded-lg hover:shadow-lg transition-all">
                                <i class="fas fa-calendar-alt mr-1"></i> Mes actual
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Ventas -->
            <div class="lg:col-span-3">
                <div class="historial-card rounded-xl p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Historial de Ventas</h2>
                            <p class="text-sm text-gray-600">
                                Mostrando <?php echo count($ventas); ?> de <?php echo number_format($total_ventas); ?> venta(s)
                                <?php if ($total_ventas > $por_pagina): ?>
                                (página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?>)
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="flex items-center space-x-3 mt-4 md:mt-0">
                            <!-- Selector de resultados por página -->
                            <div class="flex items-center">
                                <span class="text-sm text-gray-600 mr-2">Mostrar:</span>
                                <select onchange="cambiarResultadosPorPagina(this)" class="border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="15" <?php echo $por_pagina == 15 ? 'selected' : ''; ?>>15</option>
                                    <option value="30" <?php echo $por_pagina == 30 ? 'selected' : ''; ?>>30</option>
                                    <option value="50" <?php echo $por_pagina == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $por_pagina == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($ventas)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-shopping-cart text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-600 mb-2">No se encontraron ventas</h3>
                            <p class="text-gray-500 mb-4">
                                <?php if (!empty($fecha_desde) || !empty($cliente_busqueda) || !empty($ticket_busqueda) || !empty($metodo_pago)): ?>
                                Intenta con otros criterios de búsqueda o <a href="historial.php" class="text-green-600 hover:text-green-800">limpia los filtros</a>.
                                <?php else: ?>
                                No hay ventas registradas en el período seleccionado.
                                <?php endif; ?>
                            </p>
                            <a href="nueva.php" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-lg hover:shadow-lg transition-all">
                                <i class="fas fa-plus mr-2"></i> Registrar Primera Venta
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto scrollbar-thin">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Ticket #</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Fecha y Hora</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Cliente</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Método</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Productos</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Total</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas as $venta): 
                                        $metodo_texto = $venta['metodo_pago'] == 'tarjeta_debito' ? 'Tarjeta' : 'Pago Móvil';
                                        $metodo_clase = $venta['metodo_pago'] == 'tarjeta_debito' ? 'metodo-tarjeta' : 'metodo-movil';
                                        $fecha_formateada = date('d/m/Y', strtotime($venta['fecha_venta']));
                                        $hora_formateada = date('H:i', strtotime($venta['fecha_venta']));
                                    ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <span class="font-mono text-sm font-medium text-gray-800"><?php echo $venta['numero_ticket']; ?></span>
                                            <p class="text-xs text-gray-500">Ref: <?php echo $venta['referencia_pago']; ?></p>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div>
                                                <p class="text-sm font-medium text-gray-800"><?php echo $fecha_formateada; ?></p>
                                                <p class="text-xs text-gray-500"><?php echo $hora_formateada; ?></p>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div>
                                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($venta['cliente_nombre']); ?></p>
                                                <?php if ($venta['cliente_cedula'] != 'N/A'): ?>
                                                <p class="text-xs text-gray-500"><?php echo $venta['cliente_cedula']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="metodo-badge <?php echo $metodo_clase; ?>"><?php echo $metodo_texto; ?></span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center">
                                                <span class="text-sm text-gray-700"><?php echo $venta['total_productos']; ?></span>
                                                <span class="text-xs text-gray-500 ml-1">producto(s)</span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-lg font-bold text-green-600"><?php echo number_format($venta['total'], 2); ?></span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex space-x-2">
                                                <a href="ticket.php?id=<?php echo $venta['id']; ?>" 
                                                   target="_blank"
                                                   class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center hover:bg-blue-200 transition-all"
                                                   title="Ver Ticket">
                                                    <i class="fas fa-receipt text-xs"></i>
                                                </a>
                                                <button onclick="verDetalleVenta(<?php echo $venta['id']; ?>)" 
                                                        class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center hover:bg-green-200 transition-all"
                                                        title="Ver Detalles">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <div class="flex flex-col md:flex-row justify-between items-center">
                                <div class="mb-4 md:mb-0">
                                    <p class="text-sm text-gray-600">
                                        Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?> 
                                        • <?php echo number_format($total_ventas); ?> ventas en total
                                    </p>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <!-- Botón Primera Página -->
                                    <?php if ($pagina_actual > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>" 
                                       class="w-8 h-8 bg-gray-100 text-gray-700 rounded-full flex items-center justify-center hover:bg-gray-200 transition-all">
                                        <i class="fas fa-angle-double-left text-xs"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- Botón Anterior -->
                                    <?php if ($pagina_actual > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>" 
                                       class="w-8 h-8 bg-gray-100 text-gray-700 rounded-full flex items-center justify-center hover:bg-gray-200 transition-all">
                                        <i class="fas fa-angle-left text-xs"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- Números de Página -->
                                    <?php
                                    $pagina_inicio = max(1, $pagina_actual - 2);
                                    $pagina_fin = min($total_paginas, $pagina_actual + 2);
                                    
                                    for ($i = $pagina_inicio; $i <= $pagina_fin; $i++):
                                    ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" 
                                           class="w-8 h-8 rounded-full flex items-center justify-center transition-all <?php echo $i == $pagina_actual ? 'pagina-activa' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <!-- Botón Siguiente -->
                                    <?php if ($pagina_actual < $total_paginas): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>" 
                                       class="w-8 h-8 bg-gray-100 text-gray-700 rounded-full flex items-center justify-center hover:bg-gray-200 transition-all">
                                        <i class="fas fa-angle-right text-xs"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- Botón Última Página -->
                                    <?php if ($pagina_actual < $total_paginas): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $total_paginas])); ?>" 
                                       class="w-8 h-8 bg-gray-100 text-gray-700 rounded-full flex items-center justify-center hover:bg-gray-200 transition-all">
                                        <i class="fas fa-angle-double-right text-xs"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Estadísticas Detalladas -->
                <div class="historial-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Estadísticas del Período</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-blue-600">Total Ventas</p>
                                    <p class="text-2xl font-bold text-blue-800"><?php echo number_format($estadisticas['total_ventas'] ?? 0); ?></p>
                                </div>
                                <div class="w-10 h-10 bg-blue-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-shopping-cart text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-green-50 to-green-100 border border-green-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-green-600">Monto Total</p>
                                    <p class="text-2xl font-bold text-green-800"><?php echo number_format($estadisticas['total_monto'] ?? 0, 2); ?></p>
                                </div>
                                <div class="w-10 h-10 bg-green-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-dollar-sign text-green-600"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-purple-50 to-purple-100 border border-purple-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-purple-600">Ticket Promedio</p>
                                    <p class="text-2xl font-bold text-purple-800"><?php echo number_format($estadisticas['ticket_promedio'] ?? 0, 2); ?></p>
                                </div>
                                <div class="w-10 h-10 bg-purple-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-chart-line text-purple-600"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-amber-50 to-amber-100 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-amber-600">Diferencia por Método</p>
                                    <p class="text-lg font-bold text-amber-800">
                                        <?php 
                                            $total_tarjeta = $estadisticas['total_tarjeta'] ?? 0;
                                            $total_movil = $estadisticas['total_pago_movil'] ?? 0;
                                            echo number_format($total_tarjeta, 2) . ' / ' . number_format($total_movil, 2);
                                        ?>
                                    </p>
                                    <p class="text-xs text-amber-700">Tarjeta / Pago Móvil</p>
                                </div>
                                <div class="w-10 h-10 bg-amber-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-percentage text-amber-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles de venta -->
    <div id="modal-detalle" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold text-gray-800">Detalles de Venta</h3>
                <button onclick="cerrarModalDetalle()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="modal-contenido" class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- Contenido cargado dinámicamente -->
            </div>
            
            <div class="p-6 border-t bg-gray-50">
                <button onclick="cerrarModalDetalle()" 
                        class="w-full px-4 py-2 bg-gradient-to-r from-gray-600 to-gray-700 text-white font-semibold rounded-lg hover:shadow-lg transition-all">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        // Inicializar datepicker
        flatpickr(".flatpickr", {
            dateFormat: "Y-m-d",
            locale: "es",
            maxDate: "today"
        });
        
        // Función para cambiar resultados por página
        function cambiarResultadosPorPagina(select) {
            const url = new URL(window.location.href);
            url.searchParams.set('por_pagina', select.value);
            url.searchParams.set('pagina', 1); // Volver a página 1
            window.location.href = url.toString();
        }
        
        // Función para ver detalles de venta
        function verDetalleVenta(ventaId) {
            fetch(`detalle_venta.php?id=${ventaId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modal-contenido').innerHTML = html;
                    document.getElementById('modal-detalle').classList.remove('hidden');
                })
                .catch(error => {
                    document.getElementById('modal-contenido').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i>
                            <p class="text-red-600">Error al cargar los detalles de la venta.</p>
                            <p class="text-sm text-gray-500">${error.message}</p>
                        </div>
                    `;
                    document.getElementById('modal-detalle').classList.remove('hidden');
                });
        }
        
        // Función para cerrar modal
        function cerrarModalDetalle() {
            document.getElementById('modal-detalle').classList.add('hidden');
        }
        
        // Exportar a PDF (simulado)
        function exportarPDF() {
            alert('La exportación a PDF estará disponible en la próxima versión.');
        }
        
        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // ESC: Cerrar modal
            if (e.key === 'Escape') {
                cerrarModalDetalle();
            }
            
            // Ctrl + E: Exportar a CSV
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                window.location.href = `historial.php?exportar=csv&${new URLSearchParams(window.location.search)}`;
            }
        });
        
        // Auto-submit al cambiar algunos filtros
        document.addEventListener('DOMContentLoaded', function() {
            const metodoSelect = document.querySelector('select[name="metodo_pago"]');
            
            if (metodoSelect) {
                metodoSelect.addEventListener('change', function() {
                    // Auto-submit si no hay búsqueda de texto
                    const clienteInput = document.querySelector('input[name="cliente"]');
                    const ticketInput = document.querySelector('input[name="ticket"]');
                    
                    if ((!clienteInput || clienteInput.value.trim() === '') && 
                        (!ticketInput || ticketInput.value.trim() === '')) {
                        document.getElementById('filtros-form').submit();
                    }
                });
            }
            
            // Mostrar número de resultados
            console.log(`📊 ${<?php echo $total_ventas; ?>} ventas encontradas con los filtros aplicados`);
        });
    </script>
</body>
</html>