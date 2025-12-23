<?php
/**
 * Panel de Administración Principal
 * Ubicación: /views/dashboard.php
 */
session_start();

// Control de acceso: Si no hay sesión, rebota al login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=acceso_restringido');
    exit();
}

require_once '../config/db.php';
require_once '../models/Usuario.php';

// CORRECCIÓN: Obtener conteos usando fetchColumn() para obtener el número directamente
try {
    // Conteo de suscripciones
    $stmtsubs = $pdo->query("SELECT COUNT(*) FROM suscripciones");
    $totalSuscripciones = $stmtsubs->fetchColumn();

    // Conteo de usuarios
    $stmtusers = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $totalUsuarios = $stmtusers->fetchColumn();
} catch (PDOException $e) {
    $totalSuscripciones = 0;
    $totalUsuarios = 0;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        gold: {
                            400: '#FACC15',
                            500: '#EAB308',
                            600: '#CA8A04',
                        },
                        dark: {
                            900: '#0F172A',
                            800: '#1E293B',
                            700: '#334155',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0F172A;
            background-image: 
                radial-gradient(at 0% 0%, rgba(234, 179, 8, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(30, 41, 59, 0.4) 0px, transparent 50%);
            font-family: 'Inter', sans-serif;
            color: #ffffff;
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.4);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(234, 179, 8, 0.3);
            transform: translateY(-2px);
        }
        .nav-glass {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Navbar Estilo Glassmorphism -->
    <nav class="nav-glass sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-18 flex items-center justify-between py-4">
            <div class="flex items-center gap-4">
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gold-500 rounded-full opacity-25 group-hover:opacity-50 blur transition duration-200"></div>
                        <img src="../assets/img/torre_general_logo_290x290.png" 
                            alt="Logo"
                            class="relative rounded-full border-2 border-white/10 w-12 h-12 object-cover shadow-lg" 
                            onerror="this.src='https://ui-avatars.com/api/?name=Logo&background=EAB308&color=fff&rounded=true'">
                    </div>
                <div>
                    <span class="font-bold tracking-tighter text-lg block leading-none">TORRE GENERAL</span>
                    <span class="text-[10px] text-gold-500 font-bold uppercase tracking-[0.2em]">Administración</span>
                </div>
            </div>

            <div class="flex items-center gap-4 md:gap-8">
                <div class="text-right hidden sm:block border-r border-white/10 pr-6">
                    <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest mb-0.5"><?php echo $_SESSION['user_rol'] ?? 'Admin'; ?></p>
                    <p class="text-sm font-semibold text-white"><?php echo $_SESSION['user_name'] ?? 'Usuario'; ?></p>
                </div>
                <a href="../controllers/AuthController.php?action=logout" 
                   class="flex items-center gap-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 px-4 py-2.5 rounded-xl transition-all text-sm font-bold border border-red-500/20 active:scale-95">
                    <i class="fas fa-power-off text-xs"></i>
                    <span class="hidden md:inline">Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl mx-auto w-full p-6 lg:p-10">
        <header class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-white mb-2">Panel de Control</h1>
                <p class="text-slate-400 font-medium">Gestión centralizada de recursos y usuarios.</p>
            </div>
            <div class="text-slate-500 text-xs font-mono bg-dark-800/50 px-3 py-1.5 rounded-lg border border-white/5">
                <i class="far fa-calendar-alt mr-2"></i> <?php echo date('d . m . Y'); ?>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <!-- Usuarios -->
            <div class="glass-card p-6 rounded-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 text-blue-500/5 text-7xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-users"></i>
                </div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-blue-500/20 text-blue-400 rounded-2xl flex items-center justify-center text-xl border border-blue-500/20">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Usuarios</h3>
                        <p class="text-2xl font-bold text-white leading-none">
                            <?php echo $totalUsuarios; ?>
                        </p>
                    </div>
                </div>
                <div class="h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 w-1/3"></div>
                </div>
            </div>

            <!-- Suscritos -->
            <div class="glass-card p-6 rounded-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 text-gold-500/5 text-7xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-gold-500/20 text-gold-500 rounded-2xl flex items-center justify-center text-xl border border-gold-500/20">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Suscriptores</h3>
                        <p class="text-2xl font-bold text-white leading-none">
                            <?php echo $totalSuscripciones; ?>
                        </p>
                    </div>
                </div>
                <div class="h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gold-500 w-1/2"></div>
                </div>
            </div>

            <!-- Sistema -->
            <div class="glass-card p-6 rounded-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 text-green-500/5 text-7xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-microchip"></i>
                </div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-green-500/20 text-green-400 rounded-2xl flex items-center justify-center text-xl border border-green-500/20">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Sistema</h3>
                        <p class="text-2xl font-bold text-white leading-none">Activo</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-[10px] text-green-400 font-bold uppercase">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span> Servidor Estable
                </div>
            </div>

            <!-- Uptime -->
            <div class="glass-card p-6 rounded-2xl relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 text-purple-500/5 text-7xl group-hover:scale-110 transition-transform">
                    <i class="fas fa-database"></i>
                </div>
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-purple-500/20 text-purple-400 rounded-2xl flex items-center justify-center text-xl border border-purple-500/20">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div>
                        <h3 class="text-slate-400 text-xs font-bold uppercase tracking-widest">Uptime</h3>
                        <p class="text-2xl font-bold text-white leading-none">99.9%</p>
                    </div>
                </div>
                <div class="text-[10px] text-slate-500 font-bold uppercase">Sincronizado</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="glass-panel rounded-3xl overflow-hidden border border-white/10">
                <div class="p-8 border-b border-white/10 bg-white/5 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold flex items-center gap-3 text-white">
                            <i class="fas fa-user-lock text-gold-500"></i>
                            Gestión de Usuarios
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">Control de acceso y niveles de seguridad.</p>
                    </div>
                </div>
                <div class="p-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="admin_roles.php" class="flex flex-col gap-3 p-5 rounded-2xl bg-dark-900/50 border border-white/5 hover:border-gold-500/50 transition-all group">
                        <div class="w-10 h-10 bg-slate-800 text-slate-300 rounded-xl flex items-center justify-center group-hover:bg-gold-500 group-hover:text-dark-900 transition-all">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div>
                            <span class="font-bold block text-sm">Gestionar Roles</span>
                            <span class="text-[10px] text-slate-500">Editar permisos y niveles</span>
                        </div>
                    </a>
                    <a href="registro.php" class="flex flex-col gap-3 p-5 rounded-2xl bg-dark-900/50 border border-white/5 hover:border-gold-500/50 transition-all group">
                        <div class="w-10 h-10 bg-slate-800 text-slate-300 rounded-xl flex items-center justify-center group-hover:bg-gold-500 group-hover:text-dark-900 transition-all">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div>
                            <span class="font-bold block text-sm">Añadir Usuario</span>
                            <span class="text-[10px] text-slate-500">Crear nueva cuenta</span>
                        </div>
                    </a>
                </div>
            </div>

            <div class="glass-panel rounded-3xl overflow-hidden border border-white/10">
                <div class="p-8 border-b border-white/10 bg-white/5">
                    <h2 class="text-xl font-bold flex items-center gap-3 text-white">
                        <i class="fas fa-broadcast-tower text-gold-500"></i>
                        Comunicación
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">Exportación y análisis de suscriptores.</p>
                </div>
                <div class="p-8 space-y-6">
                    <p class="text-sm text-slate-300 leading-relaxed italic border-l-2 border-gold-500 pl-4">
                        Actualmente hay <span class="text-gold-500 font-bold underline"><?php echo $totalSuscripciones; ?> usuarios</span> interesados esperando noticias del lanzamiento del proyecto.
                    </p>
                    <button class="w-full bg-white/5 hover:bg-white/10 text-white px-6 py-4 rounded-2xl font-bold text-sm transition-all flex items-center justify-center gap-3 border border-white/10 active:scale-95">
                        <i class="fas fa-download text-gold-500"></i>
                        Descargar Base de Datos (CSV)
                    </button>
                </div>
            </div>
        </div>
    </main>

    <footer class="p-10 text-center">
        <p class="text-slate-600 text-[10px] font-bold tracking-[0.3em] uppercase">
            &copy; 2025 Torre General &bull; Sistema Centralizado
        </p>
    </footer>

</body>
</html>