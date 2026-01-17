<?php
/**
 * Sistema de Punto de Venta (POS) - Nueva Venta - VERSIÓN MEJORADA
 * Archivo: views/dashboard/cajero/ventas/nueva.php
 * 
 * Mejoras incluidas:
 * 1. Sistema de ventas pendientes (hasta 10)
 * 2. Eliminada sección "Agregar Producto Rápido"
 * 3. Diseño mejorado con scroll interno
 * 4. Búsqueda de clientes corregida
 */

// ==============================================
// INICIAR SESIÓN PRIMERO - SIN NINGÚN OUTPUT ANTES
// ==============================================
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// ==============================================
// DEBUG: Solo para error_log
// ==============================================
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__, 5) . '/error_log_cajero.txt');

// ==============================================
// CARGAR ARCHIVOS CON RUTAS ABSOLUTAS
// ==============================================
$root_path = dirname(__DIR__, 4);
require_once $root_path . '/includes/seguridad.php';
require_once $root_path . '/includes/conexion.php';
require_once $root_path . '/includes/auditoria.php';

// ==============================================
// VERIFICAR AUTENTICACIÓN
// ==============================================
if (!usuarioAutenticado()) {
    error_log("ERROR: usuarioAutenticado() devolvió FALSE");
    header('Location: ' . $root_path . '/index.php?error=no_autenticado');
    exit();
}

$usuario = obtenerUsuarioActual();

// Verificar que sea cajero
if ($usuario['rol'] !== 'Cajero') {
    error_log("ERROR: Usuario no es cajero, es: " . $usuario['rol']);
    header('Location: ' . $root_path . '/index.php?error=no_autorizado');
    exit();
}

// Obtener conexión PDO
try {
    $pdo = obtenerConexion();
} catch (Exception $e) {
    error_log("ERROR obtenerConexion: " . $e->getMessage());
    die("Error de conexión a la base de datos");
}

// ==============================================
// CONTROL DE SESIONES SIMULTÁNEAS
// ==============================================
$cajero_id = $usuario['id'];

// Verificar token de sesión en BD
try {
    $stmt = $pdo->prepare("SELECT session_token FROM usuarios WHERE id = ?");
    $stmt->execute([$cajero_id]);
    $token_bd = $stmt->fetchColumn();
    
    if (!$token_bd && isset($_SESSION['user_id'])) {
        $session_token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE usuarios SET session_token = ?, ultima_actividad = NOW() WHERE id = ?");
        $stmt->execute([$session_token, $cajero_id]);
        $_SESSION['session_token'] = $session_token;
    } elseif ($token_bd && (!isset($_SESSION['session_token']) || $token_bd !== $_SESSION['session_token'])) {
        error_log("ERROR: Token BD no coincide con sesión");
        session_destroy();
        $stmt = $pdo->prepare("UPDATE usuarios SET session_token = NULL WHERE id = ?");
        $stmt->execute([$cajero_id]);
        header('Location: ' . $root_path . '/index.php?error=sesion_duplicada');
        exit();
    }
} catch (Exception $e) {
    error_log("ERROR en control de sesión: " . $e->getMessage());
}

// ==============================================
// VERIFICACIÓN DE CAJA Y TURNO
// ==============================================
$caja_abierta = cajaAbierta($cajero_id);
$turno_activo = true; // Temporalmente siempre activo

// Si no hay caja abierta, redirigir
if (!$caja_abierta) {
    $_SESSION['error_venta'] = "❌ ERROR: Debe abrir caja antes de realizar ventas.";
    header('Location: ../index.php');
    exit();
}

if (!$turno_activo) {
    $_SESSION['error_venta'] = "❌ ERROR: No está en su turno de trabajo asignado.";
    header('Location: ../index.php');
    exit();
}

// ==============================================
// SISTEMA DE VENTAS PENDIENTES
// ==============================================
// Inicializar sistema de ventas pendientes si no existe
if (!isset($_SESSION['ventas_pendientes'])) {
    $_SESSION['ventas_pendientes'] = [];
    $_SESSION['venta_activa_id'] = null;
}

// Inicializar venta activa si no existe
if (!isset($_SESSION['venta_activa_id']) || $_SESSION['venta_activa_id'] === null) {
    // Crear primera venta pendiente
    $venta_id = uniqid('venta_', true);
    $_SESSION['ventas_pendientes'][$venta_id] = [
        'id' => $venta_id,
        'productos' => [],
        'cliente_id' => null,
        'metodo_pago' => null,
        'referencia' => null,
        'total' => 0,
        'items' => 0,
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'ultima_actividad' => date('Y-m-d H:i:s')
    ];
    $_SESSION['venta_activa_id'] = $venta_id;
}

$venta_activa_id = $_SESSION['venta_activa_id'];
$venta_activa = &$_SESSION['ventas_pendientes'][$venta_activa_id];

// ==============================================
// CONFIGURACIÓN INICIAL
// ==============================================
// Obtener color del rol
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Cajero'");
$stmt->execute();
$rol_color = $stmt->fetchColumn();

