<?php
/**
 * Dashboard para Inquilino
 */

require_once '../../../includes/seguridad.php';
require_once '../../../config/db.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea inquilino
if ($usuario['rol'] !== 'Inquilino') {
    header('Location: ../../../index.php?error=no_autorizado');
    exit();
}

// Obtener color del rol
// $pdo = new PDO("mysql:host=localhost;dbname=proyecto_sistema;charset=utf8mb4", "usuario", "password");
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = 'Inquilino'");
$stmt->execute();
$rol_color = $stmt->fetchColumn();

// Datos simulados para el inquilino
$datos_inquilino = [
    'empresa' => 'Empresa Tech Solutions C.A.',
    'piso' => 'Piso 5',
    'oficinas' => '501-510',
    'area' => '450 m²',
    'fecha_ingreso' => '15/03/2020',
    'contacto' => 'Juan Martínez - Gerente General',
    'telefono' => '+584141234571',
    'email' => 'contacto@techsolutions.com'
];

$contrato_actual = [
    'numero' => 'CT-2024-005',
    'tipo' => 'Arrendamiento Comercial',
    'vigencia' => '01/01/2024 - 31/12/2024',
    'monto_mensual' => 3250.00,
    'proximo_pago' => '01/11/2024',
    'estado' => 'Vigente'
];

$pagos_recientes = [
    ['mes' => 'Octubre 2024', 'fecha' => '01/10/2024', 'monto' => 3250.00, 'metodo' => 'Transferencia', 'estado' => 'Pagado'],
    ['mes' => 'Septiembre 2024', 'fecha' => '01/09/2024', 'monto' => 3250.00, 'metodo' => 'Transferencia', 'estado' => 'Pagado'],
    ['mes' => 'Agosto 2024', 'fecha' => '01/08/2024', 'monto' => 3250.00, 'metodo' => 'Depósito', 'estado' => 'Pagado'],
    ['mes' => 'Julio 2024', 'fecha' => '01/07/2024', 'monto' => 3250.00, 'metodo' => 'Transferencia', 'estado' => 'Pagado'],
    ['mes' => 'Junio 2024', 'fecha' => '01/06/2024', 'monto' => 3250.00, 'metodo' => 'Efectivo', 'estado' => 'Pagado']
];

$servicios_pendientes = [
    ['id' => 'SRV-045', 'tipo' => 'Mantenimiento A/C', 'descripcion' => 'Aire acondicionado oficina 504 no enfría', 'fecha' => '25/10/2024', 'estado' => 'En proceso'],
    ['id' => 'SRV-042', 'tipo' => 'Reparación eléctrica', 'descripcion' => 'Toma corriente dañada en sala de reuniones', 'fecha' => '18/10/2024', 'estado' => 'Completado'],
    ['id' => 'SRV-038', 'tipo' => 'Limpieza especial', 'descripcion' => 'Limpieza alfombra después de evento', 'fecha' => '10/10/2024', 'estado' => 'Completado']
];

