<?php
/**
 * Dashboard para Visitante (Confirmado y No Confirmado)
 */

require_once '../../../includes/seguridad.php';
require_once '../../../config/db.php';

requerirAutenticacion();

$usuario = obtenerUsuarioActual();

// Verificar que sea algún tipo de visitante
$es_visitante = in_array($usuario['rol'], [
    'Visitante-No-Confirmado', 
    'Visitante-Confirmado-Admin', 
    'Visitante-Confirmado-Propietario'
]);

if (!$es_visitante) {
    header('Location: ../../../index.php?error=no_autorizado');
    exit();
}

// Obtener color del rol (usamos el color de Visitante-No-Confirmado por defecto)
// $pdo = new PDO("mysql:host=localhost;dbname=proyecto_sistema;charset=utf8mb4", "usuario", "password");
$stmt = $pdo->prepare("SELECT color_pastel FROM roles WHERE nombre_rol = ?");
$stmt->execute([$usuario['rol']]);
$rol_color = $stmt->fetchColumn();

// Determinar estado de confirmación
$pendiente = ($usuario['rol'] === 'Visitante-No-Confirmado');
$confirmado = !$pendiente;

// Obtener datos del usuario desde la sesión o BD
$datos_visitante = [
    'nombre' => $usuario['nombre'],
    'email' => $usuario['email'],
    'telefono' => '+584141234573', // Simulado
    'empresa' => 'Empresa Visitante', // Simulado
    'fecha_registro' => date('d/m/Y', strtotime('-5 days')),
    'fecha_visita' => date('d/m/Y'),
    'motivo' => 'Reunión de negocios'
];

// Servicios disponibles para visitantes
$servicios_edificio = [
    ['nombre' => 'Cafetería', 'horario' => '7:00 AM - 7:00 PM', 'ubicacion' => 'Piso 1', 'estado' => 'Abierto'],
    ['nombre' => 'Sala de Conferencias', 'horario' => '8:00 AM - 6:00 PM', 'ubicacion' => 'Piso 2', 'estado' => 'Disponible'],
    ['nombre' => 'Estacionamiento', 'horario' => '24 horas', 'ubicacion' => 'Sótano', 'estado' => 'Espacios limitados'],
    ['nombre' => 'Wifi Público', 'horario' => '24 horas', 'ubicacion' => 'Todo el edificio', 'estado' => 'Activo'],
    ['nombre' => 'Área de Espera', 'horario' => '8:00 AM - 6:00 PM', 'ubicacion' => 'Lobby Principal', 'estado' => 'Disponible']
];

$anuncios_publicos = [
    ['titulo' => 'Horario Especial Festivo', 'contenido' => 'El 1 de noviembre el edificio cerrará a las 2:00 PM.', 'fecha' => '28/10/2024'],
    ['titulo' => 'Mantenimiento Ascensores', 'contenido' => 'Ascensores norte en mantenimiento el 30/10 (8AM-12PM).', 'fecha' => '25/10/2024'],
    ['titulo' => 'Nuevo Protocolo de Seguridad', 'contenido' => 'Recordatorio: uso obligatorio de credenciales dentro del edificio.', 'fecha' => '20/10/2024']
];

