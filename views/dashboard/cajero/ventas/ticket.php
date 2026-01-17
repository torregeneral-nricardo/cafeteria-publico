<?php
/**
 * Ticket de Venta - Vista e Impresión
 * Archivo   :   views/dashboard/cajero/ventas/ticket.php
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

// Obtener ID de venta
$venta_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$venta_id) {
    header('Location: historial.php');
    exit();
}

// Obtener conexión PDO
$pdo = obtenerConexion();

// Obtener información de la venta
$stmt = $pdo->prepare("
    SELECT 
        v.*,
        u.nombre as cajero_nombre,
        c.nombre as cliente_nombre,
        c.apellido as cliente_apellido,
        CONCAT(c.prefijo_cedula, '-', c.cedula) as cliente_cedula
    FROM ventas v
    JOIN usuarios u ON v.cajero_id = u.id
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.id = :venta_id
");

$stmt->execute([':venta_id' => $venta_id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    die("Venta no encontrada");
}

// Verificar que la venta pertenezca al cajero actual (seguridad)
if ($venta['cajero_id'] != $usuario['id']) {
    header('Location: historial.php?error=no_autorizado');
    exit();
}

// Obtener detalles de la venta
$stmt = $pdo->prepare("
    SELECT 
        dv.*,
        p.nombre as producto_nombre,
        p.codigo as producto_codigo
    FROM detalle_ventas dv
    JOIN productos p ON dv.producto_id = p.id
    WHERE dv.venta_id = :venta_id
    ORDER BY dv.id
");

$stmt->execute([':venta_id' => $venta_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Registrar actividad de visualización de ticket
registrarActividad('Ticket generado', 'Ventas', "Ticket #{$venta['numero_ticket']} generado");

// Formatear datos
$metodo_pago_texto = $venta['metodo_pago'] == 'tarjeta_debito' ? 'Tarjeta Débito' : 'Pago Móvil';
$cliente_nombre = $venta['cliente_nombre'] ? 
    "{$venta['cliente_nombre']} {$venta['cliente_apellido']}" : 
    'Cliente Ocasional';
$cliente_cedula = $venta['cliente_cedula'] ?? 'N/A';

// Configurar para impresión
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta - <?php echo $venta['numero_ticket']; ?></title>
    <style>
        /* Estilos específicos para impresión de ticket */
        @media print {
            @page {
                size: 80mm auto; /* Tamaño ticket */
                margin: 0;
            }
            body {
                font-family: 'Courier New', monospace;
                font-size: 12px;
                width: 80mm;
                margin: 0;
                padding: 5mm;
                color: #000;
                background: #fff;
            }
            .no-print {
                display: none !important;
            }
            .ticket {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .break-page {
                page-break-after: always;
            }
        }
        
        @media screen {
            body {
                font-family: 'Inter', sans-serif;
                background: #f3f4f6;
                padding: 20px;
                display: flex;
                justify-content: center;
            }
            .ticket {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                max-width: 80mm;
                padding: 20px;
            }
        }
        
        /* Estilos comunes */
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-sm {
            font-size: 11px;
        }
        .text-lg {
            font-size: 14px;
        }
        .text-xl {
            font-size: 16px;
        }
        .my-2 {
            margin-top: 8px;
            margin-bottom: 8px;
        }
        .my-3 {
            margin-top: 12px;
            margin-bottom: 12px;
        }
        .my-4 {
            margin-top: 16px;
            margin-bottom: 16px;
        }
        .py-2 {
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .border-top {
            border-top: 1px dashed #000;
        }
        .border-bottom {
            border-bottom: 1px dashed #000;
        }
        .w-full {
            width: 100%;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 4px 0;
            border-bottom: 1px dotted #ddd;
        }
        .table th {
            text-align: left;
            font-weight: bold;
        }
        .bg-gray-100 {
            background-color: #f3f4f6;
        }
        .text-gray-600 {
            color: #4b5563;
        }
        .text-green-600 {
            color: #059669;
        }
        .break-line {
            word-break: break-all;
            white-space: pre-wrap;
        }
        .qr-code {
            display: inline-block;
            padding: 10px;
            background: #f8fafc;
            border: 1px dashed #d1d5db;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Ticket -->
    <div class="ticket">
        <!-- Encabezado -->
        <div class="text-center my-3">
            <h1 class="font-bold text-xl">TORRE GENERAL</h1>
            <p class="text-sm">Cafetería y Restaurante</p>
            <p class="text-sm">Av. Principal, Torre General</p>
            <p class="text-sm">RIF: J-12345678-9</p>
            <p class="text-sm">Tel: (0412) 123-4567</p>
        </div>
        
        <div class="border-top my-2"></div>
        
        <!-- Información del Ticket -->
        <div class="text-center my-3">
            <h2 class="font-bold text-lg">COMPROBANTE DE VENTA</h2>
            <p class="text-sm">No. Control: <?php echo $venta['numero_ticket']; ?></p>
            <p class="text-sm">Fecha: <?php echo date('d/m/Y', strtotime($venta['fecha_venta'])); ?></p>
            <p class="text-sm">Hora: <?php echo date('H:i:s', strtotime($venta['fecha_venta'])); ?></p>
        </div>
        
        <div class="border-top my-2"></div>
        
        <!-- Información del Cajero -->
        <div class="my-3">
            <p class="text-sm"><strong>Cajero:</strong> <?php echo htmlspecialchars($venta['cajero_nombre']); ?></p>
            <p class="text-sm"><strong>Cliente:</strong> <?php echo htmlspecialchars($cliente_nombre); ?></p>
            <?php if ($venta['cliente_cedula']): ?>
            <p class="text-sm"><strong>C.I.:</strong> <?php echo htmlspecialchars($cliente_cedula); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="border-top my-2"></div>
        
        <!-- Detalles de la Venta -->
        <table class="table my-3">
            <thead>
                <tr>
                    <th class="text-left">Descripción</th>
                    <th class="text-right">Cant.</th>
                    <th class="text-right">P.Unit</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $detalle): ?>
                <tr>
                    <td class="text-left break-line"><?php echo htmlspecialchars($detalle['producto_nombre']); ?></td>
                    <td class="text-right"><?php echo $detalle['cantidad']; ?></td>
                    <td class="text-right"><?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($detalle['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="border-top my-2"></div>
        
        <!-- Totales -->
        <div class="my-3">
            <div class="flex justify-between py-2">
                <span class="font-bold">Subtotal:</span>
                <span class="font-bold"><?php echo number_format($venta['subtotal'], 2); ?></span>
            </div>
            <div class="flex justify-between py-2">
                <span>IVA (0%):</span>
                <span>0.00</span>
            </div>
            <div class="flex justify-between py-2 border-top">
                <span class="font-bold text-lg">TOTAL:</span>
                <span class="font-bold text-lg text-green-600"><?php echo number_format($venta['total'], 2); ?></span>
            </div>
        </div>
        
        <div class="border-top my-2"></div>
        
        <!-- Información de Pago -->
        <div class="my-3">
            <p class="text-sm"><strong>Método de Pago:</strong> <?php echo $metodo_pago_texto; ?></p>
            <p class="text-sm"><strong>Referencia:</strong> <?php echo $venta['referencia_pago']; ?></p>
            <p class="text-sm"><strong>Estado:</strong> COMPLETADA</p>
        </div>
        
        <!-- Código QR (simulado) -->
        <div class="text-center my-4">
            <div class="qr-code">
                <p class="text-sm">CÓDIGO DE VERIFICACIÓN</p>
                <p class="font-bold"><?php echo substr(md5($venta['numero_ticket']), 0, 12); ?></p>
                <p class="text-xs">Verifique en: www.torregeneral.com/verificar</p>
            </div>
        </div>
        
        <!-- Mensajes al cliente -->
        <div class="text-center my-3">
            <p class="text-sm">¡Gracias por su compra!</p>
            <p class="text-sm">Conserve este ticket para cualquier reclamo</p>
            <p class="text-sm">Válido por 30 días</p>
        </div>
        
        <!-- Pie de página -->
        <div class="border-top my-2"></div>
        <div class="text-center my-3">
            <p class="text-xs">Sistema de Gestión Torre General v1.0</p>
            <p class="text-xs"><?php echo date('d/m/Y H:i:s'); ?></p>
            <p class="text-xs">Transacción #<?php echo str_pad($venta['id'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>
        
        <!-- Separador para cortar -->
        <div class="text-center my-4">
            <p class="text-xs">--- CORTAR AQUÍ ---</p>
        </div>
    </div>
    
    <!-- Botones de acción (solo en pantalla) -->
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; display: flex; flex-direction: column; gap: 10px;">
        <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-print mr-2"></i> Imprimir Ticket
        </button>
        <button onclick="window.location.href='nueva.php'" class="bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i> Nueva Venta
        </button>
        <button onclick="window.location.href='historial.php'" class="bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-history mr-2"></i> Volver al Historial
        </button>
        <button onclick="window.location.href='../index.php'" class="bg-purple-600 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-home mr-2"></i> Dashboard
        </button>
    </div>
    
    <!-- Script para auto-impresión (opcional) -->
    <script>
        // Auto-imprimir al cargar (opcional, descomentar si se quiere)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // };
        
        // Configurar para impresión
        document.addEventListener('keydown', function(e) {
            // Ctrl+P para imprimir
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            
            // ESC para cerrar
            if (e.key === 'Escape') {
                window.location.href = 'historial.php';
            }
        });
        
        // Deshabilitar menú contextual para proteger ticket
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
    </script>
</body>
</html>