$anuncios_edificio = [
    ['titulo' => 'Mantenimiento Ascensores', 'contenido' => 'Los ascensores norte estarán en mantenimiento el 30/10 de 8:00 a 12:00.', 'fecha' => '25/10/2024'],
    ['titulo' => 'Reunión Consejo de Inquilinos', 'contenido' => 'Próxima reunión: 5/11/2024 a las 10:00 en sala de conferencias.', 'fecha' => '20/10/2024'],
    ['titulo' => 'Horarios Festivos', 'contenido' => 'Recordatorio: el edificio cerrará a las 14:00 el 1/11 por festivo.', 'fecha' => '18/10/2024']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Inquilino - Torre General</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-rol: <?php echo $rol_color ?: '#FCE7F3'; ?>;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
            min-height: 100vh;
        }
        .inquilino-card {
            background: white;
            border: 1px solid rgba(252, 231, 243, 0.3);
            box-shadow: 0 4px 6px rgba(252, 231, 243, 0.1);
        }
        .info-card {
            background: linear-gradient(135deg, var(--color-rol) 0%, #fbcfe8 100%);
        }
        .menu-item {
            background: rgba(252, 231, 243, 0.1);
            border: 1px solid rgba(252, 231, 243, 0.3);
        }
        .menu-item:hover {
            background: rgba(252, 231, 243, 0.2);
            transform: translateX(5px);
        }
        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .status-process {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .status-completed {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior -->
    <nav class="bg-white shadow-md border-b border-pink-100">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-rose-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-building text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Portal del Inquilino</h1>
                        <p class="text-sm text-gray-600">Torre General - Gestión de Espacios</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <!-- Indicador de Contrato -->
                    <div class="flex items-center space-x-2 px-4 py-2 rounded-lg bg-green-50 text-green-800">
                        <i class="fas fa-file-contract"></i>
                        <span class="font-medium">Contrato Vigente</span>
                        <span class="text-sm">• <?php echo $contrato_actual['numero']; ?></span>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="font-semibold"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                            <p class="text-sm text-gray-600">Inquilino</p>
                        </div>
                        <div class="relative">
                            <button id="user-menu" class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-500 to-rose-600 text-white flex items-center justify-center">
                                <i class="fas fa-user"></i>
                            </button>
                            <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 z-50 border border-gray-200">
                                <a href="perfil.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Mi Perfil
                                </a>
                                <a href="../contratos.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-file-contract mr-2"></i>Mis Contratos
                                </a>
                                <a href="../configuracion.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2"></i>Configuración
                                </a>
                                <div class="border-t my-2"></div>
                                <a href="../../../controllers/AuthController.php?action=logout" 
                                   class="block px-4 py-2 text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-6 py-8">
        <!-- Encabezado con Información -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido, <?php echo explode(' ', $usuario['nombre'])[0]; ?></h1>
                    <p class="text-gray-600">Gestión de su espacio en Torre General</p>
                </div>
                
                <div class="flex flex-wrap gap-3">
                    <a href="../pagos/realizar.php" class="px-6 py-3 bg-gradient-to-r from-pink-600 to-rose-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-credit-card mr-2"></i>Realizar Pago
                    </a>
                    <a href="../servicios/solicitar.php" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-tools mr-2"></i>Solicitar Servicio
                    </a>
                    <a href="../comunicaciones/nueva.php" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-envelope mr-2"></i>Contactar Admin
                    </a>
                </div>
            </div>

            <!-- Información de la Empresa -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="info-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-building mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Empresa</p>
                            <p class="text-lg font-bold truncate"><?php echo $datos_inquilino['empresa']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="info-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-map-marker-alt mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Ubicación</p>
                            <p class="text-lg font-bold"><?php echo $datos_inquilino['piso']; ?> • <?php echo $datos_inquilino['oficinas']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="info-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-expand-arrows-alt mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Área</p>
                            <p class="text-lg font-bold"><?php echo $datos_inquilino['area']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="info-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-calendar-alt mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Desde</p>
                            <p class="text-lg font-bold"><?php echo $datos_inquilino['fecha_ingreso']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Columna Izquierda: Contrato y Pagos -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Contrato Actual -->
                <div class="inquilino-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">📄 Contrato Actual</h2>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <?php echo $contrato_actual['estado']; ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Número de Contrato</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo $contrato_actual['numero']; ?></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Tipo</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo $contrato_actual['tipo']; ?></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Vigencia</p>
                            <p class="text-lg font-bold text-gray-800"><?php echo $contrato_actual['vigencia']; ?></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Monto Mensual</p>
                            <p class="text-lg font-bold text-pink-600">$<?php echo number_format($contrato_actual['monto_mensual'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">Próximo pago</p>
                                <p class="text-lg font-bold text-gray-800"><?php echo $contrato_actual['proximo_pago']; ?></p>
                            </div>
                            <a href="../contratos/detalle.php" class="text-pink-600 hover:text-pink-800 font-medium">
                                Ver detalles completos →
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Historial de Pagos -->
                <div class="inquilino-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">💰 Historial de Pagos Recientes</h2>
                        <a href="../pagos/historial.php" class="text-pink-600 hover:text-pink-800 font-medium text-sm">
                            Ver historial completo →
                        </a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 text-gray-600">Mes</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Fecha Pago</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Monto</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Método</th>
                                    <th class="text-left py-3 px-4 text-gray-600">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos_recientes as $pago): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4 font-medium"><?php echo $pago['mes']; ?></td>
                                    <td class="py-3 px-4"><?php echo $pago['fecha']; ?></td>
                                    <td class="py-3 px-4 font-bold text-green-600">$<?php echo number_format($pago['monto'], 2); ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo $pago['metodo']; ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                                            <?php echo $pago['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">Próximo pago programado</p>
                                <p class="text-lg font-bold text-gray-800">01/11/2024</p>
                            </div>
                            <a href="../pagos/programar.php" class="px-4 py-2 bg-gradient-to-r from-pink-600 to-rose-700 text-white rounded-lg hover:shadow-lg transition-all">
                                Programar Pago
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Servicios y Comunicaciones -->
            <div class="space-y-6">
                <!-- Servicios Pendientes -->
                <div class="inquilino-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">🔧 Servicios Solicitados</h2>
                        <a href="../servicios/historial.php" class="text-pink-600 hover:text-pink-800 font-medium text-sm">
                            Ver todos →
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($servicios_pendientes as $servicio): ?>
                        <div class="p-4 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium"><?php echo $servicio['tipo']; ?></p>
                                    <p class="text-sm text-gray-600"><?php echo $servicio['id']; ?></p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-medium 
                                    <?php echo $servicio['estado'] === 'En proceso' ? 'status-process' : 
                                           ($servicio['estado'] === 'Completado' ? 'status-completed' : 
                                           'status-active'); ?>">
                                    <?php echo $servicio['estado']; ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 mb-3"><?php echo $servicio['descripcion']; ?></p>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">
                                    <i class="far fa-calendar mr-1"></i><?php echo $servicio['fecha']; ?>
                                </span>
                                <?php if ($servicio['estado'] === 'En proceso'): ?>
                                <button class="text-xs text-pink-600 hover:text-pink-800">
                                    <i class="fas fa-comment-medical mr-1"></i>Actualizar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a href="../servicios/solicitar.php" class="block w-full py-3 text-center bg-gradient-to-r from-purple-600 to-indigo-700 text-white rounded-xl hover:shadow-lg transition-all font-medium">
                            <i class="fas fa-plus mr-2"></i>Solicitar Nuevo Servicio
                        </a>
                    </div>
                </div>

                <!-- Anuncios del Edificio -->
                <div class="inquilino-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">📢 Anuncios del Edificio</h2>
                    
                    <div class="space-y-4">
                        <?php foreach ($anuncios_edificio as $anuncio): ?>
                        <div class="p-4 rounded-lg bg-blue-50 border border-blue-200">
                            <div class="flex justify-between items-start mb-2">
                                <p class="font-medium text-blue-800"><?php echo $anuncio['titulo']; ?></p>
                                <span class="text-xs text-blue-600"><?php echo $anuncio['fecha']; ?></span>
                            </div>
                            <p class="text-sm text-blue-700"><?php echo $anuncio['contenido']; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 text-center">
                        <a href="../comunicaciones/anuncios.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Ver todos los anuncios →
                        </a>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="inquilino-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">⚡ Acciones Rápidas</h2>
                    <div class="space-y-3">
                        <a href="../pagos/realizar.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-credit-card text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Realizar Pago</p>
                                    <p class="text-sm text-gray-600">Pagar alquiler mensual</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../servicios/solicitar.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-tools text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Solicitar Servicio</p>
                                    <p class="text-sm text-gray-600">Mantenimiento o reparación</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../comunicaciones/nueva.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-envelope text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Contactar Administración</p>
                                    <p class="text-sm text-gray-600">Enviar mensaje o consulta</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../visitantes/registrar.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-user-friends text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Registrar Visitante</p>
                                    <p class="text-sm text-gray-600">Acceso para invitados</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dropdown menu
        document.getElementById('user-menu').addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('dropdown-menu');
            menu.classList.toggle('hidden');
        });

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('dropdown-menu');
            const button = document.getElementById('user-menu');
            
            if (!button.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        // Actualización de servicio
        document.querySelectorAll('button:contains("Actualizar")').forEach(button => {
            button.addEventListener('click', function() {
                const servicio = this.closest('.bg-gray-50');
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                modal.innerHTML = `
                    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
                        <h3 class="text-lg font-bold mb-4">Actualizar Solicitud</h3>
                        <textarea class="w-full border rounded-lg p-3 mb-4" placeholder="Agregar información adicional..."></textarea>
                        <div class="flex justify-end space-x-3">
                            <button class="px-4 py-2 text-gray-600 hover:text-gray-800" onclick="this.closest('.fixed').remove()">Cancelar</button>
                            <button class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700" onclick="this.closest('.fixed').remove(); alert('Actualización enviada')">Enviar</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            });
        });
    </script>
</body>
</html>