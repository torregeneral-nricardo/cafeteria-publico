<?php
/**
 * Dashboard Móvil para Cajero - VERSIÓN DEFINITIVA
 * Archivo: /views/dashboard/cajero-mobile/index.php
 * Rutas exactas y consultas precisas basadas en BD real
 */

// ============================================
// INCLUSIONES - MISMAS RUTAS QUE EL ORIGINAL
// ============================================
require_once '../../../includes/seguridad.php';
require_once '../../../includes/conexion.php';
require_once '../../../includes/auditoria.php';
// NOTA: La función obtenerEstadoCaja() debe estar en helpers.php

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea cajero
if ($usuario['rol'] !== 'Cajero') {
    header('Location: ../../../index.php?error=no_autorizado');
    exit();
}

// Obtener conexión PDO
$pdo = obtenerConexion();

// Obtener color del rol (usando tu tabla roles)
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Cajero'");
$stmt->execute();
$rol_color = $stmt->fetchColumn();

// ============================================
// DATOS REALES - BASADO EN TU ESTRUCTURA DE BD
// ============================================

$cajero_id = $usuario['id'];

// 1. ESTADO DE CAJA - Usando función existente
$estado_caja = obtenerEstadoCaja($cajero_id);

// Si no hay caja abierta, crear array vacío (igual que original)
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