// Obtener categorías para el filtro
$stmt = $pdo->prepare("SELECT id, nombre FROM categorias_productos WHERE estado = 'activo' ORDER BY orden");
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==============================================
// PROCESAMIENTO DE ACCIONES
// ==============================================
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// --- GESTIÓN DE VENTAS PENDIENTES ---
if ($action === 'nueva_venta') {
    // Crear nueva venta pendiente (máximo 10)
    if (count($_SESSION['ventas_pendientes']) >= 10) {
        $_SESSION['error_venta'] = "❌ LÍMITE ALCANZADO: Máximo 10 ventas pendientes permitidas.";
        header('Location: nueva.php');
        exit();
    }
    
    $nueva_venta_id = uniqid('venta_', true);
    $_SESSION['ventas_pendientes'][$nueva_venta_id] = [
        'id' => $nueva_venta_id,
        'productos' => [],
        'cliente_id' => null,
        'metodo_pago' => null,
        'referencia' => null,
        'total' => 0,
        'items' => 0,
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'ultima_actividad' => date('Y-m-d H:i:s')
    ];
    $_SESSION['venta_activa_id'] = $nueva_venta_id;
    
    $_SESSION['mensaje_carrito'] = "✅ Nueva venta creada (#" . substr($nueva_venta_id, -4) . ")";
    header('Location: nueva.php');
    exit();
    
} elseif ($action === 'cambiar_venta' && isset($_GET['venta_id'])) {
    // Cambiar a otra venta pendiente
    $venta_id = $_GET['venta_id'];
    
    if (isset($_SESSION['ventas_pendientes'][$venta_id])) {
        $_SESSION['venta_activa_id'] = $venta_id;
        $_SESSION['mensaje_carrito'] = "✅ Cambió a venta #" . substr($venta_id, -4);
    }
    
    header('Location: nueva.php');
    exit();
    
} elseif ($action === 'eliminar_venta' && isset($_GET['venta_id'])) {
    // Eliminar venta pendiente
    $venta_id = $_GET['venta_id'];
    
    if (isset($_SESSION['ventas_pendientes'][$venta_id])) {
        unset($_SESSION['ventas_pendientes'][$venta_id]);
        
        // Si eliminamos la venta activa, activar otra o crear nueva
        if ($venta_activa_id === $venta_id) {
            if (count($_SESSION['ventas_pendientes']) > 0) {
                $_SESSION['venta_activa_id'] = array_key_first($_SESSION['ventas_pendientes']);
            } else {
                // Crear nueva venta
                $nueva_venta_id = uniqid('venta_', true);
                $_SESSION['ventas_pendientes'][$nueva_venta_id] = [
                    'id' => $nueva_venta_id,
                    'productos' => [],
                    'cliente_id' => null,
                    'metodo_pago' => null,
                    'referencia' => null,
                    'total' => 0,
                    'items' => 0,
                    'fecha_creacion' => date('Y-m-d H:i:s'),
                    'ultima_actividad' => date('Y-m-d H:i:s')
                ];
                $_SESSION['venta_activa_id'] = $nueva_venta_id;
            }
        }
        
        $_SESSION['mensaje_carrito'] = "✅ Venta pendiente eliminada";
    }
    
    header('Location: nueva.php');
    exit();
    
// --- AGREGAR PRODUCTO AL CARRITO ---
} elseif ($action === 'agregar' && isset($_POST['producto_id'])) {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = intval($_POST['cantidad'] ?? 1);
    
    // Obtener información del producto
    $stmt = $pdo->prepare("
        SELECT id, nombre, precio_venta, stock 
        FROM productos 
        WHERE id = :id AND estado = 'activo'
    ");
    $stmt->execute([':id' => $producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($producto) {
        // Verificar stock
        if ($producto['stock'] < $cantidad) {
            $_SESSION['error_stock'] = "❌ STOCK INSUFICIENTE: {$producto['nombre']}. Disponible: {$producto['stock']} unidades.";
        } else {
            // Agregar o actualizar en carrito de venta activa
            if (isset($venta_activa['productos'][$producto_id])) {
                $venta_activa['productos'][$producto_id]['cantidad'] += $cantidad;
            } else {
                $venta_activa['productos'][$producto_id] = [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => floatval($producto['precio_venta']),
                    'cantidad' => $cantidad,
                    'stock_disponible' => $producto['stock']
                ];
            }
            
            // Actualizar totales
            $venta_activa['total'] = calcularTotalVenta($venta_activa['productos']);
            $venta_activa['items'] = array_sum(array_column($venta_activa['productos'], 'cantidad'));
            $venta_activa['ultima_actividad'] = date('Y-m-d H:i:s');
            
            $_SESSION['mensaje_carrito'] = "✅ {$producto['nombre']} agregado al carrito";
        }
    } else {
        $_SESSION['error_stock'] = "❌ PRODUCTO NO DISPONIBLE: El producto seleccionado no está activo o no existe.";
    }
    
    header('Location: nueva.php');
    exit();
    
// --- ACTUALIZAR CANTIDAD EN CARRITO ---
} elseif ($action === 'actualizar' && isset($_POST['producto_id'])) {
    $producto_id = intval($_POST['producto_id']);
    $cantidad = intval($_POST['cantidad'] ?? 1);
    
    if (isset($venta_activa['productos'][$producto_id])) {
        if ($cantidad <= 0) {
            unset($venta_activa['productos'][$producto_id]);
            $_SESSION['mensaje_carrito'] = "✅ Producto eliminado del carrito";
        } else {
            // Verificar stock nuevamente
            $stmt = $pdo->prepare("SELECT stock, nombre FROM productos WHERE id = :id");
            $stmt->execute([':id' => $producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($producto && $cantidad <= $producto['stock']) {
                $venta_activa['productos'][$producto_id]['cantidad'] = $cantidad;
                $_SESSION['mensaje_carrito'] = "✅ Cantidad actualizada";
            } else {
                $_SESSION['error_stock'] = "❌ STOCK INSUFICIENTE para {$producto['nombre']}. Disponible: {$producto['stock']} unidades.";
            }
        }
        
        // Actualizar totales
        $venta_activa['total'] = calcularTotalVenta($venta_activa['productos']);
        $venta_activa['items'] = array_sum(array_column($venta_activa['productos'], 'cantidad'));
        $venta_activa['ultima_actividad'] = date('Y-m-d H:i:s');
    }
    
    header('Location: nueva.php');
    exit();
    
// --- ELIMINAR PRODUCTO DEL CARRITO ---
} elseif ($action === 'eliminar' && isset($_GET['producto_id'])) {
    $producto_id = intval($_GET['producto_id']);
    
    if (isset($venta_activa['productos'][$producto_id])) {
        unset($venta_activa['productos'][$producto_id]);
        
        // Actualizar totales
        $venta_activa['total'] = calcularTotalVenta($venta_activa['productos']);
        $venta_activa['items'] = array_sum(array_column($venta_activa['productos'], 'cantidad'));
        $venta_activa['ultima_actividad'] = date('Y-m-d H:i:s');
        
        $_SESSION['mensaje_carrito'] = "✅ Producto eliminado del carrito";
    }
    
    header('Location: nueva.php');
    exit();
    
// --- LIMPIAR CARRITO (venta activa) ---
} elseif ($action === 'limpiar') {
    $venta_activa['productos'] = [];
    $venta_activa['cliente_id'] = null;
    $venta_activa['metodo_pago'] = null;
    $venta_activa['referencia'] = null;
    $venta_activa['total'] = 0;
    $venta_activa['items'] = 0;
    $venta_activa['ultima_actividad'] = date('Y-m-d H:i:s');
    
    $_SESSION['mensaje_carrito'] = "✅ Carrito vaciado correctamente";
    
    header('Location: nueva.php');
    exit();
    
// --- BÚSQUEDA DE CLIENTES ---
} elseif ($action === 'buscar_cliente') {
    header('Content-Type: application/json');
    
    $busqueda = $_GET['q'] ?? '';
    $clientes = [];
    
    if (strlen($busqueda) >= 2) {
        $busqueda_numeros = preg_replace('/[^0-9]/', '', $busqueda);
        
        $stmt = $pdo->prepare("
            SELECT id, 
                   CONCAT(prefijo_cedula, '-', cedula) as cedula_completa, 
                   CONCAT(nombre, ' ', apellido) as nombre_completo,
                   telefono, email
            FROM clientes 
            WHERE (
                cedula LIKE ? OR
                CONCAT(prefijo_cedula, cedula) LIKE ? OR
                CONCAT(prefijo_cedula, '-', cedula) LIKE ? OR
                nombre LIKE ? OR 
                apellido LIKE ? OR
                CONCAT(nombre, ' ', apellido) LIKE ?
            ) AND estado = 'activo'
            LIMIT 10
        ");
        
        $busqueda_numeros_like = "%{$busqueda_numeros}%";
        $busqueda_texto_like = "%{$busqueda}%";
        $busqueda_completa_like = "%{$busqueda}%";
        
        try {
            $stmt->execute([
                $busqueda_numeros_like,
                $busqueda_numeros_like,
                $busqueda_completa_like,
                $busqueda_texto_like,
                $busqueda_texto_like,
                $busqueda_texto_like
            ]);
            
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ERROR en búsqueda cliente: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error en consulta']);
            exit();
        }
    }
    
    echo json_encode($clientes);
    exit();
    
// --- REGISTRAR NUEVO CLIENTE RÁPIDO ---
} elseif ($action === 'registrar_cliente') {
    $cedula = trim($_POST['cedula'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    
    $errores = [];
    
    if (empty($cedula)) {
        $errores[] = "La cédula es obligatoria";
    } elseif (!preg_match('/^[0-9]{6,10}$/', $cedula)) {
        $errores[] = "La cédula debe contener entre 6 y 10 dígitos numéricos";
    }
    
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($errores)) {
        $stmt = $pdo->prepare("SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo FROM clientes WHERE cedula = :cedula");
        $stmt->execute([':cedula' => $cedula]);
        
        if ($cliente_existente = $stmt->fetch()) {
            $_SESSION['error_cliente'] = "❌ CLIENTE YA EXISTE: Ya existe un cliente con cédula {$cedula} - {$cliente_existente['nombre_completo']}";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO clientes 
                    (prefijo_cedula, cedula, nombre, apellido, tipo_cliente, fecha_registro)
                    VALUES 
                    ('V', :cedula, :nombre, :apellido, 'ocasional', NOW())
                ");
                
                $stmt->execute([
                    ':cedula' => $cedula,
                    ':nombre' => $nombre,
                    ':apellido' => $apellido
                ]);
                
                $cliente_id = $pdo->lastInsertId();
                $venta_activa['cliente_id'] = $cliente_id;
                $venta_activa['ultima_actividad'] = date('Y-m-d H:i:s');
                
                $_SESSION['mensaje_cliente'] = "✅ CLIENTE REGISTRADO: {$nombre} {$apellido} (CI: V-{$cedula})";
                
                registrarActividad('Cliente registrado', 'Clientes', "Nuevo cliente: {$nombre} {$apellido} (CI: V-{$cedula})");
                
            } catch (Exception $e) {
                $_SESSION['error_cliente'] = "❌ ERROR AL REGISTRAR: " . $e->getMessage();
            }
        }
    } else {
        $_SESSION['error_cliente'] = "❌ ERRORES DE VALIDACIÓN:<br>" . implode("<br>", $errores);
    }
    
    header('Location: nueva.php');
    exit();
    
// --- SELECCIONAR CLIENTE ---
} elseif ($action === 'seleccionar_cliente') {
    $cliente_id = intval($_GET['cliente_id'] ?? 0);
    
    if ($cliente_id > 0) {
        $venta_activa['cliente_id'] = $cliente_id;
        $venta_activa['ultima_actividad'] = date('Y-m-d H:i:s');
        $_SESSION['mensaje_cliente'] = "✅ Cliente seleccionado correctamente";
    } else {
        $venta_activa['cliente_id'] = null;
        $venta_activa['ultima_actividad'] = date('Y-m-d H:i:s');
        $_SESSION['mensaje_carrito'] = "✅ Cliente deseleccionado";
    }
    
    header('Location: nueva.php');
    exit();
    
// --- GUARDAR MÉTODO DE PAGO Y REFERENCIA ---
} elseif ($action === 'guardar_pago' && isset($_POST['metodo_pago'])) {
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $referencia = trim($_POST['referencia'] ?? '');
    
    if (in_array($metodo_pago, ['tarjeta_debito', 'pago_movil'])) {
        $venta_activa['metodo_pago'] = $metodo_pago;
        $venta_activa['referencia'] = $referencia;
        $venta_activa['ultima_actividad'] = date('Y-m-d H:i:s');
        
        $_SESSION['mensaje_carrito'] = "✅ Método de pago guardado";
    }
    
    header('Location: nueva.php');
    exit();
    
// ==============================================
// PROCESAR VENTA COMPLETA
// ==============================================
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_venta'])) {
    // Validar que la venta activa tenga productos
    if (empty($venta_activa['productos'])) {
        $_SESSION['error_venta'] = "❌ CARRITO VACÍO: Agregue productos antes de finalizar la venta";
        header('Location: nueva.php');
        exit();
    }
    
    // Validar método de pago
    if (!in_array($venta_activa['metodo_pago'], ['tarjeta_debito', 'pago_movil'])) {
        $_SESSION['error_venta'] = "❌ MÉTODO DE PAGO INVÁLIDO: Seleccione Tarjeta Débito o Pago Móvil";
        header('Location: nueva.php');
        exit();
    }
    
    // Validar referencia
    $referencia = $venta_activa['referencia'] ?? '';
    if (strlen($referencia) !== 6) {
        $_SESSION['error_venta'] = "❌ REFERENCIA INVÁLIDA: La referencia debe tener exactamente 6 dígitos (tiene " . strlen($referencia) . ")";
        header('Location: nueva.php');
        exit();
    }
    
    if (!ctype_digit($referencia)) {
        $_SESSION['error_venta'] = "❌ REFERENCIA INVÁLIDA: Solo se permiten dígitos numéricos (0-9)";
        header('Location: nueva.php');
        exit();
    }
    
    // Verificar stock de todos los productos en carrito
    foreach ($venta_activa['productos'] as $producto_id => $item) {
        $stmt = $pdo->prepare("SELECT stock, nombre FROM productos WHERE id = :id");
        $stmt->execute([':id' => $producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            $_SESSION['error_venta'] = "❌ PRODUCTO NO DISPONIBLE: {$item['nombre']} ya no está disponible";
            header('Location: nueva.php');
            exit();
        }
        
        if ($producto['stock'] < $item['cantidad']) {
            $_SESSION['error_venta'] = "❌ STOCK INSUFICIENTE: {$producto['nombre']}. Disponible: {$producto['stock']}, Solicitado: {$item['cantidad']}";
            header('Location: nueva.php');
            exit();
        }
    }
    
    // Calcular totales
    $subtotal = 0;
    $detalles = [];
    
    foreach ($venta_activa['productos'] as $item) {
        $item_subtotal = $item['precio'] * $item['cantidad'];
        $subtotal += $item_subtotal;
        
        $detalles[] = [
            'producto_id' => $item['id'],
            'cantidad' => $item['cantidad'],
            'precio_unitario' => $item['precio'],
            'subtotal' => $item_subtotal
        ];
    }
    
    $total = $subtotal;
    
    // Preparar datos de venta
    $datos_venta = [
        'cajero_id' => $cajero_id,
        'cliente_id' => $venta_activa['cliente_id'],
        'subtotal' => $subtotal,
        'total' => $total,
        'metodo_pago' => $venta_activa['metodo_pago'],
        'referencia_pago' => $referencia
    ];
    
    // Registrar venta
    try {
        $pdo->beginTransaction();
        
        // Insertar venta
        $stmt = $pdo->prepare("
            INSERT INTO ventas 
            (cajero_id, cliente_id, subtotal, total, metodo_pago, referencia_pago, fecha_venta)
            VALUES 
            (:cajero_id, :cliente_id, :subtotal, :total, :metodo_pago, :referencia_pago, NOW())
        ");
        
        $stmt->execute([
            ':cajero_id' => $datos_venta['cajero_id'],
            ':cliente_id' => $datos_venta['cliente_id'],
            ':subtotal' => $datos_venta['subtotal'],
            ':total' => $datos_venta['total'],
            ':metodo_pago' => $datos_venta['metodo_pago'],
            ':referencia_pago' => $datos_venta['referencia_pago']
        ]);
        
        $venta_id = $pdo->lastInsertId();
        
        /**
        // Generar número de ticket
        $stmt = $pdo->prepare("SELECT generar_numero_ticket()");
        $stmt->execute();
        $numero_ticket = $stmt->fetchColumn();
        
        // Actualizar venta con número de ticket
        $stmt = $pdo->prepare("UPDATE ventas SET numero_ticket = :numero_ticket WHERE id = :id");
        $stmt->execute([
            ':numero_ticket' => $numero_ticket,
            ':id' => $venta_id
        ]);
        /**/

        // ========== GENERACIÓN DE TICKET A PRUEBA DE ERRORES ==========
        $intentos = 0;
        $max_intentos = 3;
        $numero_ticket = null;
        
        do {
            $intentos++;
            
            try {
                // Generar número de ticket usando la función MySQL
                $stmt = $pdo->prepare("SELECT generar_numero_ticket()");
                $stmt->execute();
                $numero_ticket = $stmt->fetchColumn();
                
                // Verificar si ya existe (por si acaso)
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE numero_ticket = :ticket");
                $stmt_check->execute([':ticket' => $numero_ticket]);
                
                if ($stmt_check->fetchColumn() == 0) {
                    // Ticket único, actualizar la venta
                    $stmt_update = $pdo->prepare("UPDATE ventas SET numero_ticket = :numero_ticket WHERE id = :id");
                    $stmt_update->execute([
                        ':numero_ticket' => $numero_ticket,
                        ':id' => $venta_id
                    ]);
                    
                    // Éxito, salir del loop
                    break;
                } else {
                    // Ticket duplicado, intentar de nuevo
                    error_log("Intento $intentos: Ticket duplicado $numero_ticket");
                    $numero_ticket = null;
                    
                    // Pequeña pausa antes de reintentar
                    if ($intentos < $max_intentos) {
                        usleep(100000); // 100ms
                    }
                }
                
            } catch (Exception $e) {
                error_log("Error en intento $intentos: " . $e->getMessage());
                $numero_ticket = null;
                
                if ($intentos < $max_intentos) {
                    usleep(100000); // 100ms
                }
            }
            
        } while ($intentos < $max_intentos && $numero_ticket === null);
        
        // Si después de los intentos no hay ticket, usar alternativa
        if ($numero_ticket === null) {
            $prefijo = 'TG';
            $fecha = date('ymd');
            $micro = substr(microtime(), 2, 4); // Últimos 4 dígitos del microtime
            
            $numero_ticket = "{$prefijo}-{$micro}-{$fecha}";
            
            // Verificar que no exista
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM ventas WHERE numero_ticket = :ticket");
            $stmt_check->execute([':ticket' => $numero_ticket]);
            
            if ($stmt_check->fetchColumn() > 0) {
                // Si por casualidad existe, agregar más dígitos
                $numero_ticket .= '-' . rand(10, 99);
            }
            
            // Actualizar con ticket alternativo
            $stmt = $pdo->prepare("UPDATE ventas SET numero_ticket = :numero_ticket WHERE id = :id");
            $stmt->execute([
                ':numero_ticket' => $numero_ticket,
                ':id' => $venta_id
            ]);
            
            error_log("Generado ticket alternativo: $numero_ticket");
        }
        // ========== FIN GENERACIÓN DE TICKET ==========
        
        // Insertar detalles de venta y actualizar stock
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
            
            $stmt = $pdo->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :producto_id");
            $stmt->execute([
                ':cantidad' => $detalle['cantidad'],
                ':producto_id' => $detalle['producto_id']
            ]);
        }
        
        $pdo->commit();
        
        // Eliminar venta pendiente procesada
        unset($_SESSION['ventas_pendientes'][$venta_activa_id]);
        
        // Si no hay más ventas pendientes, crear una nueva
        if (count($_SESSION['ventas_pendientes']) === 0) {
            $nueva_venta_id = uniqid('venta_', true);
            $_SESSION['ventas_pendientes'][$nueva_venta_id] = [
                'id' => $nueva_venta_id,
                'productos' => [],
                'cliente_id' => null,
                'metodo_pago' => null,
                'referencia' => null,
                'total' => 0,
                'items' => 0,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'ultima_actividad' => date('Y-m-d H:i:s')
            ];
            $_SESSION['venta_activa_id'] = $nueva_venta_id;
        } else {
            // Activar la primera venta disponible
            $_SESSION['venta_activa_id'] = array_key_first($_SESSION['ventas_pendientes']);
        }
        
        // Registrar actividad
        registrarActividad('Venta registrada', 'Ventas', "Venta #{$numero_ticket} por {$total}");
        
        // Redirigir a ticket
        header('Location: ticket.php?id=' . $venta_id);
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $_SESSION['error_venta'] = "❌ ERROR AL PROCESAR VENTA: " . $e->getMessage();
        header('Location: nueva.php');
        exit();
    }
}

// ==============================================
// FUNCIÓN AUXILIAR PARA CALCULAR TOTAL
// ==============================================
function calcularTotalVenta($productos) {
    $total = 0;
    foreach ($productos as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    return $total;
}

// ==============================================
// DATOS PARA LA VISTA
// ==============================================
// Obtener productos para mostrar
$stmt = $pdo->prepare("
    SELECT p.id, p.nombre, p.precio_venta, p.stock, c.nombre as categoria
    FROM productos p
    JOIN categorias_productos c ON p.categoria_id = c.id
    WHERE p.estado = 'activo'
    ORDER BY p.nombre
    LIMIT 50
");
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener información del cliente si está seleccionado en venta activa
$cliente_info = null;
if ($venta_activa['cliente_id']) {
    $stmt = $pdo->prepare("
        SELECT id, CONCAT(prefijo_cedula, '-', cedula) as cedula_completa,
               CONCAT(nombre, ' ', apellido) as nombre_completo
        FROM clientes 
        WHERE id = :id
    ");
    $stmt->execute([':id' => $venta_activa['cliente_id']]);
    $cliente_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Calcular estadísticas de ventas pendientes
$total_ventas_pendientes = count($_SESSION['ventas_pendientes']);
$ventas_pendientes_lista = $_SESSION['ventas_pendientes'];

// Ordenar por última actividad (más reciente primero)
uasort($ventas_pendientes_lista, function($a, $b) {
    return strtotime($b['ultima_actividad']) - strtotime($a['ultima_actividad']);
});

// Registrar actividad
registrarActividad('Acceso POS', 'Ventas', 'Ingresó al sistema de punto de venta');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta - Torre General</title>
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
            height: 100vh;
            overflow: hidden;
        }
        .pos-card {
            background: white;
            border: 1px solid rgba(209, 250, 229, 0.3);
            box-shadow: 0 4px 6px rgba(209, 250, 229, 0.1);
        }
        .producto-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(209, 250, 229, 0.2);
            border-color: var(--color-rol);
        }
        .carrito-item:hover {
            background: rgba(209, 250, 229, 0.1);
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
        .badge-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .venta-activa {
            border: 2px solid #10b981;
            background-color: rgba(16, 185, 129, 0.05);
        }
        .venta-pendiente {
            border: 1px solid #d1d5db;
        }
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior MEJORADA -->
    <nav class="bg-white shadow-md border-b border-green-100">
        <div class="container mx-auto px-6 py-3">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                <!-- Logo y título -->
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center pulse-animation">
                        <i class="fas fa-cash-register text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Punto de Venta - Nueva Venta</h1>
                        <p class="text-sm text-gray-600">Sistema Torre General | Cajero: <?php echo htmlspecialchars($usuario['nombre']); ?></p>
                        <p class="text-xs <?php echo $caja_abierta ? 'text-green-600' : 'text-red-600'; ?>">
                            <i class="fas fa-<?php echo $caja_abierta ? 'check-circle' : 'times-circle'; ?>"></i>
                            Caja: <?php echo $caja_abierta ? 'ABIERTA' : 'CERRADA'; ?>
                        </p>
                    </div>
                </div>
                
                <!-- Selector de ventas pendientes -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Venta activa:</span>
                        <div class="relative">
                            <button id="dropdown-ventas" class="px-3 py-1 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 flex items-center space-x-2">
                                <span class="font-medium">#<?php echo substr($venta_activa_id, -4); ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                                <span class="badge-success text-xs px-2 py-0.5 rounded-full">
                                    <?php echo $venta_activa['items']; ?> items
                                </span>
                            </button>
                            <div id="menu-ventas" class="hidden absolute right-0 mt-1 w-64 bg-white rounded-lg shadow-xl py-2 z-50 border border-gray-200 max-h-80 overflow-y-auto scrollbar-thin">
                                <div class="px-3 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-700">Ventas pendientes (<?php echo $total_ventas_pendientes; ?>/10)</p>
                                </div>
                                
                                <?php foreach ($ventas_pendientes_lista as $venta): 
                                    $es_activa = $venta['id'] === $venta_activa_id;
                                    $hace_cuanto = tiempoRelativo($venta['ultima_actividad']);
                                ?>
                                <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer border-l-4 <?php echo $es_activa ? 'border-green-500 bg-green-50' : 'border-transparent'; ?>"
                                     onclick="cambiarVenta('<?php echo $venta['id']; ?>')">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-medium text-sm">#<?php echo substr($venta['id'], -4); ?></span>
                                            <?php if ($es_activa): ?>
                                                <span class="badge-success text-xs px-2 py-0.5 rounded-full">Activa</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-gray-500"><?php echo $hace_cuanto; ?></span>
                                    </div>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-xs text-gray-600"><?php echo $venta['items']; ?> items</span>
                                        <span class="text-sm font-bold text-green-600"><?php echo number_format($venta['total'], 2); ?></span>
                                    </div>
                                    <?php if (!$es_activa): ?>
                                    <div class="mt-1 flex justify-end">
                                        <button onclick="event.stopPropagation(); eliminarVenta('<?php echo $venta['id']; ?>');" 
                                                class="text-xs text-red-500 hover:text-red-700 hover:bg-red-50 px-2 py-1 rounded">
                                            <i class="fas fa-trash-alt mr-1"></i> Eliminar
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="px-3 py-2 border-t border-gray-100">
                                    <button onclick="crearNuevaVenta()" 
                                            class="w-full text-center text-green-600 hover:text-green-800 text-sm font-medium hover:bg-green-50 py-2 rounded">
                                        <i class="fas fa-plus mr-2"></i> Nueva venta
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Indicadores -->
                    <div class="flex items-center space-x-4">
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Items</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo $venta_activa['items']; ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Total</p>
                            <p class="text-2xl font-bold text-emerald-700"><?php echo number_format($venta_activa['total'], 2); ?></p>
                        </div>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="flex items-center space-x-2">
                        <a href="../index.php" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center text-sm">
                            <i class="fas fa-arrow-left mr-1"></i> Volver
                        </a>
                        <a href="nueva.php?action=limpiar" 
                           class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 flex items-center text-sm" 
                           onclick="return confirm('¿Está seguro de vaciar esta venta?')">
                            <i class="fas fa-trash-alt mr-1"></i> Vaciar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mensajes de alerta -->
    <div class="container mx-auto px-6 pt-4">
        <?php if (isset($_SESSION['mensaje_carrito'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800"><?php echo $_SESSION['mensaje_carrito']; unset($_SESSION['mensaje_carrito']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_stock'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800"><?php echo $_SESSION['error_stock']; unset($_SESSION['error_stock']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_venta'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800"><?php echo $_SESSION['error_venta']; unset($_SESSION['error_venta']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_cliente'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-800"><?php echo $_SESSION['error_cliente']; unset($_SESSION['error_cliente']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['mensaje_cliente'])): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-800"><?php echo $_SESSION['mensaje_cliente']; unset($_SESSION['mensaje_cliente']); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contenido Principal - 3 Columnas -->
    <div class="container mx-auto px-6 py-4 h-[calc(100vh-200px)]">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-full">
            
            <!-- Columna 1: Catálogo de Productos -->
            <div class="lg:col-span-2 flex flex-col">
                <div class="pos-card rounded-xl p-4 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Catálogo de Productos</h2>
                        <div class="flex items-center space-x-4">
                            <!-- Filtro por categoría -->
                            <select id="filtro-categoria" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- Búsqueda -->
                            <div class="relative">
                                <input type="text" id="buscar-producto" 
                                       placeholder="Buscar producto..." 
                                       class="border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 w-64">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de productos -->
                    <div id="lista-productos" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 overflow-y-auto scrollbar-thin flex-1">
                        <?php foreach ($productos as $producto): 
                            $stock_bajo = $producto['stock'] <= 5;
                            $sin_stock = $producto['stock'] <= 0;
                        ?>
                        <div class="producto-card border border-gray-200 rounded-lg p-3 cursor-pointer transition-all duration-200 <?php echo $sin_stock ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                             data-id="<?php echo $producto['id']; ?>"
                             data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                             data-precio="<?php echo $producto['precio_venta']; ?>"
                             data-stock="<?php echo $producto['stock']; ?>"
                             data-categoria="<?php echo $producto['categoria']; ?>"
                             onclick="<?php echo $sin_stock ? '' : "agregarAlCarrito({$producto['id']})"; ?>">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($producto['nombre']); ?></h3>
                                <?php if ($stock_bajo && !$sin_stock): ?>
                                    <span class="badge-warning text-xs px-2 py-1 rounded">Stock bajo</span>
                                <?php elseif ($sin_stock): ?>
                                    <span class="badge-error text-xs px-2 py-1 rounded">Agotado</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($producto['categoria']); ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-green-600"><?php echo number_format($producto['precio_venta'], 2); ?></span>
                                <span class="text-sm <?php echo $sin_stock ? 'text-red-500' : ($stock_bajo ? 'text-amber-500' : 'text-gray-500'); ?>">
                                    Stock: <?php echo $producto['stock']; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Contador de productos -->
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p class="text-sm text-gray-600">
                            Mostrando <span id="contador-productos"><?php echo count($productos); ?></span> productos
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Columna 2: Carrito de Compras -->
            <div class="flex flex-col space-y-6 h-full">
                <!-- Carrito -->
                <div class="pos-card rounded-xl p-4 flex-1 flex flex-col max-h-[500px]">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Carrito de Compras</h2>
                        <span class="badge-success text-sm px-3 py-1 rounded-full">
                            <?php echo $venta_activa['items']; ?> item(s)
                        </span>
                    </div>
                    
                    <!-- Items del carrito -->
                    <div id="items-carrito" class="flex-1 overflow-y-auto scrollbar-thin mb-4">
                        <?php if (empty($venta_activa['productos'])): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-shopping-cart text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500">El carrito está vacío</p>
                                <p class="text-sm text-gray-400">Agregue productos desde el catálogo</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($venta_activa['productos'] as $item): ?>
                            <div class="carrito-item border-b border-gray-100 py-3 px-2 flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($item['nombre']); ?></h4>
                                    <div class="flex items-center space-x-4 mt-1">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="cambiarCantidad(<?php echo $item['id']; ?>, -1)" 
                                                    class="w-6 h-6 bg-gray-100 rounded flex items-center justify-center hover:bg-gray-200">
                                                <i class="fas fa-minus text-gray-600 text-xs"></i>
                                            </button>
                                            <input type="number" 
                                                   id="cantidad-<?php echo $item['id']; ?>" 
                                                   value="<?php echo $item['cantidad']; ?>" 
                                                   min="1" 
                                                   max="<?php echo $item['stock_disponible']; ?>"
                                                   class="w-12 text-center border border-gray-300 rounded py-1 text-sm"
                                                   onchange="actualizarCantidad(<?php echo $item['id']; ?>, this.value)">
                                            <button onclick="cambiarCantidad(<?php echo $item['id']; ?>, 1)" 
                                                    class="w-6 h-6 bg-gray-100 rounded flex items-center justify-center hover:bg-gray-200">
                                                <i class="fas fa-plus text-gray-600 text-xs"></i>
                                            </button>
                                        </div>
                                        <span class="text-green-600 font-bold">
                                            <?php echo number_format($item['precio'], 2); ?> × <?php echo $item['cantidad']; ?> = 
                                            <?php echo number_format($item['precio'] * $item['cantidad'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                                <button onclick="eliminarDelCarrito(<?php echo $item['id']; ?>)" 
                                        class="ml-2 text-red-500 hover:text-red-700 hover:bg-red-50 p-1 rounded">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Resumen del carrito -->
                    <div class="border-t border-gray-200 pt-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-bold"><?php echo number_format($venta_activa['total'], 2); ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">IVA (0%):</span>
                            <span class="font-bold">0.00</span>
                        </div>
                        <div class="flex justify-between mb-4 text-lg font-bold border-t pt-2">
                            <span class="text-gray-800">TOTAL:</span>
                            <span class="text-emerald-700"><?php echo number_format($venta_activa['total'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Información de Cliente y Pago -->
                <div class="pos-card rounded-xl p-4 flex-1 flex flex-col max-h-[250px]">
                    <!-- Selección de Cliente -->
                    <div class="mb-4">
                        <h3 class="font-bold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-user mr-2"></i> Cliente
                        </h3>
                        
                        <?php if ($cliente_info): ?>
                            <div class="badge-success border rounded-lg p-3 mb-2">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="font-medium"><?php echo htmlspecialchars($cliente_info['nombre_completo']); ?></p>
                                        <p class="text-sm">CI: <?php echo $cliente_info['cedula_completa']; ?></p>
                                    </div>
                                    <a href="nueva.php?action=seleccionar_cliente&cliente_id=0" 
                                       class="text-red-500 hover:text-red-700 hover:bg-red-50 p-1 rounded"
                                       title="Deseleccionar cliente">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-2">
                                <div class="relative">
                                    <input type="text" 
                                           id="buscar-cliente" 
                                           placeholder="Buscar por cédula (solo números) o nombre..." 
                                           class="w-full border border-gray-300 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                           title="Escriba números de cédula o nombre del cliente">
                                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                </div>
                                <div id="resultados-cliente" class="hidden absolute z-10 w-full bg-white border border-gray-300 rounded-lg mt-1 shadow-lg max-h-60 overflow-y-auto scrollbar-thin"></div>
                                
                                <div id="info-busqueda" class="text-xs text-gray-500 mt-1 hidden">
                                    <i class="fas fa-info-circle"></i> Escriba al menos 2 caracteres para buscar
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button onclick="mostrarModalCliente()" 
                                        class="text-green-600 hover:text-green-800 text-sm font-medium hover:bg-green-50 px-3 py-1 rounded">
                                    <i class="fas fa-user-plus mr-1"></i> Registrar cliente nuevo
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Método de Pago -->
                    <div class="mb-4">
                        <h3 class="font-bold text-gray-800 mb-2 flex items-center">
                            <i class="fas fa-credit-card mr-2"></i> Método de Pago
                        </h3>
                        
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <label class="flex items-center p-2 border <?php echo $venta_activa['metodo_pago'] === 'tarjeta_debito' ? 'border-green-500 bg-green-50' : 'border-gray-300'; ?> rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="metodo_pago" value="tarjeta_debito" class="mr-2" 
                                       <?php echo $venta_activa['metodo_pago'] === 'tarjeta_debito' ? 'checked' : ''; ?>
                                       onchange="guardarMetodoPago(this.value)">
                                <div>
                                    <p class="font-medium text-sm">Tarjeta Débito</p>
                                    <p class="text-xs text-gray-500">Últimos 6 dígitos</p>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-2 border <?php echo $venta_activa['metodo_pago'] === 'pago_movil' ? 'border-green-500 bg-green-50' : 'border-gray-300'; ?> rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="metodo_pago" value="pago_movil" class="mr-2"
                                       <?php echo $venta_activa['metodo_pago'] === 'pago_movil' ? 'checked' : ''; ?>
                                       onchange="guardarMetodoPago(this.value)">
                                <div>
                                    <p class="font-medium text-sm">Pago Móvil</p>
                                    <p class="text-xs text-gray-500">Referencia 6 dígitos</p>
                                </div>
                            </label>
                        </div>
                        
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Número de Referencia (6 dígitos)</label>
                                   <!--
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,6)"
                                   onblur="guardarReferencia(this.value)">
                                   -->
                            <input type="text" 
                                   id="referencia-pago" 
                                   maxlength="6" 
                                   pattern="\d{6}"
                                   placeholder="123456"
                                   value="<?php echo htmlspecialchars($venta_activa['referencia'] ?? ''); ?>"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                   oninput="actualizarReferenciaEnTiempoReal(this)"
                                   onblur="guardarReferencia(this.value)">
                                   
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-xs text-gray-500">Ingrese 6 dígitos de la transacción</p>
                                <span id="referencia-contador" class="text-xs <?php echo (strlen($venta_activa['referencia'] ?? '') === 6) ? 'text-green-600' : 'text-gray-400'; ?>">
                                    <?php echo strlen($venta_activa['referencia'] ?? ''); ?>/6
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botón Finalizar Venta -->
                    <!-- form method="POST" action="nueva.php" onsubmit="return validarVenta()" class="mt-auto" -->
                    <!--
                    <form method="POST" action="nueva.php" onsubmit="return validarVenta()" 
                    onkeydown="manejarEnterFormulario(event)" class="mt-auto" id="form-finalizar">
                    -->
                        
                    <form method="POST" action="nueva.php" onsubmit="return validarVenta()" class="mt-auto" id="form-finalizar">
                        
                        <input type="hidden" name="finalizar_venta" value="1">
                        
                        <!-- botón sin ID
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-green-600 to-emerald-700 text-white font-bold py-3 rounded-xl hover:shadow-lg transition-all text-lg flex items-center justify-center <?php echo empty($venta_activa['productos']) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php // echo empty($venta_activa['productos']) ? 'disabled' : ''; ?>
                                title="<?php // echo empty($venta_activa['productos']) ? 'Agregue productos al carrito primero' : 'Finalizar venta'; ?>">
                            <i class="fas fa-check-circle mr-2"></i>
                            FINALIZAR VENTA
                        </button>
                        -->

                        <button type="submit" 
                                id="btn-finalizar"
                                class="w-full bg-gradient-to-r from-green-600 to-emerald-700 text-white font-bold py-3 rounded-xl hover:shadow-lg transition-all text-lg flex items-center justify-center <?php echo empty($venta_activa['productos']) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                <?php echo empty($venta_activa['productos']) ? 'disabled' : ''; ?>
                                title="<?php echo empty($venta_activa['productos']) ? 'Agregue productos al carrito primero' : 'Finalizar venta'; ?>">                        
                            <i class="fas fa-check-circle mr-2"></i>
                            FINALIZAR VENTA
                        </button>
                        
                        <div class="mt-2 text-center">
                            <p class="text-sm text-gray-600">
                                Total a cobrar: <span class="font-bold text-emerald-700 text-lg"><?php echo number_format($venta_activa['total'], 2); ?></span>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para registrar nuevo cliente -->
    <div id="modal-cliente" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Registrar Nuevo Cliente</h3>
                <button onclick="cerrarModalCliente()" class="text-gray-500 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="nueva.php" id="form-cliente" onsubmit="return validarClienteRapido()">
                <input type="hidden" name="action" value="registrar_cliente">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">
                            Cédula <span class="text-red-500">*</span>
                            <span class="text-xs text-gray-500">(solo números, 6-10 dígitos)</span>
                        </label>
                        <input type="text" 
                               name="cedula" 
                               id="cedula-cliente"
                               required
                               pattern="[0-9]{6,10}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Ej: 12345678"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                    
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nombre" 
                               id="nombre-cliente"
                               required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Nombre del cliente">
                    </div>
                    
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">
                            Apellido
                        </label>
                        <input type="text" 
                               name="apellido" 
                               id="apellido-cliente"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                               placeholder="Apellido del cliente">
                    </div>
                </div>
                
                <div id="errores-cliente" class="hidden mt-3 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700"></div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            onclick="cerrarModalCliente()"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-700 text-white rounded-lg hover:shadow-lg transition-all">
                        <i class="fas fa-check mr-1"></i> Registrar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Variables globales
        let productosOriginales = <?php echo json_encode($productos); ?>;
        let timeoutBusqueda = null;
        let timeoutCliente = null;
        let ventaActivaId = '<?php echo $venta_activa_id; ?>';
        
        // ==============================================
        // FUNCIONES DEL SISTEMA DE VENTAS PENDIENTES
        // ==============================================
        
        function crearNuevaVenta() {
            window.location.href = 'nueva.php?action=nueva_venta';
        }
        
        function cambiarVenta(ventaId) {
            window.location.href = 'nueva.php?action=cambiar_venta&venta_id=' + encodeURIComponent(ventaId);
        }
        
        function eliminarVenta(ventaId) {
            if (confirm('¿Está seguro de eliminar esta venta pendiente? Se perderán todos los productos agregados.')) {
                window.location.href = 'nueva.php?action=eliminar_venta&venta_id=' + encodeURIComponent(ventaId);
            }
        }
        
        // Dropdown de ventas
        document.getElementById('dropdown-ventas').addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('menu-ventas');
            menu.classList.toggle('hidden');
        });
        
        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('menu-ventas');
            const button = document.getElementById('dropdown-ventas');
            
            if (!button.contains(event.target) && menu && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
        
        // ==============================================
        // FUNCIONES DEL CARRITO
        // ==============================================
        
        function agregarAlCarrito(productoId) {
            const cantidad = 1; // Cantidad fija por defecto
            
            if (cantidad < 1) {
                mostrarError('La cantidad debe ser al menos 1');
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'nueva.php';
            
            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'agregar';
            form.appendChild(inputAction);
            
            const inputProductoId = document.createElement('input');
            inputProductoId.type = 'hidden';
            inputProductoId.name = 'producto_id';
            inputProductoId.value = productoId;
            form.appendChild(inputProductoId);
            
            const inputCantidad = document.createElement('input');
            inputCantidad.type = 'hidden';
            inputCantidad.name = 'cantidad';
            inputCantidad.value = cantidad;
            form.appendChild(inputCantidad);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function cambiarCantidad(productoId, delta) {
            const input = document.getElementById(`cantidad-${productoId}`);
            if (!input) return;
            
            let nuevaCantidad = parseInt(input.value) + delta;
            if (nuevaCantidad < 1) nuevaCantidad = 1;
            
            input.value = nuevaCantidad;
            actualizarCantidad(productoId, nuevaCantidad);
        }
        
        function actualizarCantidad(productoId, cantidad) {
            cantidad = parseInt(cantidad);
            if (isNaN(cantidad) || cantidad < 1) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'nueva.php';
            
            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'actualizar';
            form.appendChild(inputAction);
            
            const inputProductoId = document.createElement('input');
            inputProductoId.type = 'hidden';
            inputProductoId.name = 'producto_id';
            inputProductoId.value = productoId;
            form.appendChild(inputProductoId);
            
            const inputCantidad = document.createElement('input');
            inputCantidad.type = 'hidden';
            inputCantidad.name = 'cantidad';
            inputCantidad.value = cantidad;
            form.appendChild(inputCantidad);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function eliminarDelCarrito(productoId) {
            if (confirm('¿Está seguro de eliminar este producto del carrito?')) {
                window.location.href = `nueva.php?action=eliminar&producto_id=${productoId}`;
            }
        }
        
        // ==============================================
        // BÚSQUEDA DE PRODUCTOS
        // ==============================================
        
        document.getElementById('buscar-producto').addEventListener('input', function(e) {
            clearTimeout(timeoutBusqueda);
            
            timeoutBusqueda = setTimeout(() => {
                const busqueda = e.target.value.toLowerCase().trim();
                const filtroCategoria = document.getElementById('filtro-categoria').value;
                
                const productosFiltrados = productosOriginales.filter(producto => {
                    const coincideBusqueda = !busqueda || 
                        producto.nombre.toLowerCase().includes(busqueda) ||
                        producto.categoria.toLowerCase().includes(busqueda);
                    
                    const coincideCategoria = !filtroCategoria || 
                        producto.categoria_id == filtroCategoria;
                    
                    return coincideBusqueda && coincideCategoria;
                });
                
                actualizarListaProductos(productosFiltrados);
                document.getElementById('contador-productos').textContent = productosFiltrados.length;
            }, 300);
        });
        
        document.getElementById('filtro-categoria').addEventListener('change', function() {
            document.getElementById('buscar-producto').dispatchEvent(new Event('input'));
        });
        
        function actualizarListaProductos(productos) {
            const contenedor = document.getElementById('lista-productos');
            contenedor.innerHTML = '';
            
            if (productos.length === 0) {
                contenedor.innerHTML = `
                    <div class="col-span-4 text-center py-8">
                        <i class="fas fa-search text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">No se encontraron productos</p>
                        <p class="text-sm text-gray-400">Intente con otros términos de búsqueda</p>
                    </div>
                `;
                return;
            }
            
            productos.forEach(producto => {
                const stockBajo = producto.stock <= 5;
                const sinStock = producto.stock <= 0;
                
                const productoHTML = `
                    <div class="producto-card border border-gray-200 rounded-lg p-3 cursor-pointer transition-all duration-200 ${sinStock ? 'opacity-50 cursor-not-allowed' : 'hover:border-green-300'}"
                         data-id="${producto.id}"
                         data-nombre="${producto.nombre}"
                         data-precio="${producto.precio_venta}"
                         data-stock="${producto.stock}"
                         data-categoria="${producto.categoria}"
                         onclick="${sinStock ? 'mostrarError(\'Producto agotado\')' : `agregarAlCarrito(${producto.id})`}">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-semibold text-gray-800 truncate" title="${producto.nombre}">${producto.nombre}</h3>
                            ${stockBajo && !sinStock ? '<span class="badge-warning text-xs px-2 py-1 rounded">Stock bajo</span>' : ''}
                            ${sinStock ? '<span class="badge-error text-xs px-2 py-1 rounded">Agotado</span>' : ''}
                        </div>
                        <p class="text-sm text-gray-600 mb-2">${producto.categoria}</p>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-green-600">${parseFloat(producto.precio_venta).toFixed(2)}</span>
                            <span class="text-sm ${sinStock ? 'text-red-500' : (stockBajo ? 'text-amber-500' : 'text-gray-500')}">
                                Stock: ${producto.stock}
                            </span>
                        </div>
                    </div>
                `;
                
                contenedor.innerHTML += productoHTML;
            });
        }
        
        // ==============================================
        // BÚSQUEDA DE CLIENTES
        // ==============================================
        
        const buscarClienteInput = document.getElementById('buscar-cliente');
        if (buscarClienteInput) {
            buscarClienteInput.addEventListener('input', function(e) {
                clearTimeout(timeoutCliente);
                
                const infoBusqueda = document.getElementById('info-busqueda');
                if (infoBusqueda) {
                    infoBusqueda.classList.remove('hidden');
                }
                
                const busqueda = e.target.value.trim();
                
                if (busqueda.length >= 2) {
                    timeoutCliente = setTimeout(() => {
                        buscarClientes(busqueda);
                    }, 500);
                } else {
                    document.getElementById('resultados-cliente').classList.add('hidden');
                }
            });
            
            buscarClienteInput.addEventListener('focus', function() {
                const busqueda = this.value.trim();
                if (busqueda.length >= 2) {
                    buscarClientes(busqueda);
                }
            });
        }
        
        function buscarClientes(busqueda) {
            fetch(`nueva.php?action=buscar_cliente&q=${encodeURIComponent(busqueda)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    return response.json();
                })
                .then(clientes => {
                    const resultados = document.getElementById('resultados-cliente');
                    resultados.innerHTML = '';
                    
                    if (clientes.length === 0) {
                        resultados.innerHTML = `
                            <div class="p-4 text-center text-gray-500">
                                <i class="fas fa-user-slash text-2xl mb-2"></i>
                                <p>No se encontraron clientes</p>
                                <p class="text-xs mt-1">Búsqueda: "${busqueda}"</p>
                                <button onclick="mostrarModalCliente()" 
                                        class="mt-2 text-green-600 hover:text-green-800 text-sm font-medium">
                                    <i class="fas fa-user-plus mr-1"></i> Registrar nuevo cliente
                                </button>
                            </div>
                        `;
                    } else {
                        clientes.forEach(cliente => {
                            const item = document.createElement('div');
                            item.className = 'p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors';
                            item.innerHTML = `
                                <div class="font-medium text-gray-800">${cliente.nombre_completo}</div>
                                <div class="text-sm text-gray-600">CI: ${cliente.cedula_completa}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    ${cliente.telefono ? `<i class="fas fa-phone mr-1"></i>${cliente.telefono}` : ''}
                                    ${cliente.email ? `<br><i class="fas fa-envelope mr-1"></i>${cliente.email}` : ''}
                                </div>
                            `;
                            item.onclick = () => {
                                window.location.href = `nueva.php?action=seleccionar_cliente&cliente_id=${cliente.id}`;
                            };
                            resultados.appendChild(item);
                        });
                        
                        /**
                        const nuevoItem = document.createElement('div');
                        nuevoItem.className = 'p-3 border-t border-gray-200 bg-gray-50 hover:bg-gray-100 cursor-pointer';
                        nuevoItem.innerHTML = `
                            <div class="font-medium text-green-700">
                                <i class="fas fa-user-plus mr-2"></i> Registrar nuevo cliente
                            </div>
                            <div class="text-xs text-gray-600">¿No encuentra al cliente?</div>
                        `;
                        nuevoItem.onclick = mostrarModalCliente;
                        resultados.appendChild(nuevoItem);
                        /**/
                    }
                    
                    resultados.classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error buscando clientes:', error);
                    const resultados = document.getElementById('resultados-cliente');
                    resultados.innerHTML = `
                        <div class="p-3 text-red-500">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Error al buscar clientes. Intente nuevamente.
                        </div>
                    `;
                    resultados.classList.remove('hidden');
                });
        }
        
        // Cerrar resultados al hacer clic fuera
        document.addEventListener('click', function(e) {
            const resultados = document.getElementById('resultados-cliente');
            const buscador = document.getElementById('buscar-cliente');
            
            if (resultados && !resultados.contains(e.target) && buscador && !buscador.contains(e.target)) {
                resultados.classList.add('hidden');
            }
        });
        
        // ==============================================
        // MÉTODO DE PAGO Y REFERENCIA
        // ==============================================
        
        function guardarMetodoPago(metodo) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'nueva.php';
            
            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'guardar_pago';
            form.appendChild(inputAction);
            
            const inputMetodo = document.createElement('input');
            inputMetodo.type = 'hidden';
            inputMetodo.name = 'metodo_pago';
            inputMetodo.value = metodo;
            form.appendChild(inputMetodo);
            
            const referenciaInput = document.getElementById('referencia-pago');
            if (referenciaInput && referenciaInput.value) {
                const inputReferencia = document.createElement('input');
                inputReferencia.type = 'hidden';
                inputReferencia.name = 'referencia';
                inputReferencia.value = referenciaInput.value;
                form.appendChild(inputReferencia);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function guardarReferencia(referencia) {
            if (referencia.length === 6) {
                const metodoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
                if (metodoSeleccionado) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'nueva.php';
                    
                    const inputAction = document.createElement('input');
                    inputAction.type = 'hidden';
                    inputAction.name = 'action';
                    inputAction.value = 'guardar_pago';
                    form.appendChild(inputAction);
                    
                    const inputMetodo = document.createElement('input');
                    inputMetodo.type = 'hidden';
                    inputMetodo.name = 'metodo_pago';
                    inputMetodo.value = metodoSeleccionado.value;
                    form.appendChild(inputMetodo);
                    
                    const inputReferencia = document.createElement('input');
                    inputReferencia.type = 'hidden';
                    inputReferencia.name = 'referencia';
                    inputReferencia.value = referencia;
                    form.appendChild(inputReferencia);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }
        
        // Contador de referencia
        const referenciaInput = document.getElementById('referencia-pago');
        if (referenciaInput) {
            referenciaInput.addEventListener('input', function() {
                const contador = document.getElementById('referencia-contador');
                if (contador) {
                    const longitud = this.value.length;
                    contador.textContent = `${longitud}/6`;
                    contador.className = `text-xs ${longitud === 6 ? 'text-green-600' : 'text-gray-400'}`;
                }
            });
        }
        
        // ==============================================
        // MODAL CLIENTE
        // ==============================================
        
        function mostrarModalCliente() {
            document.getElementById('modal-cliente').classList.remove('hidden');
            document.getElementById('cedula-cliente').focus();
            document.getElementById('resultados-cliente')?.classList.add('hidden');
        }
        
        function cerrarModalCliente() {
            document.getElementById('modal-cliente').classList.add('hidden');
            document.getElementById('errores-cliente').classList.add('hidden');
            document.getElementById('errores-cliente').innerHTML = '';
        }
        
        function validarClienteRapido() {
            const cedula = document.getElementById('cedula-cliente').value.trim();
            const nombre = document.getElementById('nombre-cliente').value.trim();
            const erroresDiv = document.getElementById('errores-cliente');
            
            let errores = [];
            
            if (!cedula) {
                errores.push('La cédula es obligatoria');
            } else if (!/^[0-9]{6,10}$/.test(cedula)) {
                errores.push('La cédula debe contener entre 6 y 10 dígitos numéricos');
            }
            
            if (!nombre) {
                errores.push('El nombre es obligatorio');
            } else if (nombre.length < 2) {
                errores.push('El nombre debe tener al menos 2 caracteres');
            }
            
            if (errores.length > 0) {
                erroresDiv.innerHTML = '<strong>Errores de validación:</strong><br>' + errores.join('<br>');
                erroresDiv.classList.remove('hidden');
                return false;
            }
            
            erroresDiv.classList.add('hidden');
            return true;
        }
        
        // ==============================================
        // VALIDACIÓN DE VENTA
        // ==============================================

        /**
        function validarVenta() {
            const productosVacios = <?php // echo empty($venta_activa['productos']) ? 'true' : 'false'; ?>;
            
            if (productosVacios) {
                mostrarError('El carrito está vacío. Agregue productos antes de finalizar la venta.');
                return false;
            }
            
            const metodoPago = '<?php // echo $venta_activa['metodo_pago'] ?? ''; ?>';
            if (!metodoPago) {
                mostrarError('Seleccione un método de pago (Tarjeta Débito o Pago Móvil).');
                return false;
            }
            
            const referencia = '<?php // echo $venta_activa['referencia'] ?? ''; ?>';
            if (referencia.length !== 6) {
                mostrarError(`La referencia debe tener exactamente 6 dígitos. Actual: ${referencia.length} dígitos.`);
                document.getElementById('referencia-pago').focus();
                return false;
            }
            
            if (!/^\d{6}$/.test(referencia)) {
                mostrarError('La referencia solo puede contener dígitos numéricos (0-9).');
                document.getElementById('referencia-pago').focus();
                document.getElementById('referencia-pago').select();
                return false;
            }
            
            const totalVenta = <?php // echo number_format($venta_activa['total'], 2); ?>;
            const metodoPagoTexto = metodoPago === 'tarjeta_debito' ? 'Tarjeta Débito' : 'Pago Móvil';
            
            return confirm(`¿CONFIRMAR VENTA?\n\nVenta #${ventaActivaId.slice(-4)}\nTotal: ${totalVenta}\nMétodo: ${metodoPagoTexto}\nReferencia: ${referencia}\n\n¿Desea proceder con la venta?`);
        }
        /**/

            function validarVenta() {
                const productosVacios = <?php echo empty($venta_activa['productos']) ? 'true' : 'false'; ?>;
                
                if (productosVacios) {
                    alert('❌ El carrito está vacío. Agregue productos antes de finalizar la venta.');
                    return false;
                }
                
                // Obtener datos ACTUALES del formulario
                const metodoPago = document.querySelector('input[name="metodo_pago"]:checked')?.value || '';
                const referencia = document.getElementById('referencia-pago')?.value || '';
                
                // Validación 1: Método de pago
                if (!metodoPago) {
                    alert('❌ Seleccione un método de pago (Tarjeta Débito o Pago Móvil).');
                    
                    // Enfocar el primer radio button
                    const primerRadio = document.querySelector('input[name="metodo_pago"]');
                    if (primerRadio) primerRadio.focus();
                    
                    return false;
                }
                
                // Validación 2: Referencia de 6 dígitos
                if (referencia.length !== 6) {
                    alert(`❌ La referencia debe tener exactamente 6 dígitos. Actual: ${referencia.length} dígitos.`);
                    document.getElementById('referencia-pago').focus();
                    return false;
                }
                
                if (!/^\d{6}$/.test(referencia)) {
                    alert('❌ La referencia solo puede contener dígitos numéricos (0-9).');
                    document.getElementById('referencia-pago').focus();
                    document.getElementById('referencia-pago').select();
                    return false;
                }
                
                // Última confirmación
                const totalVenta = <?php echo number_format($venta_activa['total'], 2); ?>;
                const metodoPagoTexto = metodoPago === 'tarjeta_debito' ? 'Tarjeta Débito' : 'Pago Móvil';
                
                return confirm(`¿CONFIRMAR VENTA?\n\nVenta #${ventaActivaId.slice(-4)}\nTotal: ${totalVenta}\nMétodo: ${metodoPagoTexto}\nReferencia: ${referencia}\n\n¿Desea proceder con la venta?`);
            }       
        
        // ==============================================
        // FUNCIONES AUXILIARES
        // ==============================================
        
        function mostrarError(mensaje) {
            alert('❌ ' + mensaje);
        }
        
            // ==============================================
            // FUNCIONES MEJORADAS DE REFERENCIA Y ENTER
            // ==============================================

            /**            
            function actualizarReferenciaEnTiempoReal(input) {
                // Limpiar y limitar a 6 dígitos
                input.value = input.value.replace(/[^0-9]/g, '').slice(0,6);
                
                // Actualizar contador
                const contador = document.getElementById('referencia-contador');
                if (contador) {
                    const longitud = input.value.length;
                    contador.textContent = `${longitud}/6`;
                    contador.className = `text-xs ${longitud === 6 ? 'text-green-600' : 'text-gray-400'}`;
                }
                
                // Guardar automáticamente cuando llega a 6 dígitos
                if (input.value.length === 6) {
                    guardarReferencia(input.value);
                    
                    // Mover foco al botón de finalizar para facilitar Enter
                    setTimeout(() => {
                        document.getElementById('btn-finalizar')?.focus();
                    }, 100);
                }
            }
            /**/
            
                function actualizarReferenciaEnTiempoReal(input) {
                    // Limpiar y limitar a 6 dígitos
                    input.value = input.value.replace(/[^0-9]/g, '').slice(0,6);
                    
                    // Actualizar contador
                    const contador = document.getElementById('referencia-contador');
                    if (contador) {
                        const longitud = input.value.length;
                        contador.textContent = `${longitud}/6`;
                        contador.className = `text-xs ${longitud === 6 ? 'text-green-600' : 'text-gray-400'}`;
                    }
                    
                    // SOLO guardar en sesión (sin recargar) cuando llega a 6 dígitos
                    if (input.value.length === 6) {
                        const metodoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
                        
                        if (!metodoSeleccionado) {
                            // Si no hay método seleccionado, seleccionar Pago Móvil por defecto
                            const pagoMovilRadio = document.querySelector('input[name="metodo_pago"][value="pago_movil"]');
                            if (pagoMovilRadio) {
                                pagoMovilRadio.checked = true;
                                // Actualizar visualmente
                                pagoMovilRadio.closest('label').classList.add('border-green-500', 'bg-green-50');
                                pagoMovilRadio.closest('label').classList.remove('border-gray-300');
                                
                                // Guardar ambos (método y referencia) con AJAX
                                guardarPagoYReferencia('pago_movil', input.value);
                            }
                        } else {
                            // Guardar referencia con AJAX
                            guardarPagoYReferencia(metodoSeleccionado.value, input.value);
                        }
                        
                        // OPCIÓN: Puedes comentar estas líneas si NO quieres que se mueva el foco
                        // setTimeout(() => {
                        //     document.getElementById('btn-finalizar')?.focus();
                        // }, 100);
                    }
                }

            /**
            function manejarEnterReferencia(event) {
                
                // Si presiona Enter en el campo de referencia
                if (event.key === 'Enter') {
                    event.preventDefault();
                    
                    const referenciaInput = document.getElementById('referencia-pago');
                    if (referenciaInput.value.length === 6) {
                        // Guardar primero
                        guardarReferencia(referenciaInput.value);
                        
                        // Luego mover foco al botón
                        setTimeout(() => {
                            document.getElementById('btn-finalizar')?.focus();
                        }, 100);
                    }
                }
            }
            /**/
                function manejarEnterReferencia(event) {
                    // Si presiona Enter en el campo de referencia
                    if (event.key === 'Enter') {
                        event.preventDefault(); // Evitar submit del formulario
                        
                        const referenciaInput = document.getElementById('referencia-pago');
                        if (referenciaInput.value.length === 6) {
                            // Guardar con AJAX (sin recargar)
                            const metodoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
                            if (metodoSeleccionado) {
                                guardarPagoYReferencia(metodoSeleccionado.value, referenciaInput.value);
                            }
                            
                            // OPCIÓN 1: Mover foco al botón (descomenta si quieres)
                            // document.getElementById('btn-finalizar')?.focus();
                            
                            // OPCIÓN 2: Dejar el foco donde está (recomendado)
                            return false;
                        } else {
                            alert('❌ La referencia debe tener 6 dígitos.');
                        }
                    }
                    return true;
                }
            
            function manejarEnterFormulario(event) {
                // Si presiona Enter en el formulario y no está en un textarea
                if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                    // Si está en un input de referencia, ya lo manejamos arriba
                    if (event.target.id === 'referencia-pago') {
                        return true;
                    }
                    
                    // Si está en otro input y es Enter, prevenir submit automático
                    if (event.target.tagName === 'INPUT' && event.target.type !== 'submit') {
                        event.preventDefault();
                        
                        // Si el botón no está deshabilitado, ejecutar validación
                        const btnFinalizar = document.getElementById('btn-finalizar');
                        if (!btnFinalizar.disabled) {
                            validarVenta() && document.getElementById('form-finalizar').submit();
                        }
                    }
                }
                return true;
            }
            
            // Mejorar la función guardarReferencia para ser más confiable
            function guardarReferencia(referencia) {
                if (referencia.length === 6) {
                    // Buscar método de pago seleccionado
                    const metodoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
                    
                    // Si no hay método seleccionado, seleccionar Pago Móvil por defecto
                    if (!metodoSeleccionado) {
                        const pagoMovilRadio = document.querySelector('input[name="metodo_pago"][value="pago_movil"]');
                        if (pagoMovilRadio) {
                            pagoMovilRadio.checked = true;
                            // Actualizar visualmente
                            pagoMovilRadio.closest('label').classList.add('border-green-500', 'bg-green-50');
                            pagoMovilRadio.closest('label').classList.remove('border-gray-300');
                            
                            // Guardar ambos (método y referencia)
                            guardarPagoYReferencia('pago_movil', referencia);
                            return;
                        }
                    } else if (metodoSeleccionado) {
                        // Solo guardar referencia
                        guardarPagoYReferencia(metodoSeleccionado.value, referencia);
                    }
                }
            }
            
            // Nueva función para guardar ambos a la vez
            /**
            function guardarPagoYReferencia(metodo, referencia) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'nueva.php';
                
                const inputAction = document.createElement('input');
                inputAction.type = 'hidden';
                inputAction.name = 'action';
                inputAction.value = 'guardar_pago';
                form.appendChild(inputAction);
                
                const inputMetodo = document.createElement('input');
                inputMetodo.type = 'hidden';
                inputMetodo.name = 'metodo_pago';
                inputMetodo.value = metodo;
                form.appendChild(inputMetodo);
                
                const inputReferencia = document.createElement('input');
                inputReferencia.type = 'hidden';
                inputReferencia.name = 'referencia';
                inputReferencia.value = referencia;
                form.appendChild(inputReferencia);
                
                document.body.appendChild(form);
                form.submit();
            }
            /**/

                // Nueva función para guardar con AJAX (sin recargar página)
                function guardarPagoYReferencia(metodo, referencia) {
                    // Crear FormData
                    const formData = new FormData();
                    formData.append('action', 'guardar_pago');
                    formData.append('metodo_pago', metodo);
                    formData.append('referencia', referencia);
                    
                    // Enviar con fetch (AJAX)
                    fetch('nueva.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        console.log('Datos guardados en sesión');
                        // NO recargar la página
                    })
                    .catch(error => {
                        console.error('Error al guardar:', error);
                    });
                }
            
            // Mejorar guardarMetodoPago para también guardar referencia si existe
            function guardarMetodoPago(metodo) {
                const referenciaInput = document.getElementById('referencia-pago');
                const referencia = referenciaInput?.value || '';
                
                if (referencia.length === 6) {
                    guardarPagoYReferencia(metodo, referencia);
                } else {
                    // Solo guardar método
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'nueva.php';
                    
                    const inputAction = document.createElement('input');
                    inputAction.type = 'hidden';
                    inputAction.name = 'action';
                    inputAction.value = 'guardar_pago';
                    form.appendChild(inputAction);
                    
                    const inputMetodo = document.createElement('input');
                    inputMetodo.type = 'hidden';
                    inputMetodo.name = 'metodo_pago';
                    inputMetodo.value = metodo;
                    form.appendChild(inputMetodo);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        
        // ==============================================
        // CONFIGURACIÓN INICIAL Y ATAJOS
        // ==============================================
        
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('buscar-producto')?.focus();
            
            const infoBusqueda = document.getElementById('info-busqueda');
            if (infoBusqueda && buscarClienteInput) {
                buscarClienteInput.addEventListener('focus', () => infoBusqueda.classList.remove('hidden'));
                buscarClienteInput.addEventListener('blur', () => infoBusqueda.classList.add('hidden'));
            }
        });
        
        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('buscar-producto')?.focus();
                document.getElementById('buscar-producto')?.select();
            }
            
            if (e.ctrlKey && e.key === 'c') {
                e.preventDefault();
                document.getElementById('buscar-cliente')?.focus();
                document.getElementById('buscar-cliente')?.select();
            }
            
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                crearNuevaVenta();
            }
            
            if (e.key === 'Escape') {
                const buscarProducto = document.getElementById('buscar-producto');
                if (document.activeElement === buscarProducto && buscarProducto.value) {
                    buscarProducto.value = '';
                    buscarProducto.dispatchEvent(new Event('input'));
                }
                
                const buscarCliente = document.getElementById('buscar-cliente');
                if (document.activeElement === buscarCliente && buscarCliente.value) {
                    buscarCliente.value = '';
                    document.getElementById('resultados-cliente')?.classList.add('hidden');
                }
                
                const modal = document.getElementById('modal-cliente');
                if (!modal.classList.contains('hidden')) {
                    cerrarModalCliente();
                }
                
                const menuVentas = document.getElementById('menu-ventas');
                if (menuVentas && !menuVentas.classList.contains('hidden')) {
                    menuVentas.classList.add('hidden');
                }
            }
        });
        
        // Auto-save cada 30 segundos (simulado)
        setInterval(() => {
            console.log('Ventas pendientes guardadas en sesión');
        }, 30000);
    </script>
</body>
</html>

<?php
// Función para mostrar tiempo relativo
function tiempoRelativo($fecha) {
    $now = new DateTime();
    $fechaObj = new DateTime($fecha);
    $interval = $now->diff($fechaObj);
    
    if ($interval->y > 0) {
        return "hace {$interval->y} año" . ($interval->y > 1 ? 's' : '');
    } elseif ($interval->m > 0) {
        return "hace {$interval->m} mes" . ($interval->m > 1 ? 'es' : '');
    } elseif ($interval->d > 0) {
        return "hace {$interval->d} día" . ($interval->d > 1 ? 's' : '');
    } elseif ($interval->h > 0) {
        return "hace {$interval->h} hora" . ($interval->h > 1 ? 's' : '');
    } elseif ($interval->i > 0) {
        return "hace {$interval->i} minuto" . ($interval->i > 1 ? 's' : '');
    } else {
        return "hace unos segundos";
    }
}
?>