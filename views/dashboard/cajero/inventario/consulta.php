<?php
/**
 * Consulta de Inventario - Módulo Cajero
 * Archivo   :   views/dashboard/cajero/inventario/consulta.php
 */

require_once '../../../../includes/seguridad.php';
require_once '../../../../includes/conexion.php';
require_once '../../../../includes/auditoria.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea cajero (por ahora permitimos para demostración)
// if ($usuario['rol'] !== 'Cajero') {
//     header('Location: ../../../../index.php?error=no_autorizado');
//     exit();
// }

// Obtener conexión PDO
$pdo = obtenerConexion();

// Obtener color del rol
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Cajero'");
$stmt->execute();
$rol_color = $stmt->fetchColumn();

// --- PROCESAR REGISTRO DE NUEVO PRODUCTO ---
$mensaje = null;
$tipo_mensaje = ''; // 'exito' o 'error'
$datos_formulario = []; // Para repoblar en caso de error

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_producto'])) {
    // Recoger y sanitizar datos
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria_id = intval($_POST['categoria_id'] ?? 0);
    $precio_compra = floatval(str_replace(',', '.', $_POST['precio_compra'] ?? '0'));
    $precio_venta = floatval(str_replace(',', '.', $_POST['precio_venta'] ?? '0'));
    $stock = intval($_POST['stock'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 12); // Por defecto 12
    $unidad_medida = $_POST['unidad_medida'] ?? 'unidad';
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($nombre)) {
        $errores[] = 'El nombre del producto es obligatorio.';
    }
    
    if (empty($descripcion)) {
        $errores[] = 'La descripción es obligatoria.';
    }
    
    if ($categoria_id <= 0) {
        $errores[] = 'Debe seleccionar una categoría válida.';
    }
    
    if ($precio_compra <= 0) {
        $errores[] = 'El precio de compra debe ser mayor a 0.';
    }
    
    if ($precio_venta <= 0) {
        $errores[] = 'El precio de venta debe ser mayor a 0.';
    }
    
    if ($precio_venta <= $precio_compra) {
        $errores[] = 'El precio de venta debe ser mayor al precio de compra.';
    }
    
    if ($stock < 0) {
        $errores[] = 'El stock no puede ser negativo.';
    }
    
    if ($stock_minimo < 0) {
        $errores[] = 'El stock mínimo no puede ser negativo.';
    }
    
    // Validar unidad de medida
    $unidades_permitidas = ['unidad', 'gramo', 'litro', 'paquete', 'botella'];
    if (!in_array($unidad_medida, $unidades_permitidas)) {
        $errores[] = 'La unidad de medida seleccionada no es válida.';
    }
    
    // Si no hay errores, proceder a insertar
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();
            
            // Generar código automático basado en la categoría
            // Obtener prefijo de categoría (primeras 3 letras en mayúscula)
            $stmt_cat = $pdo->prepare("SELECT nombre FROM categorias_productos WHERE id = ?");
            $stmt_cat->execute([$categoria_id]);
            $categoria_nombre = $stmt_cat->fetchColumn();
            $prefijo = strtoupper(substr($categoria_nombre, 0, 3));
            
            // Buscar el máximo código con ese prefijo
            $stmt_cod = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) as max_num 
                                       FROM productos 
                                       WHERE codigo LIKE ?");
            $stmt_cod->execute([$prefijo . '-%']);
            $max_num = $stmt_cod->fetchColumn();
            $siguiente_num = intval($max_num) + 1;
            
            // Formatear código: PREF-001
            $codigo = sprintf('%s-%03d', $prefijo, $siguiente_num);
            
            // Insertar producto
            $sql_insert = "INSERT INTO productos (
                codigo, nombre, descripcion, categoria_id, 
                precio_compra, precio_venta, stock, stock_minimo, 
                unidad_medida, estado
            ) VALUES (
                :codigo, :nombre, :descripcion, :categoria_id,
                :precio_compra, :precio_venta, :stock, :stock_minimo,
                :unidad_medida, 'activo'
            )";
            
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                ':codigo' => $codigo,
                ':nombre' => $nombre,
                ':descripcion' => $descripcion,
                ':categoria_id' => $categoria_id,
                ':precio_compra' => $precio_compra,
                ':precio_venta' => $precio_venta,
                ':stock' => $stock,
                ':stock_minimo' => $stock_minimo,
                ':unidad_medida' => $unidad_medida
            ]);
            
            $producto_id = $pdo->lastInsertId();
            
            // Registrar actividad
            registrarActividad('Registro producto', 'Inventario', "Registró nuevo producto: {$nombre} ({$codigo})");
            
            $pdo->commit();
            
            // Éxito
            $mensaje = "Producto <strong>{$nombre}</strong> registrado exitosamente con código <strong>{$codigo}</strong>.";
            $tipo_mensaje = 'exito';
            
            // Limpiar datos del formulario
            $datos_formulario = [];
            
            // Redirigir para evitar reenvío del formulario
            header('Location: consulta.php?registro=exito&producto=' . urlencode($nombre));
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = 'Error al registrar el producto: ' . $e->getMessage();
            $tipo_mensaje = 'error';
            
            // Guardar datos para repoblar
            $datos_formulario = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'categoria_id' => $categoria_id,
                'precio_compra' => $precio_compra,
                'precio_venta' => $precio_venta,
                'stock' => $stock,
                'stock_minimo' => $stock_minimo,
                'unidad_medida' => $unidad_medida
            ];
        }
    } else {
        // Hubo errores de validación
        $mensaje = implode('<br>', $errores);
        $tipo_mensaje = 'error';
        
        // Guardar datos para repoblar
        $datos_formulario = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'categoria_id' => $categoria_id,
            'precio_compra' => $precio_compra,
            'precio_venta' => $precio_venta,
            'stock' => $stock,
            'stock_minimo' => $stock_minimo,
            'unidad_medida' => $unidad_medida
        ];
    }
}

