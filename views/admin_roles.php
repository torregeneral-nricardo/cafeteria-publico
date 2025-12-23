<?php
/**
 * Panel de Gestión de Roles y Usuarios
 * Ubicación: /views/admin_roles.php
 */
session_start();
require_once '../config/db.php';
require_once '../models/Usuario.php';
require_once '../models/Rol.php';

// Control de acceso: Solo SuperAdmin puede gestionar roles
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'SuperAdmin') {
    header('Location: dashboard.php?error=permiso_denegado');
    exit();
}

// Lógica del Algoritmo: Obtención de datos
try {
    $usuarioModel = new Usuario($pdo);
    $usuarios = $usuarioModel->obtenerTodos();

    // Obtener roles para los select de edición
    $stmtRoles = $pdo->query("SELECT * FROM roles");
    $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si hay un error de base de datos, evitamos el error 500 mostrando un mensaje amigable
    die("Error en la conexión o consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Roles - Administración</title>
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
        }
        .glass-input {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }
        .glass-input:focus {
            border-color: #EAB308;
            outline: none;
        }
        .nav-glass {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="nav-glass sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="text-gold-500 hover:text-gold-400 transition-all group flex items-center gap-2">
                    <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                    <span class="font-bold tracking-wider uppercase text-xs">Volver al Panel</span>
                </a>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Operador:</span>
                <span class="text-xs font-semibold text-white"><?php echo htmlspecialchars($_SESSION['user_nombre']); ?></span>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto p-6 lg:p-10 w-full">
        <!-- Encabezado -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-white mb-2">Roles y Permisos</h1>
                <p class="text-slate-400 font-medium">Control jerárquico de los administradores del sistema.</p>
            </div>
            <a href="registro.php" class="bg-gold-500 text-dark-900 px-4 py-3 rounded-2xl font-bold hover:bg-gold-600 transition-all shadow-xl shadow-gold-500/20 flex items-center gap-3 transform hover:-translate-y-1 active:scale-95">
                <i class="fas fa-user-plus"></i>
                Nuevo Usuario
            </a>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="glass-panel rounded-3xl overflow-hidden shadow-2xl border border-white/10">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white/5 border-b border-white/10">
                        <tr>
                            <th class="p-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Administrador</th>
                            <th class="p-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Correo Electrónico</th>
                            <th class="p-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Nivel de Acceso</th>
                            <th class="p-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Registro</th>
                            <th class="p-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($usuarios as $u): ?>
                        <tr class="hover:bg-white/[0.03] transition-colors group">
                            <td class="p-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-slate-800 border border-white/10 flex items-center justify-center text-gold-500 font-bold group-hover:bg-gold-500 group-hover:text-dark-900 transition-all">
                                        <?php echo strtoupper(substr($u['nombre'], 0, 1)); ?>
                                    </div>
                                    <span class="font-bold text-sm text-white"><?php echo htmlspecialchars($u['nombre']); ?></span>
                                </div>
                            </td>
                            <td class="p-5 text-slate-300 text-sm font-medium"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="p-5">
                                <span class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest border 
                                    <?php echo $u['nombre_rol'] === 'SuperAdmin' ? 'bg-purple-500/10 text-purple-400 border-purple-500/30' : 'bg-blue-500/10 text-blue-400 border-blue-500/30'; ?>">
                                    <i class="fas <?php echo $u['nombre_rol'] === 'SuperAdmin' ? 'fa-crown' : 'fa-user-shield'; ?> mr-1.5"></i>
                                    <?php echo htmlspecialchars($u['nombre_rol']); ?>
                                </span>
                            </td>
                            <td class="p-5 text-slate-400 text-xs font-mono">
                                <?php echo date('d.m.Y', strtotime($u['fecha_creacion'])); ?>
                            </td>
                            <td class="p-5 text-center">
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <button onclick="openModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES); ?>', <?php echo $u['rol_id']; ?>)" 
                                        class="bg-white/5 hover:bg-gold-500 hover:text-dark-900 text-gold-500 px-4 py-2 rounded-xl transition-all text-xs font-bold border border-white/10 active:scale-95">
                                    <i class="fas fa-sliders-h mr-2"></i> Ajustar Rol
                                </button>
                                <?php else: ?>
                                <span class="text-slate-600 text-[10px] uppercase font-bold tracking-widest italic">Cuenta Actual</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Estilo Glassmorphism -->
    <div id="rolModal" class="fixed inset-0 bg-dark-900/90 backdrop-blur-md z-[100] hidden items-center justify-center p-4 transition-all duration-300">
        <div class="glass-panel w-full max-w-md p-8 lg:p-10 rounded-3xl shadow-2xl border border-white/10">
            
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gold-500/10 rounded-2xl flex items-center justify-center text-gold-500 text-2xl mx-auto mb-4 border border-gold-500/20">
                    <i class="fas fa-shield-virus"></i>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-tight">Cambio de Nivel</h2>
                <p id="modalUser" class="text-gold-500 text-sm font-semibold mt-1 uppercase tracking-tighter"></p>
            </div>

            <form action="../controllers/RolController.php?action=update" method="POST" class="space-y-6">
                <input type="hidden" name="usuario_id" id="modalUserId">
                
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">Seleccionar Nuevo Rol</label>
                    <div class="relative">
                        <i class="fas fa-layer-group absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <select name="nuevo_rol_id" id="modalSelectRol" class="w-full glass-input pl-11 pr-10 py-4 rounded-xl transition-all text-sm appearance-none cursor-pointer">
                            <?php foreach($roles as $r): ?>
                                <option value="<?php echo $r['id']; ?>" class="bg-dark-800"><?php echo htmlspecialchars($r['nombre_rol']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 pointer-events-none text-xs"></i>
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="closeModal()" class="flex-1 px-4 py-4 rounded-2xl bg-white/5 hover:bg-white/10 text-white font-bold text-sm border border-white/10 transition-all active:scale-95 uppercase tracking-wider">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-4 rounded-2xl bg-gold-500 text-dark-900 font-bold text-sm hover:bg-gold-600 transition-all shadow-xl shadow-gold-500/20 active:scale-95 uppercase tracking-wider">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(id, nombre, currentRolId) {
            document.getElementById('modalUserId').value = id;
            document.getElementById('modalUser').innerText = nombre;
            
            // Seleccionar el rol actual del usuario en el modal
            const select = document.getElementById('modalSelectRol');
            select.value = currentRolId;

            const modal = document.getElementById('rolModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            const modal = document.getElementById('rolModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('rolModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>

    <footer class="mt-auto p-10 text-center">
        <p class="text-slate-600 text-[10px] font-bold tracking-[0.3em] uppercase">
            &copy; 2025 Torre General &bull; Gestión de Privilegios
        </p>
    </footer>

</body>
</html>