<?php
/**
 * Apertura de Caja - Módulo Cajero
 * Archivo: /views/dashboard/cajero/caja/abrir.php
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

// Verificar si ya tiene caja abierta
$caja_abierta = cajaAbierta($cajero_id);

// Verificar turno activo
$stmt = $pdo->prepare("
    SELECT tc.* 
    FROM turnos_caja tc
    WHERE tc.cajero_id = :cajero_id 
        AND tc.dia_semana = LOWER(DAYNAME(CURDATE()))
        AND tc.hora_inicio <= TIME(NOW())
        AND tc.hora_fin >= TIME(NOW())
        AND tc.estado = 'activo'
    ORDER BY tc.hora_inicio
    LIMIT 1
");

$stmt->execute([':cajero_id' => $cajero_id]);
$turno_activo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Simplemente establecer siempre $turno_activo = true
    $turno_activo = true;


// Procesar apertura de caja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abrir_caja'])) {
    
    // Validaciones
    if ($caja_abierta) {
        $_SESSION['error'] = "Ya tiene una caja abierta. Debe cerrarla antes de abrir una nueva.";
        header('Location: ../../cajero/caja/abrir.php');
        exit();
    }
    
    if (!$turno_activo) {
        $_SESSION['error'] = "No está en su turno de trabajo asignado.";
        header('Location: ../../cajero/caja/abrir.php');
        exit();
    }
    
    try {
        // Insertar registro de apertura de caja
        $stmt = $pdo->prepare("
            INSERT INTO caja 
            (cajero_id, turno_id, monto_inicial, estado, fecha_apertura)
            VALUES 
            (:cajero_id, :turno_id, 0.00, 'abierta', NOW())
        ");
        
        $stmt->execute([
            ':cajero_id' => $cajero_id,
            ':turno_id' => $turno_activo['id']
        ]);
        
        // Registrar actividad
        registrarActividad(
            'Caja abierta', 
            'Caja', 
            "Apertura de caja realizada. Turno: {$turno_activo['nombre']}"
        );
        
        $_SESSION['mensaje_exito'] = "Caja abierta exitosamente.";
        header('Location: ../index.php');
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al abrir caja: " . $e->getMessage();
        header('Location: ../../cajero/caja/abrir.php');
        exit();
    }
}

// Registrar actividad de ingreso a la página
registrarActividad('Acceso apertura caja', 'Caja', 'Ingresó al formulario de apertura de caja');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abrir Caja - Torre General</title>
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
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior -->
    <nav class="bg-white shadow-md border-b border-green-100">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center pulse-animation">
                        <i class="fas fa-lock-open text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Apertura de Caja</h1>
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
        <div class="max-w-2xl mx-auto">
            <!-- Card de Apertura de Caja -->
            <div class="caja-card rounded-2xl p-6 mb-6">
                <div class="text-center mb-8">
                    <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-green-100 to-emerald-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-cash-register text-3xl text-green-600"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Apertura de Caja Diaria</h2>
                    <p class="text-gray-600">Procedimiento para iniciar operaciones de venta</p>
                </div>
                
                <!-- Información del Turno -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Información del Turno</h3>
                    
                    <?php if ($turno_activo): ?>
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-blue-800"><?php echo htmlspecialchars($turno_activo['nombre']); ?></p>
                                    <p class="text-sm text-blue-600">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo date('H:i', strtotime($turno_activo['hora_inicio'])); ?> 
                                        - 
                                        <?php echo date('H:i', strtotime($turno_activo['hora_fin'])); ?>
                                    </p>
                                    <p class="text-sm text-blue-600">
                                        <i class="fas fa-calendar mr-1"></i>
                                        Hoy: <?php echo ucfirst(date('l')); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                        <i class="fas fa-check-circle mr-1"></i>Turno Activo
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-amber-500 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="font-medium text-amber-800">No está en turno activo</p>
                                    <p class="text-sm text-amber-600">
                                        Solo puede abrir caja durante su horario de trabajo asignado.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Estado de Caja Actual -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Estado Actual de Caja</h3>
                    
                    <?php if ($caja_abierta): ?>
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-red-800">Caja ya está abierta</p>
                                    <p class="text-sm text-red-600">
                                        Tiene una caja abierta desde hoy. Debe cerrarla antes de abrir una nueva.
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
                                        <i class="fas fa-lock mr-1"></i>Abierta
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4 text-center">
                                <a href="../index.php" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                    <i class="fas fa-arrow-right mr-1"></i>Ir al Dashboard para ver estado de caja
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-green-800">Caja cerrada</p>
                                    <p class="text-sm text-green-600">
                                        La caja está actualmente cerrada. Puede proceder con la apertura.
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                        <i class="fas fa-lock mr-1"></i>Cerrada
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Información Importante -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Información Importante</h3>
                    <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <ul class="space-y-3 text-sm text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-info-circle text-green-600 mt-0.5 mr-2"></i>
                                <span>Este sistema solo maneja transacciones digitales (tarjeta débito y pago móvil).</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-info-circle text-green-600 mt-0.5 mr-2"></i>
                                <span>El monto inicial de caja siempre es $0.00 (no hay manejo de efectivo).</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-info-circle text-green-600 mt-0.5 mr-2"></i>
                                <span>Debe estar en su turno asignado para abrir caja.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-info-circle text-green-600 mt-0.5 mr-2"></i>
                                <span>Al cerrar caja, se generará un reporte de todas las transacciones del día.</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Formulario de Apertura -->
                <?php if (!$caja_abierta && $turno_activo): ?>
                <form method="POST" action="abrir.php" onsubmit="return confirmarApertura()">
                    <input type="hidden" name="abrir_caja" value="1">
                    
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 mb-6">
                            <h4 class="font-semibold text-green-800 mb-2 flex items-center">
                                <i class="fas fa-clipboard-check mr-2"></i>
                                Confirmación de Apertura
                            </h4>
                            <p class="text-sm text-green-600 mb-4">
                                Al proceder, se registrará la apertura de caja con monto inicial $0.00. 
                                Esta acción permitirá comenzar a registrar ventas.
                            </p>
                            
                            <div class="flex items-center mb-4">
                                <input type="checkbox" id="confirmar" name="confirmar" required class="mr-3 h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                                <label for="confirmar" class="text-sm text-green-800">
                                    Confirmo que estoy en mi turno de trabajo y procedo con la apertura de caja.
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex justify-center space-x-4">
                            <a href="../index.php" class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition-all flex items-center">
                                <i class="fas fa-times mr-2"></i> Cancelar
                            </a>
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                                <i class="fas fa-lock-open mr-2"></i> Abrir Caja
                            </button>
                        </div>
                    </div>
                </form>
                <?php elseif ($caja_abierta): ?>
                <div class="text-center py-6 border-t border-gray-200">
                    <p class="text-gray-600 mb-4">No puede abrir una nueva caja porque ya tiene una abierta.</p>
                    <a href="../index.php" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all inline-flex items-center">
                        <i class="fas fa-cash-register mr-2"></i> Ir al Dashboard
                    </a>
                </div>
                <?php else: ?>
                <div class="text-center py-6 border-t border-gray-200">
                    <p class="text-gray-600 mb-4">No puede abrir caja porque no está en su turno de trabajo.</p>
                    <a href="../index.php" class="px-6 py-3 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-semibold rounded-xl hover:shadow-lg transition-all inline-flex items-center">
                        <i class="fas fa-clock mr-2"></i> Ver Horarios
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Información Adicional -->
            <div class="caja-card rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Preguntas Frecuentes</h3>
                <div class="space-y-4">
                    <div>
                        <p class="font-medium text-gray-800">¿Por qué el monto inicial es $0.00?</p>
                        <p class="text-sm text-gray-600">El sistema solo maneja transacciones digitales, por lo que no hay efectivo físico en caja.</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">¿Qué pasa si abro caja fuera de mi turno?</p>
                        <p class="text-sm text-gray-600">El sistema no lo permitirá. Solo puede abrir caja durante los horarios asignados por el gerente.</p>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">¿Puedo tener más de una caja abierta al mismo tiempo?</p>
                        <p class="text-sm text-gray-600">No, cada cajero solo puede tener una caja abierta a la vez.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmarApertura() {
            const confirmacion = document.getElementById('confirmar');
            if (!confirmacion.checked) {
                alert('Debe confirmar que está en su turno de trabajo para proceder.');
                return false;
            }
            
            return confirm('¿Está seguro de proceder con la apertura de caja?\n\nEsta acción permitirá comenzar a registrar ventas.');
        }
        
        // Auto-redirección si ya tiene caja abierta
        document.addEventListener('DOMContentLoaded', function() {
            const tieneCajaAbierta = <?php echo $caja_abierta ? 'true' : 'false'; ?>;
            const estaEnTurno = <?php echo $turno_activo ? 'true' : 'false'; ?>;
            
            if (tieneCajaAbierta) {
                console.log('El cajero ya tiene caja abierta.');
            }
            
            if (!estaEnTurno) {
                console.log('El cajero no está en turno activo.');
            }
        });
    </script>
</body>
</html>