// Si llegó por redirección con éxito
if (isset($_GET['registro']) && $_GET['registro'] === 'exito') {
    $mensaje = isset($_GET['producto']) 
        ? "Producto <strong>" . htmlspecialchars($_GET['producto']) . "</strong> registrado exitosamente."
        : "Producto registrado exitosamente.";
    $tipo_mensaje = 'exito';
}

// Obtener parámetros de búsqueda y filtro (para la consulta normal)
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$categoria_id_filtro = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$stock_bajo = isset($_GET['stock_bajo']) ? true : false;

// Obtener categorías para el filtro y para el formulario
$stmt = $pdo->prepare("SELECT id, nombre FROM categorias_productos WHERE estado = 'activo' ORDER BY nombre");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Construir consulta SQL con filtros
$where_conditions = ["p.estado != 'inactivo'"];
$params = [];

// Filtro por búsqueda
if (!empty($busqueda)) {
    $where_conditions[] = "(p.nombre LIKE :busqueda OR p.codigo LIKE :busqueda)";
    $params[':busqueda'] = "%{$busqueda}%";
}

// Filtro por categoría
if ($categoria_id_filtro > 0) {
    $where_conditions[] = "p.categoria_id = :categoria_id";
    $params[':categoria_id'] = $categoria_id_filtro;
}

// Filtro por stock bajo
if ($stock_bajo) {
    $where_conditions[] = "p.stock <= p.stock_minimo AND p.stock > 0";
}

// Unir condiciones WHERE
$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Consulta para obtener productos
$sql = "
    SELECT 
        p.id,
        p.codigo,
        p.nombre,
        p.descripcion,
        cp.nombre as categoria,
        p.precio_venta,
        p.stock,
        p.stock_minimo,
        p.unidad_medida,
        p.estado,
        CASE 
            WHEN p.stock = 0 THEN 'agotado'
            WHEN p.stock <= p.stock_minimo THEN 'bajo'
            ELSE 'normal'
        END as estado_stock
    FROM productos p
    JOIN categorias_productos cp ON p.categoria_id = cp.id
    {$where_sql}
    ORDER BY p.stock ASC, p.nombre
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas del inventario
$sql_stats = "
    SELECT 
        COUNT(*) as total_productos,
        SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotados,
        SUM(CASE WHEN stock > 0 AND stock <= stock_minimo THEN 1 ELSE 0 END) as bajos,
        SUM(CASE WHEN stock > stock_minimo THEN 1 ELSE 0 END) as normales,
        SUM(stock * precio_venta) as valor_total
    FROM productos 
    WHERE estado != 'inactivo'