// Si está pendiente, mostrar mensaje especial
if ($pendiente) {
    $mensaje_pendiente = "Su cuenta está pendiente de confirmación. Un administrador o el inquilino que lo invitó debe confirmar su acceso para habilitar todas las funcionalidades.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Visitante - Torre General</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-rol: <?php echo $rol_color ?: '#E0F2FE'; ?>;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
        }
        .visitante-card {
            background: white;
            border: 1px solid rgba(224, 242, 254, 0.3);
            box-shadow: 0 4px 6px rgba(224, 242, 254, 0.1);
        }
        .info-card {
            background: linear-gradient(135deg, var(--color-rol) 0%, #bae6fd 100%);
        }
        .menu-item {
            background: rgba(224, 242, 254, 0.1);
            border: 1px solid rgba(224, 242, 254, 0.3);
        }
        .menu-item:hover {
            background: rgba(224, 242, 254, 0.2);
            transform: translateX(5px);
        }
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .status-confirmed {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
    </style>
</head>
<body class="text-gray-800">
    <!-- Navbar Superior -->
    <nav class="bg-white shadow-md border-b border-blue-100">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-friends text-white"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-xl text-gray-800">Portal del Visitante</h1>
                        <p class="text-sm text-gray-600">Torre General - Acceso para Invitados</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-6">
                    <!-- Indicador de Estado -->
                    <div class="flex items-center space-x-2 px-4 py-2 rounded-lg <?php echo $pendiente ? 'bg-amber-50 text-amber-800' : 'bg-green-50 text-green-800'; ?>">
                        <div class="w-2 h-2 rounded-full <?php echo $pendiente ? 'bg-amber-500' : 'bg-green-500'; ?>"></div>
                        <span class="font-medium">
                            <?php echo $pendiente ? 'Pendiente de Confirmación' : 'Visitante Confirmado'; ?>
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="font-semibold"><?php echo htmlspecialchars($usuario['nombre']); ?></p>
                            <p class="text-sm text-gray-600">Visitante</p>
                        </div>
                        <div class="relative">
                            <button id="user-menu" class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 text-white flex items-center justify-center">
                                <i class="fas fa-user"></i>
                            </button>
                            <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 z-50 border border-gray-200">
                                <a href="perfil.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Mi Perfil
                                </a>
                                <?php if ($confirmado): ?>
                                <a href="../servicios/solicitar.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">
                                    <i class="fas fa-concierge-bell mr-2"></i>Servicios
                                </a>
                                <?php endif; ?>
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
        <!-- Encabezado con Estado -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <?php echo $pendiente ? 'Acceso Temporal' : 'Bienvenido a Torre General'; ?>
                    </h1>
                    <p class="text-gray-600">
                        <?php echo $pendiente 
                            ? 'Su acceso está limitado hasta confirmación' 
                            : 'Información y servicios disponibles durante su visita'; 
                        ?>
                    </p>
                </div>
                
                <?php if ($pendiente): ?>
                <div class="px-6 py-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <div class="flex items-start">
                        <i class="fas fa-clock text-amber-500 mt-1 mr-3"></i>
                        <div>
                            <p class="font-medium text-amber-800">Esperando confirmación</p>
                            <p class="text-sm text-amber-700">Un administrador o el inquilino que lo invitó confirmará su acceso pronto.</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="flex flex-wrap gap-3">
                    <a href="../servicios/solicitar.php" class="px-6 py-3 bg-gradient-to-r from-cyan-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-concierge-bell mr-2"></i>Solicitar Servicio
                    </a>
                    <a href="../mapa.php" class="px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all flex items-center">
                        <i class="fas fa-map-marked-alt mr-2"></i>Ver Mapa
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Información del Visitante -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="info-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-user mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Visitante</p>
                            <p class="text-lg font-bold truncate"><?php echo $datos_visitante['nombre']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="info-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-building mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Empresa</p>
                            <p class="text-lg font-bold"><?php echo $datos_visitante['empresa']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="info-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-calendar-day mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Fecha de Visita</p>
                            <p class="text-lg font-bold"><?php echo $datos_visitante['fecha_visita']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="info-card text-white rounded-xl p-6">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle mr-3 text-2xl opacity-70"></i>
                        <div>
                            <p class="text-sm opacity-90 mb-1">Motivo</p>
                            <p class="text-lg font-bold truncate"><?php echo $datos_visitante['motivo']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($pendiente): ?>
        <!-- Vista para Visitante No Confirmado -->
        <div class="visitante-card rounded-2xl p-8 mb-8">
            <div class="text-center max-w-2xl mx-auto">
                <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-user-clock text-amber-500 text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Acceso Limitado Temporalmente</h2>
                <p class="text-gray-600 mb-6">
                    Su registro como visitante ha sido recibido, pero requiere confirmación para acceder a todos los servicios del edificio.
                    Un administrador o el inquilino que lo invitó revisará su solicitud y le notificará cuando su acceso sea confirmado.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8">
                    <div class="p-4 bg-gray-50 rounded-xl text-center">
                        <i class="fas fa-envelope text-blue-500 text-2xl mb-3"></i>
                        <p class="font-medium">Notificación por Email</p>
                        <p class="text-sm text-gray-600 mt-2">Recibirá un correo cuando sea confirmado</p>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl text-center">
                        <i class="fas fa-clock text-amber-500 text-2xl mb-3"></i>
                        <p class="font-medium">Tiempo de Espera</p>
                        <p class="text-sm text-gray-600 mt-2">Usualmente 24-48 horas hábiles</p>
                    </div>
                    <div class="p-4 bg-gray-50 rounded-xl text-center">
                        <i class="fas fa-question-circle text-green-500 text-2xl mb-3"></i>
                        <p class="font-medium">¿Preguntas?</p>
                        <p class="text-sm text-gray-600 mt-2">Contacte al inquilino que lo invitó</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Columna Izquierda: Servicios y Mapa -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Servicios del Edificio -->
                <div class="visitante-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">🏢 Servicios del Edificio</h2>
                        <a href="../mapa.php" class="text-cyan-600 hover:text-cyan-800 font-medium text-sm">
                            Ver mapa completo →
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($servicios_edificio as $servicio): ?>
                        <div class="p-4 rounded-xl border border-gray-200 hover:border-cyan-300 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="font-bold text-gray-800"><?php echo $servicio['nombre']; ?></p>
                                    <p class="text-sm text-gray-600"><?php echo $servicio['ubicacion']; ?></p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-medium 
                                    <?php echo $servicio['estado'] === 'Abierto' || $servicio['estado'] === 'Activo' || $servicio['estado'] === 'Disponible' 
                                        ? 'bg-green-100 text-green-800' 
                                        : 'bg-amber-100 text-amber-800'; ?>">
                                    <?php echo $servicio['estado']; ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-700">
                                <i class="far fa-clock mr-2"></i><?php echo $servicio['horario']; ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Anuncios Públicos -->
                <div class="visitante-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">📢 Anuncios Públicos</h2>
                        <a href="../anuncios.php" class="text-cyan-600 hover:text-cyan-800 font-medium text-sm">
                            Ver todos →
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php foreach ($anuncios_publicos as $anuncio): ?>
                        <div class="p-4 rounded-lg bg-blue-50 border border-blue-200 hover:bg-blue-100 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <p class="font-medium text-blue-800"><?php echo $anuncio['titulo']; ?></p>
                                <span class="text-xs text-blue-600"><?php echo $anuncio['fecha']; ?></span>
                            </div>
                            <p class="text-sm text-blue-700"><?php echo $anuncio['contenido']; ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Información y Acciones -->
            <div class="space-y-6">
                <!-- Información de Acceso -->
                <div class="visitante-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">🔐 Información de Acceso</h2>
                    
                    <div class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Estado de Confirmación</p>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full <?php echo $pendiente ? 'bg-amber-500' : 'bg-green-500'; ?> mr-3"></div>
                                <p class="font-bold <?php echo $pendiente ? 'text-amber-700' : 'text-green-700'; ?>">
                                    <?php echo $pendiente ? 'PENDIENTE' : 'CONFIRMADO'; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Fecha de Registro</p>
                            <p class="font-bold text-gray-800"><?php echo $datos_visitante['fecha_registro']; ?></p>
                        </div>
                        
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-1">Email Registrado</p>
                            <p class="font-bold text-gray-800 truncate"><?php echo $datos_visitante['email']; ?></p>
                        </div>
                        
                        <?php if ($confirmado): ?>
                        <div class="p-4 bg-green-50 rounded-xl border border-green-200">
                            <p class="text-sm text-green-600 mb-1">Accesos Habilitados</p>
                            <p class="font-bold text-green-700">Servicios, mapa, anuncios</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones Disponibles -->
                <div class="visitante-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">⚡ Acciones Disponibles</h2>
                    
                    <div class="space-y-3">
                        <?php if ($confirmado): ?>
                        <a href="../servicios/solicitar.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-concierge-bell text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Solicitar Servicio</p>
                                    <p class="text-sm text-gray-600">Asistencia durante su visita</p>
                                </div>
                            </div>
                        </a>
                        
                        <a href="../mapa.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-map-marked-alt text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Mapa del Edificio</p>
                                    <p class="text-sm text-gray-600">Ubicaciones y servicios</p>
                                </div>
                            </div>
                        </a>
                        <?php endif; ?>
                        
                        <a href="../anuncios.php" class="menu-item block p-4 rounded-xl transition-all">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-bullhorn text-white"></i>
                                </div>
                                <div>
                                    <p class="font-semibold">Anuncios</p>
                                    <p class="text-sm text-gray-600">Noticias del edificio</p>
                                </div>
                            </div>
                        </a>
                        
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-600 mb-2">¿Necesita ayuda?</p>
                            <div class="flex space-x-3">
                                <a href="tel:+584141234567" class="flex-1 text-center py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition-colors text-sm">
                                    <i class="fas fa-phone mr-1"></i>Llamar
                                </a>
                                <a href="mailto:info@torregeneral.com" class="flex-1 text-center py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm">
                                    <i class="fas fa-envelope mr-1"></i>Email
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reglas del Edificio -->
                <div class="visitante-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">📜 Reglas del Edificio</h2>
                    
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <i class="fas fa-id-card text-cyan-600 mt-1 mr-3"></i>
                            <p class="text-sm text-gray-700">Use su credencial visiblemente en todo momento</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-ban text-red-500 mt-1 mr-3"></i>
                            <p class="text-sm text-gray-700">No ingrese a áreas restringidas sin autorización</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-clock text-amber-500 mt-1 mr-3"></i>
                            <p class="text-sm text-gray-700">Respete los horarios de las áreas comunes</p>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-triangle text-orange-500 mt-1 mr-3"></i>
                            <p class="text-sm text-gray-700">En caso de emergencia, siga las señales de evacuación</p>
                        </div>
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

        // Mostrar alerta para visitantes pendientes
        <?php if ($pendiente): ?>
        setTimeout(function() {
            const alertBox = document.createElement('div');
            alertBox.className = 'fixed bottom-4 right-4 bg-amber-100 border border-amber-300 rounded-xl p-4 max-w-sm shadow-lg z-50';
            alertBox.innerHTML = `
                <div class="flex">
                    <i class="fas fa-info-circle text-amber-500 mr-3 mt-1"></i>
                    <div>
                        <p class="font-medium text-amber-800">Estado pendiente</p>
                        <p class="text-sm text-amber-700">Su acceso será ampliado después de la confirmación.</p>
                    </div>
                    <button class="text-amber-500 hover:text-amber-700 ml-4" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            document.body.appendChild(alertBox);
            
            // Auto-remover después de 10 segundos
            setTimeout(() => {
                if (alertBox.parentElement) {
                    alertBox.remove();
                }
            }, 10000);
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>