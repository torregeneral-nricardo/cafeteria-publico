<?php
/**
 * Cierre de Caja - Módulo Cajero
 * Archivo: /views/dashboard/cajero/caja/cerrar.php
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

// Obtener caja abierta actual
$stmt = $pdo->prepare("
    SELECT c.*, tc.nombre as turno_nombre
    FROM caja c
    LEFT JOIN turnos_caja tc ON c.turno_id = tc.id
    WHERE c.cajero_id = :cajero_id 
        AND c.estado = 'abierta'
    ORDER BY c.fecha_apertura DESC 
    LIMIT 1
");

$stmt->execute([':cajero_id' => $cajero_id]);
$caja_actual = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no hay caja abierta, redirigir
if (!$caja_actual) {
    $_SESSION['error'] = "No tiene una caja abierta para cerrar.";
    header('Location: ../index.php');
    exit();
}

// Obtener ventas del día para este cajero
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_ventas,
        SUM(total) as total_monto,
        SUM(CASE WHEN metodo_pago = 'tarjeta_debito' THEN total ELSE 0 END) as total_tarjeta,
        SUM(CASE WHEN metodo_pago = 'pago_movil' THEN total ELSE 0 END) as total_pago_movil
    FROM ventas 
    WHERE cajero_id = :cajero_id 
        AND DATE(fecha_venta) = CURDATE()
        AND estado = 'completada'
");

$stmt->execute([':cajero_id' => $cajero_id]);
$ventas_dia = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener detalles de las últimas ventas
$stmt = $pdo->prepare("
    SELECT 
        v.numero_ticket,
        v.total,
        v.metodo_pago,
        TIME(v.fecha_venta) as hora,
        COALESCE(CONCAT(c.nombre, ' ', c.apellido), 'Cliente Ocasional') as cliente
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.cajero_id = :cajero_id 
        AND DATE(v.fecha_venta) = CURDATE()
        AND v.estado = 'completada'
    ORDER BY v.fecha_venta DESC
    LIMIT 10
");

$stmt->execute([':cajero_id' => $cajero_id]);
$ultimas_ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar cierre de caja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cerrar_caja'])) {
    
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        // Calcular totales
        $monto_final = $ventas_dia['total_monto'] ?? 0.00;
        $diferencia = 0.00; // Siempre 0 porque no hay efectivo
        
        // Actualizar registro de caja
        $stmt = $pdo->prepare("
            UPDATE caja 
            SET 
                monto_final = :monto_final,
                diferencia = :diferencia,
                estado = 'cerrada',
                fecha_cierre = NOW(),
                observaciones = :observaciones
            WHERE id = :caja_id
        ");
        
        $stmt->execute([
            ':monto_final' => $monto_final,
            ':diferencia' => $diferencia,
            ':observaciones' => $observaciones,
            ':caja_id' => $caja_actual['id']
        ]);
        
        // Registrar actividad
        registrarActividad(
            'Caja cerrada', 
            'Caja', 
            "Cierre de caja. Total ventas: " . number_format($monto_final, 2),
            [
                'caja_id' => $caja_actual['id'],
                'total_ventas' => $monto_final,
                'total_transacciones' => $ventas_dia['total_ventas'] ?? 0
            ]
        );
        
        $pdo->commit();
        
        // Preparar datos para mostrar en resumen
        $_SESSION['cierre_exitoso'] = [
            'monto_final' => $monto_final,
            'total_ventas' => $ventas_dia['total_ventas'] ?? 0,
            'total_tarjeta' => $ventas_dia['total_tarjeta'] ?? 0,
            'total_pago_movil' => $ventas_dia['total_pago_movil'] ?? 0,
            'fecha_apertura' => $caja_actual['fecha_apertura'],
            'fecha_cierre' => date('Y-m-d H:i:s'),
            'observaciones' => $observaciones
        ];
        
        header('Location: cerrar.php?exito=1');
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Error al cerrar caja: " . $e->getMessage();
        header('Location: cerrar.php');
        exit();
    }
}

// Si hay éxito en la URL, mostrar resumen
$mostrar_resumen = isset($_GET['exito']) && $_GET['exito'] == 1;

// Registrar actividad de ingreso a la página
registrarActividad('Acceso cierre caja', 'Caja', 'Ingresó al formulario de cierre de caja');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerrar Caja - Torre General</title>
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
        .caja-card {
            background: white;
            border: 1px solid rgba(209, 250, 229, 0.3);
            box-shadow: 0 4px 6px rgba(209, 250, 229, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .caja-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(209, 250, 229, 0.15);
        }
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
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
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior -->
    <nav class="bg-white shadow-md border-b border-green-100">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center pulse-animation">
                        <i class="fas fa-lock text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Cierre de Caja</h1>
                        <p class="text-sm text-gray-600">Sistema Torre General | Cajero: <?php echo htmlspecialchars($usuario['nombre']); ?></p>
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

    <!-- Mensajes de alerta -->
    <div class="container mx-auto px-6 pt-6">
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
    </div>

    <!-- Contenido Principal -->
    <div class="container mx-auto px-6 py-6">
        <div class="max-w-4xl mx-auto">
            
            <?php if ($mostrar_resumen && isset($_SESSION['cierre_exitoso'])): 
                $resumen = $_SESSION['cierre_exitoso'];
                unset($_SESSION['cierre_exitoso']);
            ?>
            
            <!-- Resumen de Cierre Exitoso -->
            <div class="caja-card rounded-2xl p-6 mb-6">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-green-100 to-emerald-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-check-circle text-3xl text-green-600"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">¡Caja Cerrada Exitosamente!</h2>
                    <p class="text-gray-600">Resumen del día de trabajo</p>
                </div>
                
                <!-- Información General -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-800 mb-3">Horarios</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Apertura:</span>
                                <span class="font-medium"><?php echo date('H:i', strtotime($resumen['fecha_apertura'])); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Cierre:</span>
                                <span class="font-medium"><?php echo date('H:i', strtotime($resumen['fecha_cierre'])); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Duración:</span>
                                <span class="font-medium">
                                    <?php 
                                        $inicio = new DateTime($resumen['fecha_apertura']);
                                        $fin = new DateTime($resumen['fecha_cierre']);
                                        $intervalo = $inicio->diff($fin);
                                        echo $intervalo->format('%h h %i min');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-800 mb-3">Resumen de Ventas</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Ventas:</span>
                                <span class="font-medium"><?php echo $resumen['total_ventas']; ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tarjeta Débito:</span>
                                <span class="font-medium text-blue-600"><?php echo number_format($resumen['total_tarjeta'], 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Pago Móvil:</span>
                                <span class="font-medium text-purple-600"><?php echo number_format($resumen['total_pago_movil'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Total Final -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-100 border border-green-200 rounded-xl p-6 mb-8">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg">Total del Día</h3>
                            <p class="text-gray-600">Monto total de transacciones</p>
                        </div>
                        <div class="text-right mt-4 md:mt-0">
                            <p class="text-4xl font-bold text-green-700"><?php echo number_format($resumen['monto_final'], 2); ?></p>
                            <p class="text-sm text-gray-600">Diferencia: $0.00</p>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($resumen['observaciones'])): ?>
                <div class="mb-8">
                    <h3 class="font-semibold text-gray-800 mb-3">Observaciones</h3>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($resumen['observaciones'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Acciones -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
                        <a href="../index.php" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center justify-center">
                            <i class="fas fa-home mr-2"></i> Volver al Dashboard
                        </a>
                        <button onclick="window.print()" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center justify-center">
                            <i class="fas fa-print mr-2"></i> Imprimir Resumen
                        </button>
                        <a href="cerrar.php" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all flex items-center justify-center">
                            <i class="fas fa-redo mr-2"></i> Ver Otra Caja
                        </a>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            
            <!-- Formulario de Cierre de Caja -->
            <div class="caja-card rounded-2xl p-6 mb-6">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-green-100 to-emerald-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-cash-register text-3xl text-green-600"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Cierre de Caja Diaria</h2>
                    <p class="text-gray-600">Procedimiento para finalizar operaciones de venta</p>
                </div>
                
                <!-- Información de la Caja -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Información de la Caja</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-blue-800">Estado Actual</span>
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                    <i class="fas fa-lock-open mr-1"></i>Abierta
                                </span>
                            </div>
                            <p class="text-sm text-blue-600">
                                <i class="far fa-clock mr-1"></i>
                                Abierta desde: <?php echo date('H:i', strtotime($caja_actual['fecha_apertura'])); ?>
                            </p>
                            <?php if ($caja_actual['turno_nombre']): ?>
                            <p class="text-sm text-blue-600 mt-1">
                                <i class="fas fa-user-clock mr-1"></i>
                                Turno: <?php echo htmlspecialchars($caja_actual['turno_nombre']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-green-800">Monto Inicial</span>
                                <span class="font-bold text-green-700">$0.00</span>
                            </div>
                            <p class="text-sm text-green-600">
                                <i class="fas fa-info-circle mr-1"></i>
                                Sistema sin efectivo - Solo transacciones digitales
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Resumen de Ventas del Día -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Resumen de Ventas del Día</h3>
                    
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center p-3 bg-white rounded-lg">
                                <p class="text-sm text-gray-600">Total Ventas</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $ventas_dia['total_ventas'] ?? 0; ?></p>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg">
                                <p class="text-sm text-gray-600">Tarjeta Débito</p>
                                <p class="text-2xl font-bold text-blue-600"><?php echo number_format($ventas_dia['total_tarjeta'] ?? 0, 2); ?></p>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg">
                                <p class="text-sm text-gray-600">Pago Móvil</p>
                                <p class="text-2xl font-bold text-purple-600"><?php echo number_format($ventas_dia['total_pago_movil'] ?? 0, 2); ?></p>
                            </div>
                            <div class="text-center p-3 bg-white rounded-lg">
                                <p class="text-sm text-gray-600">Total General</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo number_format($ventas_dia['total_monto'] ?? 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Últimas Ventas -->
                    <?php if (!empty($ultimas_ventas)): ?>
                    <div class="mt-4">
                        <h4 class="font-medium text-gray-700 mb-3">Últimas Ventas</h4>
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Método</th>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                                            <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 uppercase">Hora</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($ultimas_ventas as $venta): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-2 px-4 text-sm font-medium"><?php echo $venta['numero_ticket']; ?></td>
                                            <td class="py-2 px-4 text-sm"><?php echo htmlspecialchars($venta['cliente']); ?></td>
                                            <td class="py-2 px-4">
                                                <span class="metodo-badge <?php echo $venta['metodo_pago'] == 'tarjeta_debito' ? 'metodo-tarjeta' : 'metodo-movil'; ?>">
                                                    <?php echo $venta['metodo_pago'] == 'tarjeta_debito' ? 'Tarjeta' : 'Pago Móvil'; ?>
                                                </span>
                                            </td>
                                            <td class="py-2 px-4 text-sm font-bold text-green-600"><?php echo number_format($venta['total'], 2); ?></td>
                                            <td class="py-2 px-4 text-sm text-gray-500"><?php echo $venta['hora']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-6">
                        <i class="fas fa-shopping-cart text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">No hay ventas registradas hoy</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Formulario de Cierre -->
                <form method="POST" action="cerrar.php" onsubmit="return confirmarCierre()">
                    <input type="hidden" name="cerrar_caja" value="1">
                    
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <!-- Observaciones -->
                        <div class="mb-6">
                            <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-2">
                                Observaciones (opcional)
                            </label>
                            <textarea 
                                id="observaciones" 
                                name="observaciones" 
                                rows="3" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                placeholder="Notas sobre el día, incidencias, comentarios..."
                            ></textarea>
                            <p class="text-xs text-gray-500 mt-1">Ej: "Todo normal", "Problema con terminal de pago", etc.</p>
                        </div>
                        
                        <!-- Confirmación -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 mb-6">
                            <h4 class="font-semibold text-green-800 mb-2 flex items-center">
                                <i class="fas fa-clipboard-check mr-2"></i>
                                Confirmación de Cierre
                            </h4>
                            <p class="text-sm text-green-600 mb-4">
                                Al proceder, se registrará el cierre de caja con los siguientes datos:
                            </p>
                            
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Total Ventas del Día:</span>
                                    <span class="font-bold text-green-700"><?php echo number_format($ventas_dia['total_monto'] ?? 0, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Monto Final:</span>
                                    <span class="font-bold text-green-700"><?php echo number_format($ventas_dia['total_monto'] ?? 0, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Diferencia:</span>
                                    <span class="font-bold text-green-700">$0.00</span>
                                </div>
                            </div>
                            
                            <div class="flex items-center mt-4">
                                <input type="checkbox" id="confirmar" name="confirmar" required class="mr-3 h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                <label for="confirmar" class="text-sm text-green-800">
                                    Confirmo que he verificado los totales y procedo con el cierre de caja.
                                </label>
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="flex flex-col md:flex-row justify-center space-y-4 md:space-y-0 md:space-x-4">
                            <a href="../index.php" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all flex items-center justify-center">
                                <i class="fas fa-times mr-2"></i> Cancelar
                            </a>
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center justify-center">
                                <i class="fas fa-lock mr-2"></i> Cerrar Caja
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Información Adicional -->
            <div class="caja-card rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Proceso de Cierre</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            Verificación de Totales
                        </h4>
                        <p class="text-sm text-gray-600">
                            El sistema calcula automáticamente todas las ventas del día, separando por método de pago.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            Sin Efectivo
                        </h4>
                        <p class="text-sm text-gray-600">
                            Al no manejar efectivo, la diferencia siempre será $0.00. No requiere conteo físico.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            Auditoría Automática
                        </h4>
                        <p class="text-sm text-gray-600">
                            Cada cierre queda registrado en el sistema con fecha y hora exactas para auditoría.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            Disponibilidad
                        </h4>
                        <p class="text-sm text-gray-600">
                            Una vez cerrada la caja, no podrá registrar más ventas hasta abrir una nueva.
                        </p>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmarCierre() {
            const confirmacion = document.getElementById('confirmar');
            if (!confirmacion.checked) {
                alert('Debe confirmar que ha verificado los totales para proceder.');
                return false;
            }
            
            const totalVentas = <?php echo $ventas_dia['total_monto'] ?? 0; ?>;
            
            return confirm(
                '¿Está seguro de proceder con el cierre de caja?\n\n' +
                'Total del día: $' + totalVentas.toFixed(2) + '\n' +
                'Una vez cerrada, no podrá registrar más ventas hasta abrir una nueva caja.'
            );
        }
        
        // Auto-focus en textarea de observaciones
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('observaciones');
            if (textarea) {
                textarea.focus();
            }
            
            // Mostrar advertencia si no hay ventas
            const totalVentas = <?php echo $ventas_dia['total_monto'] ?? 0; ?>;
            if (totalVentas == 0) {
                console.log('Advertencia: No hay ventas registradas hoy.');
            }
        });
    </script>
</body>
</html>