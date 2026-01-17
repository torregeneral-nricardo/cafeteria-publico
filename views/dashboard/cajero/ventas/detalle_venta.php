<?php
/**
 * Detalle de Venta - Para modal en historial
 * Archivo: /views/dashboard/cajero/ventas/detalle_venta.php
 */

require_once '../../../../includes/seguridad.php';
require_once '../../../../includes/conexion.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea cajero
if ($usuario['rol'] !== 'Cajero') {
    http_response_code(403);
    exit('Acceso denegado');
}

// Obtener ID de venta
$venta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$venta_id) {
    http_response_code(400);
    exit('ID de venta no válido');
}

// Obtener conexión PDO
$pdo = obtenerConexion();
$cajero_id = $usuario['id'];

// Obtener información de la venta
$stmt = $pdo->prepare("
    SELECT 
        v.*,
        u.nombre as cajero_nombre,
        COALESCE(CONCAT(c.nombre, ' ', c.apellido), 'Cliente Ocasional') as cliente_nombre,
        COALESCE(CONCAT(c.prefijo_cedula, '-', c.cedula), 'N/A') as cliente_cedula,
        c.email as cliente_email,
        c.telefono as cliente_telefono
    FROM ventas v
    JOIN usuarios u ON v.cajero_id = u.id
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = :venta_id AND v.cajero_id = :cajero_id
");

$stmt->execute([
    ':venta_id' => $venta_id,
    ':cajero_id' => $cajero_id
]);

$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    http_response_code(404);
    exit('Venta no encontrada');
}

// Obtener detalles de la venta
$stmt = $pdo->prepare("
    SELECT 
        dv.*,
        p.nombre as producto_nombre,
        p.codigo as producto_codigo,
        p.unidad_medida
    FROM detalle_ventas dv
    JOIN productos p ON dv.producto_id = p.id
    WHERE dv.venta_id = :venta_id
    ORDER BY dv.id
");

$stmt->execute([':venta_id' => $venta_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatear datos
$metodo_pago_texto = $venta['metodo_pago'] == 'tarjeta_debito' ? 'Tarjeta Débito' : 'Pago Móvil';
$fecha_formateada = date('d/m/Y H:i:s', strtotime($venta['fecha_venta']));
$estado_clases = [
    'completada' => 'bg-green-100 text-green-800',
    'pendiente' => 'bg-yellow-100 text-yellow-800',
    'anulada' => 'bg-red-100 text-red-800',
    'reembolsada' => 'bg-blue-100 text-blue-800'
];
$estado_texto = [
    'completada' => 'Completada',
    'pendiente' => 'Pendiente',
    'anulada' => 'Anulada',
    'reembolsada' => 'Reembolsada'
];
?>

<div class="space-y-6">
    <!-- Encabezado -->
    <div class="border-b pb-4">
        <div class="flex justify-between items-start">
            <div>
                <h4 class="text-lg font-bold text-gray-800">Ticket #<?php echo $venta['numero_ticket']; ?></h4>
                <p class="text-sm text-gray-600"><?php echo $fecha_formateada; ?></p>
            </div>
            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $estado_clases[$venta['estado']] ?? 'bg-gray-100 text-gray-800'; ?>">
                <?php echo $estado_texto[$venta['estado']] ?? $venta['estado']; ?>
            </span>
        </div>
    </div>
    
    <!-- Información General -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h5 class="font-semibold text-gray-700 mb-2">Información del Cliente</h5>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-800"><strong>Nombre:</strong> <?php echo htmlspecialchars($venta['cliente_nombre']); ?></p>
                <p class="text-gray-800"><strong>Cédula:</strong> <?php echo $venta['cliente_cedula']; ?></p>
                <?php if ($venta['cliente_email']): ?>
                <p class="text-gray-800"><strong>Email:</strong> <?php echo htmlspecialchars($venta['cliente_email']); ?></p>
                <?php endif; ?>
                <?php if ($venta['cliente_telefono']): ?>
                <p class="text-gray-800"><strong>Teléfono:</strong> <?php echo htmlspecialchars($venta['cliente_telefono']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div>
            <h5 class="font-semibold text-gray-700 mb-2">Información de Pago</h5>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-800"><strong>Método:</strong> <?php echo $metodo_pago_texto; ?></p>
                <p class="text-gray-800"><strong>Referencia:</strong> <?php echo $venta['referencia_pago']; ?></p>
                <p class="text-gray-800"><strong>Cajero:</strong> <?php echo htmlspecialchars($venta['cajero_nombre']); ?></p>
                <p class="text-gray-800"><strong>IP:</strong> <?php echo $venta['ip_address'] ?? 'N/A'; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Detalles de Productos -->
    <div>
        <h5 class="font-semibold text-gray-700 mb-3">Productos Vendidos</h5>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 text-left text-sm font-medium text-gray-700">Código</th>
                        <th class="py-2 px-4 text-left text-sm font-medium text-gray-700">Producto</th>
                        <th class="py-2 px-4 text-left text-sm font-medium text-gray-700">Cantidad</th>
                        <th class="py-2 px-4 text-left text-sm font-medium text-gray-700">Precio Unit.</th>
                        <th class="py-2 px-4 text-left text-sm font-medium text-gray-700">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($detalles as $detalle): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-4 text-sm font-mono"><?php echo $detalle['producto_codigo']; ?></td>
                        <td class="py-2 px-4 text-sm"><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                        <td class="py-2 px-4 text-sm">
                            <?php echo $detalle['cantidad']; ?> <?php echo $detalle['unidad_medida']; ?>
                        </td>
                        <td class="py-2 px-4 text-sm"><?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                        <td class="py-2 px-4 text-sm font-bold text-green-600"><?php echo number_format($detalle['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="3" class="py-2 px-4 text-right text-sm font-medium text-gray-700">Subtotal:</td>
                        <td colspan="2" class="py-2 px-4 text-sm font-bold"><?php echo number_format($venta['subtotal'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="py-2 px-4 text-right text-sm font-medium text-gray-700">IVA (0%):</td>
                        <td colspan="2" class="py-2 px-4 text-sm">0.00</td>
                    </tr>
                    <tr class="border-t">
                        <td colspan="3" class="py-2 px-4 text-right text-lg font-bold text-gray-800">TOTAL:</td>
                        <td colspan="2" class="py-2 px-4 text-lg font-bold text-green-600"><?php echo number_format($venta['total'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <!-- Acciones -->
    <div class="border-t pt-4">
        <div class="flex justify-center space-x-4">
            <a href="ticket.php?id=<?php echo $venta_id; ?>" 
               target="_blank"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-lg hover:shadow-lg transition-all flex items-center">
                <i class="fas fa-print mr-2"></i> Imprimir Ticket
            </a>
            <button onclick="window.history.back()" 
                    class="px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all">
                Volver
            </button>
        </div>
    </div>
</div>