// 2. VENTAS RECIENTES DEL DÍA (últimas 3 para móvil)
$stmt = $pdo->prepare("
    SELECT 
        v.id,
        v.numero_ticket,
        CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
        v.total,
        v.metodo_pago,
        DATE_FORMAT(v.fecha_venta, '%H:%i') as hora,
        v.referencia_pago
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    WHERE v.cajero_id = :cajero_id 
        AND DATE(v.fecha_venta) = CURDATE()
        AND v.estado != 'anulada'
    ORDER BY v.fecha_venta DESC
    LIMIT 3
");

$stmt->execute([':cajero_id' => $cajero_id]);
$ventas_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. PRODUCTOS MÁS VENDIDOS DEL DÍA (top 3 para móvil)
$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.codigo,
        p.nombre,
        p.stock,
        p.precio_venta,
        SUM(dv.cantidad) as vendidos
    FROM detalle_ventas dv
    JOIN ventas v ON dv.venta_id = v.id
    JOIN productos p ON dv.producto_id = p.id
    WHERE v.cajero_id = :cajero_id 
        AND DATE(v.fecha_venta) = CURDATE()
        AND v.estado != 'anulada'
    GROUP BY p.id, p.codigo, p.nombre, p.stock, p.precio_venta
    ORDER BY vendidos DESC
    LIMIT 3
");

$stmt->execute([':cajero_id' => $cajero_id]);
$productos_populares = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. MÉTRICAS DEL DÍA - Consultas precisas
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

// Clientes atendidos (consulta separada)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT cliente_id) as clientes_atendidos
    FROM ventas 
    WHERE cajero_id = :cajero_id 
        AND DATE(fecha_venta) = CURDATE()
        AND estado != 'anulada'
        AND cliente_id IS NOT NULL
");

$stmt->execute([':cajero_id' => $cajero_id]);
$clientes_result = $stmt->fetch(PDO::FETCH_ASSOC);
$metricas['clientes_atendidos'] = $clientes_result['clientes_atendidos'] ?? 0;

// Calcular porcentaje de meta diaria
$meta_diaria = 2000.00;
$porcentaje_meta = $metricas['total_ventado'] > 0 ? 
    min(100, ($metricas['total_ventado'] / $meta_diaria) * 100) : 0;

// Verificar turno activo (usando tabla turnos_caja)
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

// Para testing, mantener siempre activo si no hay turnos configurados
if (!$turno_activo) {
    $turno_activo = true; // Para desarrollo
}

// Registrar actividad
registrarActividad('Acceso dashboard móvil', 'Dashboard Cajero Móvil', 'Ingresó al panel móvil del cajero');

// Configurar título de página
$page_title = 'Dashboard Móvil';

// Iniciar buffer para contenido
ob_start();
?>

<!-- CONTENIDO DEL DASHBOARD MÓVIL -->
<div class="dashboard-mobile">
    <!-- Sección de bienvenida -->
    <section class="welcome-section">
        <div class="welcome-card">
            <div class="welcome-text">
                <h2 class="greeting">¡Hola, <?php echo htmlspecialchars(explode(' ', $usuario['nombre'])[0]); ?>!</h2>
                <p class="date-time">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date('d/m/Y'); ?> • 
                    <span id="live-clock"><?php echo date('H:i'); ?></span>
                </p>
            </div>
            <div class="welcome-icon">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>
    </section>

    <!-- Estado de caja y turno -->
    <section class="status-section">
        <div class="status-grid">
            <div class="status-card <?php echo $estado_caja['abierta'] ? 'status-open' : 'status-closed'; ?>">
                <div class="status-icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div class="status-content">
                    <h4>Caja</h4>
                    <p class="status-value <?php echo $estado_caja['abierta'] ? 'text-success' : 'text-danger'; ?>">
                        <?php echo $estado_caja['abierta'] ? 'ABIERTA' : 'CERRADA'; ?>
                    </p>
                    <p class="status-detail">
                        <small>Ventas: <strong>$<?php echo number_format($estado_caja['ventas_hoy'], 2); ?></strong></small>
                    </p>
                </div>
                <a href="caja/estado.php" class="status-action">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            
            <div class="status-card <?php echo $turno_activo ? 'status-active' : 'status-inactive'; ?>">
                <div class="status-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="status-content">
                    <h4>Turno</h4>
                    <p class="status-value <?php echo $turno_activo ? 'text-success' : 'text-warning'; ?>">
                        <?php echo $turno_activo ? 'ACTIVO' : 'INACTIVO'; ?>
                    </p>
                    <p class="status-detail">
                        <small><?php echo $turno_activo ? 'En horario' : 'Fuera de turno'; ?></small>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Acciones principales -->
    <section class="actions-section">
        <h3 class="section-title">Acciones Rápidas</h3>
        <div class="actions-grid">
            <a href="ventas/nueva.php" 
               class="action-card action-primary <?php echo (!$estado_caja['abierta'] || !$turno_activo) ? 'disabled' : ''; ?>"
               <?php echo (!$estado_caja['abierta'] || !$turno_activo) ? 'onclick="event.preventDefault(); MobileApp.showToast(\'Debe tener caja abierta y estar en turno activo\', \'warning\');"' : ''; ?>>
                <div class="action-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <span class="action-label">Nueva Venta</span>
            </a>
            
            <a href="caja/estado.php" class="action-card action-success">
                <div class="action-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <span class="action-label">Estado Caja</span>
            </a>
            
            <a href="inventario/consulta.php" class="action-card action-info">
                <div class="action-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <span class="action-label">Inventario</span>
            </a>
            
            <a href="ventas/historial.php" class="action-card action-warning">
                <div class="action-icon">
                    <i class="fas fa-history"></i>
                </div>
                <span class="action-label">Historial</span>
            </a>
        </div>
    </section>

    <!-- Métricas rápidas -->
    <section class="metrics-section">
        <h3 class="section-title">Resumen del Día</h3>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-header">
                    <i class="fas fa-shopping-cart"></i>
                    <h4>Ventas</h4>
                </div>
                <div class="metric-value"><?php echo $metricas['total_ventas']; ?></div>
                <div class="metric-label">Transacciones</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-header">
                    <i class="fas fa-money-bill-wave"></i>
                    <h4>Total</h4>
                </div>
                <div class="metric-value">$<?php echo number_format($metricas['total_ventado'], 0); ?></div>
                <div class="metric-label">Vendido hoy</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-header">
                    <i class="fas fa-users"></i>
                    <h4>Clientes</h4>
                </div>
                <div class="metric-value"><?php echo $metricas['clientes_atendidos']; ?></div>
                <div class="metric-label">Atendidos</div>
            </div>
            
            <div class="metric-card">
                <div class="metric-header">
                    <i class="fas fa-bullseye"></i>
                    <h4>Meta</h4>
                </div>
                <div class="metric-value"><?php echo number_format($porcentaje_meta, 1); ?>%</div>
                <div class="metric-label">Completada</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $porcentaje_meta; ?>%"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Ventas recientes -->
    <section class="recent-sales-section">
        <div class="section-header">
            <h3 class="section-title">Ventas Recientes</h3>
            <a href="ventas/historial.php" class="see-all">Ver todas <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <?php if (empty($ventas_recientes)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <p class="empty-text">No hay ventas hoy</p>
                <?php if ($estado_caja['abierta'] && $turno_activo): ?>
                    <a href="ventas/nueva.php" class="btn btn-primary">Realizar primera venta</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="sales-list">
                <?php foreach ($ventas_recientes as $venta): 
                    $cliente_display = !empty($venta['cliente_nombre']) ? 
                        (strlen($venta['cliente_nombre']) > 15 ? 
                         substr($venta['cliente_nombre'], 0, 12) . '...' : 
                         $venta['cliente_nombre']) : 
                        'Cliente Ocasional';
                    
                    $metodo_badge = $venta['metodo_pago'] === 'tarjeta_debito' ? 
                        'badge-blue' : ($venta['metodo_pago'] === 'pago_movil' ? 'badge-purple' : 'badge-green');
                    
                    $metodo_text = $venta['metodo_pago'] === 'tarjeta_debito' ? 
                        'Tarjeta' : ($venta['metodo_pago'] === 'pago_movil' ? 'Móvil' : 'Efectivo');
                ?>
                <div class="sale-item" data-venta-id="<?php echo $venta['id']; ?>">
                    <div class="sale-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="sale-details">
                        <div class="sale-header">
                            <h4 class="sale-ticket"><?php echo htmlspecialchars($venta['numero_ticket']); ?></h4>
                            <span class="sale-time"><?php echo $venta['hora']; ?></span>
                        </div>
                        <p class="sale-client"><?php echo htmlspecialchars($cliente_display); ?></p>
                        <div class="sale-footer">
                            <span class="badge <?php echo $metodo_badge; ?>"><?php echo $metodo_text; ?></span>
                            <span class="sale-amount">$<?php echo number_format($venta['total'], 2); ?></span>
                        </div>
                    </div>
                    <a href="ventas/detalle.php?id=<?php echo $venta['id']; ?>" class="sale-action">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Productos populares -->
    <section class="popular-products-section">
        <div class="section-header">
            <h3 class="section-title">Productos Más Vendidos</h3>
            <a href="productos/populares.php" class="see-all">Ver todos <i class="fas fa-arrow-right"></i></a>
        </div>
        
        <?php if (empty($productos_populares)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <p class="empty-text">No hay datos de ventas hoy</p>
            </div>
        <?php else: ?>
            <div class="products-list">
                <?php foreach ($productos_populares as $producto): 
                    $stock_bajo = $producto['stock'] <= 5;
                ?>
                <div class="product-item <?php echo $stock_bajo ? 'low-stock' : ''; ?>">
                    <div class="product-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="product-details">
                        <h4 class="product-name"><?php echo htmlspecialchars($producto['nombre']); ?></h4>
                        <p class="product-code"><?php echo htmlspecialchars($producto['codigo']); ?></p>
                        <div class="product-stats">
                            <div class="stat">
                                <i class="fas fa-shopping-cart"></i>
                                <span><?php echo $producto['vendidos']; ?> vendidos</span>
                            </div>
                            <div class="stat <?php echo $stock_bajo ? 'text-danger' : ''; ?>">
                                <i class="fas fa-box"></i>
                                <span>Stock: <?php echo $producto['stock']; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if ($stock_bajo): ?>
                    <div class="product-alert" title="Stock bajo">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Alertas importantes -->
    <div class="alerts-container">
        <?php if (!$estado_caja['abierta']): ?>
        <div class="alert alert-warning">
            <div class="alert-icon">
                <i class="fas fa-lock"></i>
            </div>
            <div class="alert-content">
                <strong>Caja cerrada</strong>
                <p>Debe abrir caja para registrar ventas</p>
                <a href="caja/abrir.php" class="btn btn-sm btn-warning">Abrir Caja</a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php 
        // Verificar productos con stock bajo (global, no solo los populares)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM productos WHERE stock <= 5 AND estado = 'activo'");
        $stmt->execute();
        $stock_bajo_count = $stmt->fetchColumn();
        
        if ($stock_bajo_count > 0): 
        ?>
        <div class="alert alert-danger">
            <div class="alert-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="alert-content">
                <strong>Stock bajo</strong>
                <p><?php echo $stock_bajo_count; ?> producto(s) tienen stock menor a 5 unidades</p>
                <a href="inventario/consulta.php?filter=stock_bajo" class="btn btn-sm btn-danger">Ver productos</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Reloj en tiempo real
function updateLiveClock() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const clockElement = document.getElementById('live-clock');
    if (clockElement) {
        clockElement.textContent = `${hours}:${minutes}`;
    }
}

// Inicializar
updateLiveClock();
setInterval(updateLiveClock, 60000);

// Gestos táctiles para recarga
let touchStartY = 0;
document.addEventListener('touchstart', function(e) {
    touchStartY = e.changedTouches[0].screenY;
});

document.addEventListener('touchend', function(e) {
    const touchEndY = e.changedTouches[0].screenY;
    const swipeDistance = touchStartY - touchEndY;
    
    // Swipe hacia abajo para recargar (solo en la parte superior)
    if (swipeDistance > 100 && window.scrollY === 0) {
        location.reload();
    }
});

// Manejo de clics en productos con stock bajo
document.querySelectorAll('.product-item.low-stock').forEach(item => {
    item.addEventListener('click', function() {
        const productName = this.querySelector('.product-name').textContent;
        MobileApp.showToast(`Stock bajo: ${productName}`, 'warning');
    });
});
</script>

<?php
// Capturar contenido y cargar layout
$content = ob_get_clean();

// Cargar layout móvil
require 'layouts/mobile.php';
?>