";

$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute();
$estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Registrar actividad de consulta (si no es una redirección POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['registrar_producto'])) {
    registrarActividad('Consulta inventario', 'Inventario', 'Consultó el inventario de productos');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Inventario - Torre General</title>
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
        .inventario-card {
            background: white;
            border: 1px solid rgba(209, 250, 229, 0.3);
            box-shadow: 0 4px 6px rgba(209, 250, 229, 0.1);
        }
        .stock-agotado {
            background-color: #fee2e2 !important;
            border-left: 4px solid #dc2626;
        }
        .stock-bajo {
            background-color: #fef3c7 !important;
            border-left: 4px solid #d97706;
        }
        .stock-normal {
            background-color: white;
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
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .modal-content {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
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
                        <i class="fas fa-boxes text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Consulta de Inventario</h1>
                        <p class="text-sm text-gray-600">Sistema Torre General | Cajera: <?php echo htmlspecialchars($usuario['nombre']); ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Volver al Dashboard
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
                        <p class="text-sm text-gray-600">Total Productos</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $estadisticas['total_productos'] ?? 0; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-box text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Valor Total</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($estadisticas['valor_total'] ?? 0, 2); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Stock Bajo</p>
                        <p class="text-2xl font-bold text-amber-600"><?php echo $estadisticas['bajos'] ?? 0; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-amber-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Agotados</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $estadisticas['agotados'] ?? 0; ?></p>
                    </div>
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Panel de Filtros -->
            <div class="lg:col-span-1">
                <div class="inventario-card rounded-xl p-6 sticky top-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-filter mr-2"></i> Filtros
                    </h3>
                    
                    <form method="GET" action="consulta.php" id="filtros-form">
                        <!-- Búsqueda -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Producto</label>
                            <div class="relative">
                                <input type="text" 
                                       name="busqueda" 
                                       value="<?php echo htmlspecialchars($busqueda); ?>" 
                                       placeholder="Código o nombre..." 
                                       class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                        
                        <!-- Filtro por Categoría -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                            <select name="categoria" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="0">Todas las categorías</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" <?php echo $categoria_id_filtro == $categoria['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Filtro por Stock -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Estado de Stock</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="stock_bajo" 
                                           value="1" 
                                           <?php echo $stock_bajo ? 'checked' : ''; ?>
                                           class="mr-2 h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                    <span class="text-sm text-gray-700">Solo stock bajo (≤5 unidades)</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="space-y-3">
                            <button type="submit" class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-lg hover:shadow-lg transition-all flex items-center justify-center">
                                <i class="fas fa-search mr-2"></i> Aplicar Filtros
                            </button>
                            
                            <?php if (!empty($busqueda) || $categoria_id_filtro > 0 || $stock_bajo): ?>
                            <a href="consulta.php" class="w-full px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all flex items-center justify-center">
                                <i class="fas fa-times mr-2"></i> Limpiar Filtros
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Información del Filtro -->
                        <?php if (!empty($busqueda) || $categoria_id_filtro > 0 || $stock_bajo): ?>
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-600 mb-2">Filtros activos:</p>
                            <div class="space-y-1">
                                <?php if (!empty($busqueda)): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                    <i class="fas fa-search mr-1"></i> "<?php echo htmlspecialchars($busqueda); ?>"
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($categoria_id_filtro > 0): 
                                    $categoria_nombre = '';
                                    foreach ($categorias as $categoria) {
                                        if ($categoria['id'] == $categoria_id_filtro) {
                                            $categoria_nombre = $categoria['nombre'];
                                            break;
                                        }
                                    }
                                ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                    <i class="fas fa-tag mr-1"></i> <?php echo htmlspecialchars($categoria_nombre); ?>
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($stock_bajo): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Stock bajo
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </form>
                    
                    <!-- Botón para registrar nuevo producto -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <button onclick="abrirModalRegistro()" class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-semibold rounded-lg hover:shadow-lg transition-all flex items-center justify-center">
                            <i class="fas fa-plus-circle mr-2"></i> Registrar Nuevo Producto
                        </button>
                        <p class="text-xs text-gray-500 mt-2 text-center">
                            <i class="fas fa-info-circle mr-1"></i> Demostración - Luego solo para gerentes
                        </p>
                    </div>
                    
                    <!-- Leyenda de Estados -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Leyenda de Estados</h4>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-red-100 border border-red-300 mr-2"></div>
                                <span class="text-xs text-gray-600">Agotado (stock = 0)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-amber-100 border border-amber-300 mr-2"></div>
                                <span class="text-xs text-gray-600">Stock bajo (≤5 unidades)</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-4 h-4 bg-white border border-gray-300 mr-2"></div>
                                <span class="text-xs text-gray-600">Stock normal (>5 unidades)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Productos -->
            <div class="lg:col-span-3">
                <div class="inventario-card rounded-xl p-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Lista de Productos</h2>
                            <p class="text-sm text-gray-600">
                                Mostrando <?php echo count($productos); ?> producto(s)
                                <?php if (!empty($busqueda) || $categoria_id_filtro > 0 || $stock_bajo): ?>
                                <span class="text-green-600"> (filtrados)</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="mt-4 md:mt-0 flex space-x-3">
                            <button onclick="exportarInventario()" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-lg hover:shadow-lg transition-all flex items-center">
                                <i class="fas fa-download mr-2"></i> Exportar
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($productos)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-box-open text-gray-300 text-5xl mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-600 mb-2">No se encontraron productos</h3>
                            <p class="text-gray-500 mb-4">
                                <?php if (!empty($busqueda) || $categoria_id_filtro > 0 || $stock_bajo): ?>
                                Intenta con otros criterios de búsqueda o <a href="consulta.php" class="text-green-600 hover:text-green-800">limpia los filtros</a>.
                                <?php else: ?>
                                No hay productos activos en el inventario.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto scrollbar-thin">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Código</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Producto</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Categoría</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Precio</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Stock</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $producto): 
                                        $clase_stock = '';
                                        $texto_estado = '';
                                        $icono_estado = '';
                                        
                                        if ($producto['estado_stock'] == 'agotado') {
                                            $clase_stock = 'stock-agotado';
                                            $texto_estado = 'Agotado';
                                            $icono_estado = 'fas fa-times-circle text-red-500';
                                        } elseif ($producto['estado_stock'] == 'bajo') {
                                            $clase_stock = 'stock-bajo';
                                            $texto_estado = 'Bajo';
                                            $icono_estado = 'fas fa-exclamation-triangle text-amber-500';
                                        } else {
                                            $clase_stock = 'stock-normal';
                                            $texto_estado = 'Normal';
                                            $icono_estado = 'fas fa-check-circle text-green-500';
                                        }
                                    ?>
                                    <tr class="border-b hover:bg-gray-50 <?php echo $clase_stock; ?>">
                                        <td class="py-3 px-4">
                                            <span class="font-mono text-sm font-medium"><?php echo htmlspecialchars($producto['codigo']); ?></span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div>
                                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($producto['nombre']); ?></p>
                                                <?php if (!empty($producto['descripcion'])): ?>
                                                <p class="text-xs text-gray-600 truncate max-w-xs"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-sm text-gray-700"><?php echo htmlspecialchars($producto['categoria']); ?></span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="font-bold text-green-600"><?php echo number_format($producto['precio_venta'], 2); ?></span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center">
                                                <span class="font-medium <?php echo $producto['stock'] == 0 ? 'text-red-600' : ($producto['stock'] <= 5 ? 'text-amber-600' : 'text-gray-800'); ?>">
                                                    <?php echo $producto['stock']; ?>
                                                </span>
                                                <span class="text-xs text-gray-500 ml-1"><?php echo $producto['unidad_medida']; ?></span>
                                                
                                                <?php if ($producto['stock'] <= $producto['stock_minimo'] && $producto['stock'] > 0): ?>
                                                <span class="ml-2 text-xs text-amber-600">
                                                    (mín: <?php echo $producto['stock_minimo']; ?>)
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center">
                                                <i class="<?php echo $icono_estado; ?> mr-2"></i>
                                                <span class="text-sm font-medium">
                                                    <?php echo $texto_estado; ?>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Resumen al pie -->
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Productos agotados</p>
                                    <p class="text-xl font-bold text-red-600">
                                        <?php 
                                            $agotados = array_filter($productos, function($p) {
                                                return $p['estado_stock'] == 'agotado';
                                            });
                                            echo count($agotados);
                                        ?>
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Productos con stock bajo</p>
                                    <p class="text-xl font-bold text-amber-600">
                                        <?php 
                                            $bajos = array_filter($productos, function($p) {
                                                return $p['estado_stock'] == 'bajo';
                                            });
                                            echo count($bajos);
                                        ?>
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">Valor inventario filtrado</p>
                                    <p class="text-xl font-bold text-green-600">
                                        <?php 
                                            $valor_total = array_sum(array_map(function($p) {
                                                return $p['stock'] * $p['precio_venta'];
                                            }, $productos));
                                            echo number_format($valor_total, 2);
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Información Adicional -->
                <div class="inventario-card rounded-xl p-6 mt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Información del Inventario</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-info-circle text-green-600 mr-2"></i>
                                Política de Stock
                            </h4>
                            <p class="text-sm text-gray-600">
                                El stock mínimo está configurado en <strong>12 unidades</strong> para la mayoría de productos. 
                                Cuando el stock alcanza este nivel, el producto se marca como "Stock bajo".
                            </p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-info-circle text-green-600 mr-2"></i>
                                Productos Agotados
                            </h4>
                            <p class="text-sm text-gray-600">
                                Los productos agotados aparecen en rojo. Estos productos <strong>no están disponibles</strong> 
                                para venta en el sistema POS hasta que se reponga el inventario.
                            </p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-info-circle text-green-600 mr-2"></i>
                                Registro de Productos
                            </h4>
                            <p class="text-sm text-gray-600">
                                <strong>Demostración:</strong> Puede registrar nuevos productos usando el botón en el panel izquierdo. 
                                Esta función será exclusiva para gerentes en producción.
                            </p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-info-circle text-green-600 mr-2"></i>
                                Actualización Automática
                            </h4>
                            <p class="text-sm text-gray-600">
                                El inventario se actualiza automáticamente con cada venta. 
                                Los cambios reflejan el stock disponible en tiempo real.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Registrar Nuevo Producto -->
    <div id="modalRegistro" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="p-6">
                <!-- Encabezado -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Registrar Nuevo Producto</h3>
                        <p class="text-sm text-gray-600">Complete todos los campos obligatorios</p>
                    </div>
                    <button onclick="cerrarModalRegistro()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Mensajes de éxito/error -->
                <?php if ($mensaje && $tipo_mensaje === 'error'): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <div>
                            <h4 class="font-medium text-red-800">Error en el registro</h4>
                            <p class="text-sm text-red-600 mt-1"><?php echo $mensaje; ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Formulario -->
                <form method="POST" action="consulta.php" id="formRegistroProducto" onsubmit="return validarFormulario()">
                    <div class="space-y-4">
                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nombre del Producto <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="nombre" 
                                   value="<?php echo htmlspecialchars($datos_formulario['nombre'] ?? ''); ?>"
                                   required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <!-- Descripción -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción <span class="text-red-500">*</span>
                            </label>
                            <textarea name="descripcion" 
                                      rows="3"
                                      required
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"><?php echo htmlspecialchars($datos_formulario['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select name="categoria_id" 
                                    required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"
                                        <?php echo ($datos_formulario['categoria_id'] ?? 0) == $categoria['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Precios -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Precio de Compra (Bs) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500"></span>
                                    <input type="number" 
                                           name="precio_compra" 
                                           step="0.01" 
                                           min="0.01"
                                           value="<?php echo htmlspecialchars($datos_formulario['precio_compra'] ?? '0.00'); ?>"
                                           required
                                           class="w-full border border-gray-300 rounded-lg pl-8 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Precio de Venta (Bs) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500"></span>
                                    <input type="number" 
                                           name="precio_venta" 
                                           step="0.01" 
                                           min="0.01"
                                           value="<?php echo htmlspecialchars($datos_formulario['precio_venta'] ?? '0.00'); ?>"
                                           required
                                           class="w-full border border-gray-300 rounded-lg pl-8 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stock -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Stock Inicial
                                </label>
                                <input type="number" 
                                       name="stock" 
                                       min="0"
                                       value="<?php echo htmlspecialchars($datos_formulario['stock'] ?? '0'); ?>"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Stock Mínimo
                                </label>
                                <input type="number" 
                                       name="stock_minimo" 
                                       min="1"
                                       value="<?php echo htmlspecialchars($datos_formulario['stock_minimo'] ?? '12'); ?>"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>
                        
                        <!-- Unidad de Medida -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Unidad de Medida
                            </label>
                            <select name="unidad_medida" 
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="unidad" <?php echo ($datos_formulario['unidad_medida'] ?? 'unidad') == 'unidad' ? 'selected' : ''; ?>>Unidad</option>
                                <option value="gramo" <?php echo ($datos_formulario['unidad_medida'] ?? '') == 'gramo' ? 'selected' : ''; ?>>Gramo</option>
                                <option value="litro" <?php echo ($datos_formulario['unidad_medida'] ?? '') == 'litro' ? 'selected' : ''; ?>>Litro</option>
                                <option value="paquete" <?php echo ($datos_formulario['unidad_medida'] ?? '') == 'paquete' ? 'selected' : ''; ?>>Paquete</option>
                                <option value="botella" <?php echo ($datos_formulario['unidad_medida'] ?? '') == 'botella' ? 'selected' : ''; ?>>Botella</option>
                            </select>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                                <div>
                                    <p class="text-sm text-blue-800">
                                        <strong>Nota:</strong> El código del producto se generará automáticamente basado en la categoría seleccionada.
                                        Ejemplo: Para categoría "Bebidas Calientes" → código "BEB-001"
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones del formulario -->
                    <div class="flex justify-end space-x-3 mt-8 pt-6 border-t border-gray-200">
                        <button type="button" onclick="cerrarModalRegistro()" class="px-5 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" 
                                name="registrar_producto"
                                class="px-5 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-lg hover:shadow-lg transition-all flex items-center">
                            <i class="fas fa-check-circle mr-2"></i> Registrar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Función para exportar inventario a CSV
        function exportarInventario() {
            const filtros = [];
            
            <?php if (!empty($busqueda)): ?>
            filtros.push('Búsqueda: <?php echo htmlspecialchars($busqueda); ?>');
            <?php endif; ?>
            
            <?php if ($categoria_id_filtro > 0): 
                $categoria_nombre = '';
                foreach ($categorias as $categoria) {
                    if ($categoria['id'] == $categoria_id_filtro) {
                        $categoria_nombre = $categoria['nombre'];
                        break;
                    }
                }
            ?>
            filtros.push('Categoría: <?php echo htmlspecialchars($categoria_nombre); ?>');
            <?php endif; ?>
            
            <?php if ($stock_bajo): ?>
            filtros.push('Solo stock bajo');
            <?php endif; ?>
            
            let confirmacion = '¿Exportar inventario a CSV?\n\n';
            if (filtros.length > 0) {
                confirmacion += 'Filtros aplicados:\n';
                filtros.forEach(filtro => {
                    confirmacion += '• ' + filtro + '\n';
                });
                confirmacion += '\n';
            }
            confirmacion += 'Total productos: <?php echo count($productos); ?>';
            
            if (confirm(confirmacion)) {
                // Crear contenido CSV
                let csvContent = "data:text/csv;charset=utf-8,";
                
                // Encabezados
                csvContent += "Código,Producto,Categoría,Precio,Stock,Stock Mínimo,Unidad,Estado\r\n";
                
                // Datos
                <?php foreach ($productos as $producto): ?>
                csvContent += '"<?php echo $producto['codigo']; ?>",';
                csvContent += '"<?php echo str_replace('"', '""', $producto['nombre']); ?>",';
                csvContent += '"<?php echo $producto['categoria']; ?>",';
                csvContent += '"<?php echo $producto['precio_venta']; ?>",';
                csvContent += '"<?php echo $producto['stock']; ?>",';
                csvContent += '"<?php echo $producto['stock_minimo']; ?>",';
                csvContent += '"<?php echo $producto['unidad_medida']; ?>",';
                csvContent += '"<?php echo $producto['estado_stock']; ?>"\r\n';
                <?php endforeach; ?>
                
                // Crear enlace de descarga
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "inventario_<?php echo date('Y-m-d'); ?>.csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // Registrar actividad (simulada)
                console.log('Inventario exportado: <?php echo count($productos); ?> productos');
            }
        }
        
        // Funciones para el modal de registro
        function abrirModalRegistro() {
            document.getElementById('modalRegistro').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function cerrarModalRegistro() {
            document.getElementById('modalRegistro').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        // Validación del formulario antes de enviar
        function validarFormulario() {
            const precioCompra = parseFloat(document.querySelector('input[name="precio_compra"]').value);
            const precioVenta = parseFloat(document.querySelector('input[name="precio_venta"]').value);
            
            if (precioVenta <= precioCompra) {
                alert('Error: El precio de venta debe ser mayor al precio de compra.');
                return false;
            }
            
            if (precioCompra <= 0 || precioVenta <= 0) {
                alert('Error: Los precios deben ser mayores a 0.');
                return false;
            }
            
            return true;
        }
        
        // Mostrar modal automáticamente si hay mensaje de éxito (después de redirección)
        <?php if ($mensaje && $tipo_mensaje === 'exito'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar alerta de éxito
            alert('✅ <?php echo addslashes(strip_tags($mensaje)); ?>');
        });
        <?php endif; ?>
        
        // Mostrar modal automáticamente si hubo error en el POST
        <?php if ($mensaje && $tipo_mensaje === 'error'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            abrirModalRegistro();
        });
        <?php endif; ?>
        
        // Auto-enviar formulario al cambiar algunos filtros
        document.addEventListener('DOMContentLoaded', function() {
            const categoriaSelect = document.querySelector('select[name="categoria"]');
            const stockBajoCheckbox = document.querySelector('input[name="stock_bajo"]');
            
            // Auto-submit al cambiar categoría o checkbox
            [categoriaSelect, stockBajoCheckbox].forEach(element => {
                if (element) {
                    element.addEventListener('change', function() {
                        // Solo auto-submit si no hay búsqueda de texto
                        const busquedaInput = document.querySelector('input[name="busqueda"]');
                        if (!busquedaInput || busquedaInput.value.trim() === '') {
                            document.getElementById('filtros-form').submit();
                        }
                    });
                }
            });
            
            // Auto-focus en búsqueda si hay parámetros
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('busqueda')) {
                const busquedaInput = document.querySelector('input[name="busqueda"]');
                if (busquedaInput) {
                    busquedaInput.focus();
                    busquedaInput.select();
                }
            }
            
            // Mostrar advertencia si hay productos agotados
            const agotados = document.querySelectorAll('.stock-agotado');
            if (agotados.length > 0) {
                console.log(`⚠️ ${agotados.length} producto(s) agotado(s) en inventario`);
            }
            
            // Mostrar advertencia si hay productos con stock bajo
            const bajos = document.querySelectorAll('.stock-bajo');
            if (bajos.length > 0) {
                console.log(`⚠️ ${bajos.length} producto(s) con stock bajo`);
            }
        });
        
        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            // Ctrl + F: Enfocar búsqueda
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                const busquedaInput = document.querySelector('input[name="busqueda"]');
                if (busquedaInput) {
                    busquedaInput.focus();
                    busquedaInput.select();
                }
            }
            
            // Ctrl + E: Exportar
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportarInventario();
            }
            
            // Ctrl + N: Abrir modal de nuevo producto
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                abrirModalRegistro();
            }
            
            // ESC: Limpiar búsqueda si está enfocado o cerrar modal
            if (e.key === 'Escape') {
                const busquedaInput = document.querySelector('input[name="busqueda"]');
                if (document.activeElement === busquedaInput && busquedaInput.value) {
                    busquedaInput.value = '';
                    document.getElementById('filtros-form').submit();
                }
                
                // Cerrar modal si está abierto
                if (!document.getElementById('modalRegistro').classList.contains('hidden')) {
                    cerrarModalRegistro();
                }
            }
        });
    </script>
</body>
</html>