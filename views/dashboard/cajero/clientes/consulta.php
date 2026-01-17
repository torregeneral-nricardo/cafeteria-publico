<?php
/**
 * Consulta de Clientes - Módulo Cajero
 * Archivo: /views/dashboard/cajero/clientes/consulta.php
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

// Obtener parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$tipo_cliente = isset($_GET['tipo_cliente']) ? $_GET['tipo_cliente'] : '';
$estado_cliente = isset($_GET['estado']) ? $_GET['estado'] : 'activo';

// Configurar paginación
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$por_pagina = 15; // Número de clientes por página
$offset = ($pagina_actual - 1) * $por_pagina;

// Construir consulta con filtros
$where_conditions = [];
$params_count = [];  // Parámetros solo para COUNT
$params_main = [];   // Parámetros para consulta principal

// Filtro por búsqueda (cedula, nombre, apellido, email, telefono)
if (!empty($busqueda)) {
    $where_conditions[] = "(c.cedula LIKE :busqueda OR c.nombre LIKE :busqueda OR c.apellido LIKE :busqueda OR c.email LIKE :busqueda OR c.telefono LIKE :busqueda)";
    $params_count[':busqueda'] = "%{$busqueda}%";
    $params_main[':busqueda'] = "%{$busqueda}%";
}

// Filtro por tipo de cliente
if (!empty($tipo_cliente) && in_array($tipo_cliente, ['ocasional', 'frecuente', 'empresa'])) {
    $where_conditions[] = "c.tipo_cliente = :tipo_cliente";
    $params_count[':tipo_cliente'] = $tipo_cliente;
    $params_main[':tipo_cliente'] = $tipo_cliente;
}

// Filtro por estado
if (!empty($estado_cliente) && in_array($estado_cliente, ['activo', 'inactivo'])) {
    $where_conditions[] = "c.estado = :estado";
    $params_count[':estado'] = $estado_cliente;
    $params_main[':estado'] = $estado_cliente;
}

// Unir condiciones WHERE
$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Consulta para contar total de clientes (para paginación)
$sql_count = "
    SELECT COUNT(*) as total
    FROM clientes c
    {$where_sql}
";

$stmt_count = $pdo->prepare($sql_count);
if (!empty($params_count)) {
    $stmt_count->execute($params_count);
} else {
    $stmt_count->execute();
}
$total_clientes = $stmt_count->fetchColumn();
$total_paginas = ceil($total_clientes / $por_pagina);

// **SOLUCIÓN: Usar diferentes nombres para cada parámetro :cajero_id**
$sql = "
    SELECT 
        c.id,
        CONCAT(c.prefijo_cedula, '-', c.cedula) as cedula_completa,
        c.nombre,
        c.apellido,
        CONCAT(c.nombre, ' ', c.apellido) as nombre_completo,
        c.email,
        c.telefono,
        c.tipo_cliente,
        c.estado,
        c.fecha_registro,
        (SELECT COUNT(*) FROM ventas v WHERE v.cliente_id = c.id AND v.cajero_id = :cajero_id1) as total_compras,
        (SELECT COALESCE(SUM(total), 0) FROM ventas v WHERE v.cliente_id = c.id AND v.cajero_id = :cajero_id2) as total_gastado,
        (SELECT MAX(fecha_venta) FROM ventas v WHERE v.cliente_id = c.id AND v.cajero_id = :cajero_id3) as ultima_compra
    FROM clientes c
    {$where_sql}
    ORDER BY 
        CASE WHEN c.tipo_cliente = 'empresa' THEN 1
             WHEN c.tipo_cliente = 'frecuente' THEN 2
             ELSE 3
        END,
        c.nombre,
        c.apellido
    LIMIT :limit OFFSET :offset
";

// Agregar parámetros con nombres diferentes
$params_main[':cajero_id1'] = $cajero_id;
$params_main[':cajero_id2'] = $cajero_id;
$params_main[':cajero_id3'] = $cajero_id;
$params_main[':limit'] = $por_pagina;
$params_main[':offset'] = $offset;

$stmt = $pdo->prepare($sql);

// Bind parameters
foreach ($params_main as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}

$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Calcular estadísticas generales
$sql_stats = "
    SELECT 
        COUNT(*) as total_clientes,
        SUM(CASE WHEN tipo_cliente = 'ocasional' THEN 1 ELSE 0 END) as ocasionales,
        SUM(CASE WHEN tipo_cliente = 'frecuente' THEN 1 ELSE 0 END) as frecuentes,
        SUM(CASE WHEN tipo_cliente = 'empresa' THEN 1 ELSE 0 END) as empresas,
        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
    FROM clientes
";

$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute();
$estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Obtener tipos de cliente para el filtro
$tipos_cliente = [
    'ocasional' => 'Ocasional',
    'frecuente' => 'Frecuente',
    'empresa' => 'Empresa'
];

// Registrar actividad
registrarActividad('Consulta clientes', 'Clientes', 'Consultó la lista de clientes con filtros');

// Si se solicita exportación a CSV
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=clientes_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($output, [
        'Cédula', 'Nombre', 'Apellido', 'Email', 'Teléfono', 'Tipo Cliente', 
        'Estado', 'Total Compras', 'Total Gastado', 'Última Compra', 'Fecha Registro'
    ], ';');
    
    // Datos
    foreach ($clientes as $cliente) {
        fputcsv($output, [
            $cliente['cedula_completa'],
            $cliente['nombre'],
            $cliente['apellido'],
            $cliente['email'] ?? '',
            $cliente['telefono'] ?? '',
            ucfirst($cliente['tipo_cliente']),
            ucfirst($cliente['estado']),
            $cliente['total_compras'] ?? 0,
            number_format($cliente['total_gastado'] ?? 0, 2),
            $cliente['ultima_compra'] ? date('d/m/Y', strtotime($cliente['ultima_compra'])) : 'Nunca',
            date('d/m/Y', strtotime($cliente['fecha_registro']))
        ], ';');
    }
    
    fclose($output);
    exit();
}

// Procesar cambio de estado (activar/desactivar cliente)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $nuevo_estado = $_POST['nuevo_estado'] === 'activo' ? 'activo' : 'inactivo';
    
    // Verificar que el cliente existe y pertenece a ventas del cajero (seguridad adicional)
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM clientes c
        LEFT JOIN ventas v ON c.id = v.cliente_id
        WHERE c.id = :cliente_id AND (v.cajero_id IS NULL OR v.cajero_id = :cajero_id)
        LIMIT 1
    ");
    
    $stmt->execute([
        ':cliente_id' => $cliente_id,
        ':cajero_id' => $cajero_id
    ]);
    
    $cliente_valido = $stmt->fetch();
    
    if ($cliente_valido) {
        try {
            $stmt = $pdo->prepare("UPDATE clientes SET estado = :estado WHERE id = :id");
            $stmt->execute([
                ':estado' => $nuevo_estado,
                ':id' => $cliente_id
            ]);
            
            registrarActividad(
                'Cambio estado cliente', 
                'Clientes', 
                "Cambió estado del cliente ID {$cliente_id} a {$nuevo_estado}"
            );
            
            $_SESSION['mensaje_exito'] = "Estado del cliente actualizado correctamente.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al cambiar estado del cliente: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Cliente no encontrado o no autorizado.";
    }
    
    // Redirigir para evitar reenvío del formulario
    header('Location: consulta.php?' . http_build_query($_GET));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Clientes - Torre General</title>
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
        .clientes-card {
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
        .cliente-empresa {
            border-left: 4px solid #3b82f6;
        }
        .cliente-frecuente {
            border-left: 4px solid #10b981;
        }
        .cliente-ocasional {
            border-left: 4px solid #6b7280;
        }
        .pagina-activa {
            background-color: #10b981;
            color: white;
        }
        .badge-tipo {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .badge-empresa {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-frecuente {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-ocasional {
            background-color: #f3f4f6;
            color: #374151;
        }
        .badge-activo {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-inactivo {
            background-color: #fee2e2;
            color: #991b1b;
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
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Consulta de Clientes</h1>
                        <p class="text-sm text-gray-600">Sistema Torre General | Cajero: <?php echo htmlspecialchars($usuario['nombre']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Volver al Dashboard
                    </a>
                    <a href="registro.php" class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-lg hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-user-plus mr-2"></i> Nuevo Cliente
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mensajes de alerta -->
    <div class="container mx-auto px-6 pt-6">
        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800"><?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contenido Principal -->
    <div class="container mx-auto px-6 py-6">
        <!-- Estadísticas Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Clientes</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($estadisticas['total_clientes'] ?? 0); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Clientes Activos</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($estadisticas['activos'] ?? 0); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-check text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Clientes Frecuentes</p>
                        <p class="text-2xl font-bold text-emerald-600"><?php echo number_format($estadisticas['frecuentes'] ?? 0); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-star text-emerald-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Empresas</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo number_format($estadisticas['empresas'] ?? 0); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-building text-blue-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Panel de Filtros -->
            <div class="lg:col-span-1">
                <div class="clientes-card rounded-xl p-6 sticky top-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-filter mr-2"></i> Filtros de Búsqueda
                    </h3>
                    
                    <form method="GET" action="consulta.php" id="filtros-form">
                        <!-- Búsqueda General -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Cliente</label>
                            <div class="relative">
                                <input type="text" 
                                       name="busqueda" 
                                       value="<?php echo htmlspecialchars($busqueda); ?>" 
                                       placeholder="Cédula, nombre, email, teléfono..." 
                                       class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Filtro por Tipo de Cliente -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Cliente</label>
                            <select name="tipo_cliente" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Todos los tipos</option>
                                <?php foreach ($tipos_cliente as $valor => $texto): ?>
                                    <option value="<?php echo $valor; ?>" <?php echo $tipo_cliente == $valor ? 'selected' : ''; ?>>
                                        <?php echo $texto; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Filtro por Estado -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="estado" 
                                           value="activo" 
                                           <?php echo $estado_cliente == 'activo' ? 'checked' : ''; ?>
                                           class="mr-2 h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                    <span class="text-sm text-gray-700">Activos</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="estado" 
                                           value="inactivo" 
                                           <?php echo $estado_cliente == 'inactivo' ? 'checked' : ''; ?>
                                           class="mr-2 h-4 w-4 text-red-600 border-gray-300 focus:ring-red-500">
                                    <span class="text-sm text-gray-700">Inactivos</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="estado" 
                                           value="" 
                                           <?php echo $estado_cliente == '' ? 'checked' : ''; ?>
                                           class="mr-2 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700">Todos</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="space-y-3">
                            <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-lg hover:shadow-lg transition-all flex items-center justify-center">
                                <i class="fas fa-search mr-2"></i> Aplicar Filtros
                            </button>
                            
                            <?php if (!empty($busqueda) || !empty($tipo_cliente) || !empty($estado_cliente)): ?>
                            <a href="consulta.php" class="w-full px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all flex items-center justify-center">
                                <i class="fas fa-times mr-2"></i> Limpiar Filtros
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Información del Filtro -->
                        <?php if (!empty($busqueda) || !empty($tipo_cliente) || !empty($estado_cliente)): ?>
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-600 mb-2">Filtros activos:</p>
                            <div class="space-y-1">
                                <?php if (!empty($busqueda)): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                    <i class="fas fa-search mr-1"></i> "<?php echo htmlspecialchars($busqueda); ?>"
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($tipo_cliente)): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                    <i class="fas fa-tag mr-1"></i> <?php echo $tipos_cliente[$tipo_cliente] ?? $tipo_cliente; ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($estado_cliente)): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-<?php echo $estado_cliente == 'activo' ? 'green' : 'red'; ?>-100 text-<?php echo $estado_cliente == 'activo' ? 'green' : 'red'; ?>-800">
                                    <i class="fas fa-circle mr-1"></i> 
                                    <?php echo $estado_cliente == 'activo' ? 'Activos' : 'Inactivos'; ?>
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
                            <a href="consulta.php?exportar=csv&<?php echo http_build_query($_GET); ?>" 
                               class="block text-center px-3 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white text-sm font-medium rounded-lg hover:shadow-lg transition-all">
                                <i class="fas fa-download mr-1"></i> Exportar a CSV
                            </a>
                            <a href="consulta.php?tipo_cliente=frecuente&estado=activo" 
                               class="block text-center px-3 py-2 bg-gradient-to-r from-emerald-600 to-green-600 text-white text-sm font-medium rounded-lg hover:shadow-lg transition-all">
                                <i class="fas fa-star mr-1"></i> Ver Frecuentes
                            </a>
                            <a href="consulta.php?tipo_cliente=empresa&estado=activo" 
                               class="block text-center px-3 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-medium rounded-lg hover:shadow-lg transition-all">
                                <i class="fas fa-building mr-1"></i> Ver Empresas
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Clientes -->
            <div class="lg:col-span-3">
                <div class="clientes-card rounded-xl p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Lista de Clientes</h2>
                            <p class="text-sm text-gray-600">
                                Mostrando <?php echo count($clientes); ?> de <?php echo number_format($total_clientes); ?> cliente(s)
                                <?php if ($total_clientes > $por_pagina): ?>
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
                    
                    <?php if (empty($clientes)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-users text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-600 mb-2">No se encontraron clientes</h3>
                            <p class="text-gray-500 mb-4">
                                <?php if (!empty($busqueda) || !empty($tipo_cliente) || !empty($estado_cliente)): ?>
                                Intenta con otros criterios de búsqueda o <a href="consulta.php" class="text-green-600 hover:text-green-800">limpia los filtros</a>.
                                <?php else: ?>
                                No hay clientes registrados en el sistema.
                                <?php endif; ?>
                            </p>
                            <a href="registro.php" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-lg hover:shadow-lg transition-all">
                                <i class="fas fa-user-plus mr-2"></i> Registrar Primer Cliente
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto scrollbar-thin">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Cédula</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Cliente</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Contacto</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Tipo</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Compras</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Estado</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clientes as $cliente): 
                                        $clase_tipo = '';
                                        $badge_tipo_clase = '';
                                        
                                        switch ($cliente['tipo_cliente']) {
                                            case 'empresa':
                                                $clase_tipo = 'cliente-empresa';
                                                $badge_tipo_clase = 'badge-empresa';
                                                $icono_tipo = 'fa-building';
                                                break;
                                            case 'frecuente':
                                                $clase_tipo = 'cliente-frecuente';
                                                $badge_tipo_clase = 'badge-frecuente';
                                                $icono_tipo = 'fa-star';
                                                break;
                                            default:
                                                $clase_tipo = 'cliente-ocasional';
                                                $badge_tipo_clase = 'badge-ocasional';
                                                $icono_tipo = 'fa-user';
                                        }
                                        
                                        $badge_estado_clase = $cliente['estado'] == 'activo' ? 'badge-activo' : 'badge-inactivo';
                                        $icono_estado = $cliente['estado'] == 'activo' ? 'fa-check-circle' : 'fa-times-circle';
                                        
                                        // Formatear última compra
                                        $ultima_compra_texto = 'Nunca';
                                        if ($cliente['ultima_compra']) {
                                            $diferencia = time() - strtotime($cliente['ultima_compra']);
                                            if ($diferencia < 86400) { // menos de 1 día
                                                $ultima_compra_texto = 'Hoy';
                                            } elseif ($diferencia < 172800) { // menos de 2 días
                                                $ultima_compra_texto = 'Ayer';
                                            } elseif ($diferencia < 604800) { // menos de 1 semana
                                                $ultima_compra_texto = 'Esta semana';
                                            } else {
                                                $ultima_compra_texto = date('d/m/Y', strtotime($cliente['ultima_compra']));
                                            }
                                        }
                                    ?>
                                    <tr class="border-b hover:bg-gray-50 <?php echo $clase_tipo; ?>">
                                        <td class="py-3 px-4">
                                            <span class="font-mono text-sm font-medium text-gray-800"><?php echo $cliente['cedula_completa']; ?></span>
                                            <p class="text-xs text-gray-500">
                                                Registro: <?php echo date('d/m/Y', strtotime($cliente['fecha_registro'])); ?>
                                            </p>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div>
                                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($cliente['nombre_completo']); ?></p>
                                                <p class="text-xs text-gray-600">
                                                    <?php echo $cliente['total_compras']; ?> compras
                                                    • <?php echo number_format($cliente['total_gastado'], 2); ?> total
                                                </p>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div>
                                                <?php if ($cliente['email']): ?>
                                                <p class="text-sm text-gray-700 truncate max-w-xs">
                                                    <i class="fas fa-envelope text-gray-400 mr-1"></i>
                                                    <?php echo htmlspecialchars($cliente['email']); ?>
                                                </p>
                                                <?php endif; ?>
                                                <?php if ($cliente['telefono']): ?>
                                                <p class="text-sm text-gray-700">
                                                    <i class="fas fa-phone text-gray-400 mr-1"></i>
                                                    <?php echo htmlspecialchars($cliente['telefono']); ?>
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="badge-tipo <?php echo $badge_tipo_clase; ?>">
                                                <i class="fas <?php echo $icono_tipo; ?> mr-1"></i>
                                                <?php echo ucfirst($cliente['tipo_cliente']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-center">
                                                <p class="text-lg font-bold text-gray-800"><?php echo $cliente['total_compras']; ?></p>
                                                <p class="text-xs text-gray-500"><?php echo $ultima_compra_texto; ?></p>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="badge-tipo <?php echo $badge_estado_clase; ?>">
                                                <i class="fas <?php echo $icono_estado; ?> mr-1"></i>
                                                <?php echo ucfirst($cliente['estado']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex space-x-2">
                                                <button onclick="verDetalleCliente(<?php echo $cliente['id']; ?>)" 
                                                        class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center hover:bg-blue-200 transition-all"
                                                        title="Ver Detalles">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </button>
                                                <a href="editar.php?id=<?php echo $cliente['id']; ?>" 
                                                   class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center hover:bg-green-200 transition-all"
                                                   title="Editar Cliente">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </a>
                                                <form method="POST" action="consulta.php" class="inline" onsubmit="return confirmarCambioEstado(<?php echo $cliente['id']; ?>, '<?php echo $cliente['estado']; ?>')">
                                                    <input type="hidden" name="cliente_id" value="<?php echo $cliente['id']; ?>">
                                                    <input type="hidden" name="nuevo_estado" value="<?php echo $cliente['estado'] == 'activo' ? 'inactivo' : 'activo'; ?>">
                                                    <button type="submit" name="cambiar_estado" 
                                                            class="w-8 h-8 <?php echo $cliente['estado'] == 'activo' ? 'bg-red-100 text-red-600 hover:bg-red-200' : 'bg-green-100 text-green-600 hover:bg-green-200'; ?> rounded-full flex items-center justify-center transition-all"
                                                            title="<?php echo $cliente['estado'] == 'activo' ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="fas <?php echo $cliente['estado'] == 'activo' ? 'fa-user-slash' : 'fa-user-check'; ?> text-xs"></i>
                                                    </button>
                                                </form>
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
                                        • <?php echo number_format($total_clientes); ?> clientes en total
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
                <div class="clientes-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Distribución de Clientes</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-blue-600">Clientes Ocasionales</p>
                                    <p class="text-2xl font-bold text-blue-800"><?php echo number_format($estadisticas['ocasionales'] ?? 0); ?></p>
                                    <p class="text-xs text-blue-700">
                                        <?php echo $total_clientes > 0 ? number_format(($estadisticas['ocasionales'] / $total_clientes) * 100, 1) : 0; ?>% del total
                                    </p>
                                </div>
                                <div class="w-10 h-10 bg-blue-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-green-50 to-green-100 border border-green-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-green-600">Clientes Frecuentes</p>
                                    <p class="text-2xl font-bold text-green-800"><?php echo number_format($estadisticas['frecuentes'] ?? 0); ?></p>
                                    <p class="text-xs text-green-700">
                                        <?php echo $total_clientes > 0 ? number_format(($estadisticas['frecuentes'] / $total_clientes) * 100, 1) : 0; ?>% del total
                                    </p>
                                </div>
                                <div class="w-10 h-10 bg-green-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-star text-green-600"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-r from-indigo-50 to-indigo-100 border border-indigo-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-indigo-600">Clientes Empresa</p>
                                    <p class="text-2xl font-bold text-indigo-800"><?php echo number_format($estadisticas['empresas'] ?? 0); ?></p>
                                    <p class="text-xs text-indigo-700">
                                        <?php echo $total_clientes > 0 ? number_format(($estadisticas['empresas'] / $total_clientes) * 100, 1) : 0; ?>% del total
                                    </p>
                                </div>
                                <div class="w-10 h-10 bg-indigo-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-building text-indigo-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para detalles de cliente -->
    <div id="modal-detalle" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 class="text-xl font-bold text-gray-800">Detalles del Cliente</h3>
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

    <script>
        // Función para cambiar resultados por página
        function cambiarResultadosPorPagina(select) {
            const url = new URL(window.location.href);
            url.searchParams.set('por_pagina', select.value);
            url.searchParams.set('pagina', 1); // Volver a página 1
            window.location.href = url.toString();
        }
        
        // Función para confirmar cambio de estado
        function confirmarCambioEstado(clienteId, estadoActual) {
            const nuevoEstado = estadoActual === 'activo' ? 'inactivo' : 'activo';
            const accion = estadoActual === 'activo' ? 'desactivar' : 'activar';
            
            return confirm(`¿Está seguro de ${accion} este cliente?\n\nEsta acción cambiará el estado del cliente a "${nuevoEstado}".`);
        }
        
        // Función para ver detalles de cliente
        function verDetalleCliente(clienteId) {
            fetch(`detalle.php?id=${clienteId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modal-contenido').innerHTML = html;
                    document.getElementById('modal-detalle').classList.remove('hidden');
                })
                .catch(error => {
                    document.getElementById('modal-contenido').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i>
                            <p class="text-red-600">Error al cargar los detalles del cliente.</p>
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
        
        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // ESC: Cerrar modal
            if (e.key === 'Escape') {
                cerrarModalDetalle();
            }
            
            // Ctrl + E: Exportar a CSV
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                window.location.href = `consulta.php?exportar=csv&${new URLSearchParams(window.location.search)}`;
            }
            
            // Ctrl + N: Nuevo cliente
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                window.location.href = 'registro.php';
            }
        });
        
        // Auto-submit al cambiar algunos filtros
        document.addEventListener('DOMContentLoaded', function() {
            const tipoSelect = document.querySelector('select[name="tipo_cliente"]');
            const estadoRadios = document.querySelectorAll('input[name="estado"]');
            
            if (tipoSelect) {
                tipoSelect.addEventListener('change', function() {
                    // Auto-submit si no hay búsqueda de texto
                    const busquedaInput = document.querySelector('input[name="busqueda"]');
                    if (!busquedaInput || busquedaInput.value.trim() === '') {
                        document.getElementById('filtros-form').submit();
                    }
                });
            }
            
            estadoRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Auto-submit si no hay búsqueda de texto
                    const busquedaInput = document.querySelector('input[name="busqueda"]');
                    if (!busquedaInput || busquedaInput.value.trim() === '') {
                        document.getElementById('filtros-form').submit();
                    }
                });
            });
            
            // Mostrar número de resultados
            console.log(`👥 ${<?php echo $total_clientes; ?>} clientes encontrados con los filtros aplicados`);
        });
    </script>
</body>
</html>