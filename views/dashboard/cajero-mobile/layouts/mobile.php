<?php
/**
 * Layout Base para Versión Móvil
 * Archivo: /views/dashboard/cajero-mobile/layouts/mobile.php
 */

// Verificar si ya está definido para evitar inclusión múltiple
if (!defined('MOBILE_LAYOUT_INCLUDED')) {
    define('MOBILE_LAYOUT_INCLUDED', true);

    // Determinar si es tablet
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $is_tablet = (strpos($user_agent, 'Tablet') !== false || 
                  strpos($user_agent, 'iPad') !== false || 
                  (strpos($user_agent, 'Android') !== false && strpos($user_agent, 'Mobile') === false));
    
    // Clase CSS basada en dispositivo
    $device_class = $is_tablet ? 'device-tablet' : 'device-mobile';
    
    // Obtener color del rol para personalización
    $rol_color = isset($rol_color) ? $rol_color : '#D1FAE5';
?>
<!DOCTYPE html>
<html lang="es" class="<?php echo $device_class; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Cafetería Móvil</title>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos Base -->
    <link rel="stylesheet" href="../../../assets/css/mobile/base.css">
    <link rel="stylesheet" href="../../../assets/css/style.css">
    
    <!-- Estilos específicos de dispositivo -->
    <?php if ($is_tablet): ?>
        <link rel="stylesheet" href="../../../assets/css/tablet/base.css">
    <?php endif; ?>
    
    <!-- Estilos de componentes -->
    <link rel="stylesheet" href="../cajero-mobile/css/mobile.css">
    
    <style>
        :root {
            --color-rol: <?php echo $rol_color; ?>;
            --color-primary: #059669;
            --color-primary-dark: #047857;
            --color-secondary: #6B7280;
            --color-success: #10B981;
            --color-warning: #F59E0B;
            --color-danger: #EF4444;
            --color-info: #3B82F6;
            --color-light: #F9FAFB;
            --color-dark: #1F2937;
        }
        
        <?php if ($is_tablet): ?>
        :root {
            --border-radius: 12px;
            --spacing-unit: 1.5rem;
            --header-height: 70px;
            --bottom-nav-height: 80px;
        }
        <?php else: ?>
        :root {
            --border-radius: 10px;
            --spacing-unit: 1rem;
            --header-height: 60px;
            --bottom-nav-height: 70px;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <!-- Header Móvil -->
    <header class="mobile-header">
        <div class="header-left">
            <button class="btn-menu" id="btnMenu" aria-label="Abrir menú">
                <i class="fas fa-bars"></i>
            </button>
            <div class="brand">
                <div class="brand-logo">
                    <i class="fas fa-coffee"></i>
                </div>
                <div class="brand-text">
                    <h1 class="brand-title">Cafetería</h1>
                    <span class="brand-subtitle">Punto de Venta</span>
                </div>
            </div>
        </div>
        
        <div class="header-right">
            <button class="btn-notification" id="btnNotification" aria-label="Notificaciones">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationCount">0</span>
            </button>
            
            <div class="user-avatar" id="btnUserProfile">
                <div class="avatar-initials">
                    <?php 
                    // Mostrar iniciales del usuario
                    $initials = '';
                    if (isset($usuario) && isset($usuario['nombre'])) {
                        $names = explode(' ', $usuario['nombre']);
                        $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                    }
                    echo $initials ?: 'U';
                    ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="mobile-main" id="mainContent">
        <?php echo $content ?? ''; ?>
    </main>

    <!-- Navegación Inferior (solo en páginas principales) -->
    <?php if (!isset($hide_bottom_nav) || !$hide_bottom_nav): ?>
    <nav class="bottom-nav">
        <a href="../index.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" data-page="dashboard">
            <i class="fas fa-home"></i>
            <span>Inicio</span>
        </a>
        <a href="../ventas/nueva.php" class="nav-item" data-page="ventas">
            <i class="fas fa-cash-register"></i>
            <span>Venta</span>
        </a>
        <a href="../caja/estado.php" class="nav-item" data-page="caja">
            <i class="fas fa-calculator"></i>
            <span>Caja</span>
        </a>
        <a href="../ventas/historial.php" class="nav-item" data-page="historial">
            <i class="fas fa-history"></i>
            <span>Historial</span>
        </a>
        <a href="../inventario/consulta.php" class="nav-item" data-page="inventario">
            <i class="fas fa-boxes"></i>
            <span>Stock</span>
        </a>
    </nav>
    <?php endif; ?>

    <!-- Menú Lateral (Overlay) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="sidebarMenu">
        <div class="sidebar-header">
            <div class="sidebar-user">
                <div class="user-avatar large">
                    <div class="avatar-initials">
                        <?php echo $initials ?: 'U'; ?>
                    </div>
                </div>
                <div class="user-info">
                    <h3 class="user-name"><?php echo $usuario['nombre'] ?? 'Usuario'; ?></h3>
                    <p class="user-role">Cajero • ID: <?php echo $cajero_id ?? '--'; ?></p>
                    <p class="user-status">
                        <span class="status-dot <?php echo ($estado_caja['abierta'] ?? false) ? 'online' : 'offline'; ?>"></span>
                        <?php echo ($estado_caja['abierta'] ?? false) ? 'Caja Abierta' : 'Caja Cerrada'; ?>
                    </p>
                </div>
            </div>
            <button class="btn-close-sidebar" id="btnCloseSidebar" aria-label="Cerrar menú">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="sidebar-content">
            <div class="sidebar-section">
                <h4 class="section-title">Operaciones</h4>
                <a href="../ventas/nueva.php" class="sidebar-link">
                    <i class="fas fa-plus-circle"></i>
                    <span>Nueva Venta</span>
                </a>
                <a href="../caja/estado.php" class="sidebar-link">
                    <i class="fas fa-cash-register"></i>
                    <span>Gestión de Caja</span>
                </a>
                <a href="../ventas/historial.php" class="sidebar-link">
                    <i class="fas fa-receipt"></i>
                    <span>Historial Ventas</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <h4 class="section-title">Consultas</h4>
                <a href="../inventario/consulta.php" class="sidebar-link">
                    <i class="fas fa-boxes"></i>
                    <span>Inventario</span>
                </a>
                <a href="../clientes/consulta.php" class="sidebar-link">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                </a>
                <a href="../productos/populares.php" class="sidebar-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Estadísticas</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <h4 class="section-title">Configuración</h4>
                <div class="sidebar-toggle">
                    <span><i class="fas fa-moon"></i> Modo Oscuro</span>
                    <label class="switch">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="sidebar-toggle">
                    <span><i class="fas fa-bell"></i> Notificaciones</span>
                    <label class="switch">
                        <input type="checkbox" id="notificationsToggle" checked>
                        <span class="slider"></span>
                    </label>
                </div>
                <a href="../perfil/index.php" class="sidebar-link">
                    <i class="fas fa-user-cog"></i>
                    <span>Mi Perfil</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <a href="../../../controllers/AuthController.php?action=logout" class="sidebar-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
        
        <div class="sidebar-footer">
            <p class="version">v1.0.0 • <?php echo date('Y'); ?></p>
        </div>
    </aside>

    <!-- Modal de Perfil -->
    <div class="modal-overlay" id="profileModalOverlay"></div>
    <div class="modal" id="profileModal">
        <div class="modal-header">
            <h3>Mi Perfil</h3>
            <button class="btn-close-modal" id="btnCloseProfileModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-content">
            <!-- Contenido cargado via AJAX -->
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Cargando perfil...</p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../../assets/js/mobile/main.js"></script>

    <script>
        // Variables globales para JavaScript
        const AppConfig = {
            cajeroId: <?php echo $cajero_id ?? 0; ?>,
            cajaAbierta: <?php echo ($estado_caja['abierta'] ?? false) ? 'true' : 'false'; ?>,
            usuarioNombre: "<?php echo $usuario['nombre'] ?? 'Usuario'; ?>",
            baseUrl: "<?php echo dirname($_SERVER['PHP_SELF']); ?>",
            apiUrl: "../../../controllers/"
        };
    </script>
</body>
</html>
<?php } ?>