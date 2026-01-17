<?php
/**
 * Funciones de auditoría para el módulo Cajero - VERSIÓN CORREGIDA
 * Archivo: includes/auditoria.php
 */

require_once __DIR__ . '/seguridad.php';
require_once __DIR__ . '/conexion.php';

/**
 * Registrar actividad en el sistema
 */
function registrarActividad($accion, $modulo, $descripcion = '', $metadata = null) {
    if (!usuarioAutenticado()) {
        return false;
    }
    
    try {
        $pdo = obtenerConexion();
        
        $stmt = $pdo->prepare("
            INSERT INTO actividades_log 
            (usuario_id, accion, modulo, descripcion, ip_address, user_agent, metadata) 
            VALUES 
            (:usuario_id, :accion, :modulo, :descripcion, :ip_address, :user_agent, :metadata)
        ");
        
        $metadata_json = $metadata ? json_encode($metadata) : null;
        
        $stmt->execute([
            ':usuario_id' => $_SESSION['user_id'],
            ':accion' => $accion,
            ':modulo' => $modulo,
            ':descripcion' => $descripcion,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':metadata' => $metadata_json
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar venta en el sistema
 */
function registrarVenta($datos_venta, $detalles) {
    try {
        $pdo = obtenerConexion();
        $pdo->beginTransaction();
        
        // Generar número de ticket
        $numero_ticket = generarNumeroTicket($pdo);
        
        // Insertar cabecera de venta
        $stmt = $pdo->prepare("
            INSERT INTO ventas 
            (numero_ticket, cajero_id, cliente_id, subtotal, total, metodo_pago, referencia_pago, ip_address, user_agent)
            VALUES 
            (:numero_ticket, :cajero_id, :cliente_id, :subtotal, :total, :metodo_pago, :referencia_pago, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            ':numero_ticket' => $numero_ticket,
            ':cajero_id' => $datos_venta['cajero_id'],
            ':cliente_id' => $datos_venta['cliente_id'] ?? null,
            ':subtotal' => $datos_venta['subtotal'],
            ':total' => $datos_venta['total'],
            ':metodo_pago' => $datos_venta['metodo_pago'],
            ':referencia_pago' => $datos_venta['referencia_pago'],
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        $venta_id = $pdo->lastInsertId();
        
        // Insertar detalles de venta
        foreach ($detalles as $detalle) {
            $stmt = $pdo->prepare("
                INSERT INTO detalle_ventas 
                (venta_id, producto_id, cantidad, precio_unitario, subtotal)
                VALUES 
                (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)
            ");
            
            $stmt->execute([
                ':venta_id' => $venta_id,
                ':producto_id' => $detalle['producto_id'],
                ':cantidad' => $detalle['cantidad'],
                ':precio_unitario' => $detalle['precio_unitario'],
                ':subtotal' => $detalle['subtotal']
            ]);
        }
        
        // Actualizar monto en caja abierta
        $stmt = $pdo->prepare("
            UPDATE caja 
            SET monto_esperado = COALESCE(monto_esperado, monto_inicial) + :monto_venta
            WHERE cajero_id = :cajero_id AND estado = 'abierta'
            ORDER BY fecha_apertura DESC LIMIT 1
        ");
        
        $stmt->execute([
            ':monto_venta' => $datos_venta['total'],
            ':cajero_id' => $datos_venta['cajero_id']
        ]);
        
        $pdo->commit();
        
        // Registrar actividad
        registrarActividad(
            'Venta registrada',
            'Ventas',
            "Venta #{$numero_ticket} registrada por " . number_format($datos_venta['total'], 2),
            [
                'venta_id' => $venta_id,
                'numero_ticket' => $numero_ticket,
                'total' => $datos_venta['total']
            ]
        );
        
        return [
            'success' => true,
            'venta_id' => $venta_id,
            'numero_ticket' => $numero_ticket
        ];
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al registrar venta: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => 'Error al procesar la venta',
            'debug' => $e->getMessage()
        ];
    }
}

/**
 * Generar número de ticket único
 */
function generarNumeroTicket($pdo = null) {
    if (!$pdo) {
        $pdo = obtenerConexion();
    }
    
    $prefijo = 'TG';
    $fecha_str = date('ymd');
    
    // Obtener último consecutivo del día
    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(CAST(SUBSTRING(numero_ticket, 8, 4) AS UNSIGNED)), 0) + 1 as consecutivo
        FROM ventas 
        WHERE numero_ticket LIKE CONCAT(:prefijo, '-____-', :fecha_str)
    ");
    
    $stmt->execute([
        ':prefijo' => $prefijo,
        ':fecha_str' => $fecha_str
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $consecutivo = str_pad($result['consecutivo'], 4, '0', STR_PAD_LEFT);
    
    return "{$prefijo}-{$consecutivo}-{$fecha_str}";
}

/**
 * Verificar si caja está abierta para el cajero - VERSIÓN CORREGIDA
 */
function cajaAbierta($cajero_id) {
    try {
        $pdo = obtenerConexion();
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM caja 
            WHERE cajero_id = :cajero_id AND estado = 'abierta'
        ");
        
        $stmt->execute([':cajero_id' => $cajero_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
        
    } catch (PDOException $e) {
        error_log("Error en cajaAbierta: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener estado actual de caja - VERSIÓN CORREGIDA
 */
function obtenerEstadoCaja($cajero_id) {
    try {
        $pdo = obtenerConexion();
        
        // Primero verificar si hay caja abierta
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                u.nombre as cajero_nombre
            FROM caja c
            JOIN usuarios u ON c.cajero_id = u.id
            WHERE c.cajero_id = :cajero_id AND c.estado = 'abierta'
            ORDER BY c.fecha_apertura DESC LIMIT 1
        ");
        
        $stmt->execute([':cajero_id' => $cajero_id]);
        $caja = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$caja) {
            return null;
        }
        
        // Calcular ventas del día separadamente para evitar problemas de parámetros
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(total), 0) as ventas_hoy,
                COALESCE(SUM(CASE WHEN metodo_pago = 'tarjeta_debito' THEN total ELSE 0 END), 0) as tarjeta_hoy,
                COALESCE(SUM(CASE WHEN metodo_pago = 'pago_movil' THEN total ELSE 0 END), 0) as pago_movil_hoy
            FROM ventas 
            WHERE cajero_id = :cajero_id 
                AND DATE(fecha_venta) = CURDATE()
                AND estado NOT IN ('anulada', 'reembolsada')
        ");
        
        $stmt->execute([':cajero_id' => $cajero_id]);
        $ventas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Combinar resultados
        $resultado = array_merge($caja, $ventas);
        
        return $resultado;
        
    } catch (PDOException $e) {
        error_log("Error en obtenerEstadoCaja: " . $e->getMessage());
        return null;
    }
}
?>