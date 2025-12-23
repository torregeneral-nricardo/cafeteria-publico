<?php
/**
 * Formulario de Registro de Administradores
 * Ubicación: /views/registro.php
 */
session_start();
require_once '../config/db.php';

// Control de acceso: Solo SuperAdmin puede registrar nuevos usuarios
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'SuperAdmin') {
    header('Location: dashboard.php?error=no_autorizado');
    exit();
}

// Obtener roles disponibles para el select
$stmt = $pdo->query("SELECT id, nombre_rol FROM roles");
$roles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Administrador - Sistema</title>
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
                radial-gradient(at 0% 0%, rgba(234, 179, 8, 0.1) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(30, 41, 59, 0.4) 0px, transparent 50%);
            font-family: 'Inter', sans-serif;
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glass-input {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        .glass-input:focus {
            border-color: #EAB308;
            background: rgba(15, 23, 42, 0.8);
            outline: none;
        }
    </style>
</head>
<body class="min-h-screen text-white flex flex-col">

    <!-- Navegación Superior -->
    <nav class="border-b border-white/10 bg-dark-900/50 backdrop-blur-md p-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="admin_roles.php" class="text-gold-500 hover:text-gold-400 flex items-center gap-2 transition-all group font-semibold">
                <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                <span class="text-sm uppercase tracking-wider">Volver a Gestión de Usuarios</span>
            </a>
        </div>
    </nav>

    <main class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-xl glass-panel p-8 lg:p-12 rounded-3xl shadow-2xl">
            <!-- Encabezado del Formulario -->
            <div class="mb-10 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gold-500/10 rounded-2xl mb-4 border border-gold-500/20">
                    <i class="fas fa-user-plus text-gold-500 text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white tracking-tight">Nuevo Usuario</h1>
                <p class="text-slate-400 mt-2 text-sm">Asigna una nueva cuenta de acceso al equipo administrativo.</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-400 p-4 rounded-xl text-sm mb-6 flex items-center gap-3 animate-pulse">
                    <i class="fas fa-exclamation-circle text-lg"></i>
                    <span>Error: El correo electrónico ya se encuentra registrado en el sistema.</span>
                </div>
            <?php endif; ?>

            <form action="../controllers/UsuarioController.php?action=store" method="POST" class="space-y-6">
                <!-- Nombre y Rol -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-300 ml-1">Nombre Completo</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input type="text" name="nombre" required
                                class="w-full glass-input pl-11 pr-4 py-3 rounded-xl transition-all text-sm"
                                placeholder="Ej: Juan Pérez">
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-slate-300 ml-1">Rol Asignado</label>
                        <div class="relative">
                            <i class="fas fa-shield-alt absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <select name="rol_id" required
                                class="w-full glass-input pl-11 pr-10 py-3 rounded-xl transition-all text-sm appearance-none cursor-pointer">
                                <?php foreach($roles as $rol): ?>
                                    <option value="<?php echo $rol['id']; ?>" class="bg-dark-800"><?php echo $rol['nombre_rol']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300 ml-1">Correo Electrónico</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input type="email" name="email" required
                            class="w-full glass-input pl-11 pr-4 py-3 rounded-xl transition-all text-sm"
                            placeholder="admin@ejemplo.com">
                    </div>
                </div>

                <!-- Contraseña -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-300 ml-1">Contraseña Temporal</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input type="password" name="password" required
                            class="w-full glass-input pl-11 pr-4 py-3 rounded-xl transition-all text-sm"
                            placeholder="••••••••">
                    </div>
                    <p class="text-[11px] text-slate-500 italic flex items-center gap-1 ml-1">
                        <i class="fas fa-info-circle"></i>
                        El usuario deberá actualizarla en su primer inicio de sesión.
                    </p>
                </div>

                <!-- Botón de Acción -->
                <div class="pt-4">
                    <button type="submit" 
                        class="w-full bg-gold-500 hover:bg-gold-600 text-dark-900 font-bold py-4 rounded-xl transition-all shadow-xl shadow-gold-500/20 transform hover:-translate-y-1 active:scale-[0.98]">
                        <i class="fas fa-save mr-2"></i>
                        Crear Cuenta de Usuario
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer class="p-8 text-center text-slate-500 text-xs tracking-widest uppercase">
        &copy; 2025 Sistema de Gestión Centralizado
    </footer>

</body>
</html>