<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torre General | Bienvenida</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
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
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s ease-out forwards',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'slide-down': 'slideDown 0.3s ease-out forwards',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        slideDown: {
                            '0%': { opacity: '0', transform: 'translateY(-20px) scale(0.95)' },
                            '100%': { opacity: '1', transform: 'translateY(0) scale(1)' },
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
                radial-gradient(at 0% 0%, rgba(234, 179, 8, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(15, 23, 42, 1) 0px, transparent 50%);
            color: #ffffff;
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
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
            background: rgba(15, 23, 42, 0.8);
        }
        /* Clase de utilidad para ocultar elementos pero mantener accesibilidad si fuera necesario */
        .hidden {
            display: none;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 selection:bg-gold-500 selection:text-white overflow-x-hidden relative">

    <!-- Navbar Superior -->
    <nav class="absolute top-0 left-0 w-full p-6 flex justify-between items-center z-40">
        <div class="flex items-center gap-3">
            <div class="relative group">
                <div class="absolute -inset-1 bg-gold-500 rounded-full opacity-25 group-hover:opacity-50 blur transition duration-200"></div>
                <img src="assets/img/torre_general_logo_290x290.png" 
                     alt="Logo"
                     class="relative rounded-full border-2 border-white/10 w-12 h-12 object-cover shadow-lg" 
                     onerror="this.src='https://ui-avatars.com/api/?name=TG&background=EAB308&color=fff&rounded=true'">
            </div>
            <span class="font-bold text-lg tracking-wide hidden sm:block">Torre GENERAL</span>
        </div>
        
        <button onclick="toggleModal('login-modal')" class="ml-auto flex items-center gap-2 px-5 py-2 rounded-full border border-white/10 hover:border-gold-500/50 bg-dark-800/50 hover:bg-dark-800 transition-all text-sm font-medium backdrop-blur-sm group cursor-pointer">
            <i class="fas fa-user-circle text-gold-500 text-lg group-hover:text-white transition-colors"></i>
            <span>Acceso Privado</span>
        </button>
    </nav>

    <!-- Alerta de Error (PHP MVC Integration) -->
    <?php if(isset($_GET['error'])): ?>
    <div class="fixed top-24 left-1/2 -translate-x-1/2 z-50 animate-bounce">
        <div class="bg-red-500/20 border border-red-500/50 backdrop-blur-md text-red-200 px-6 py-3 rounded-2xl flex items-center gap-3 shadow-2xl">
            <i class="fas fa-exclamation-triangle text-red-500"></i>
            <span class="text-sm font-bold uppercase tracking-tighter">
                <?php 
                    echo ($_GET['error'] == 'auth') ? 'Credenciales Inválidas' : 'Acceso Denegado';
                ?>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <div class="max-w-6xl w-full grid grid-cols-1 lg:grid-cols-2 gap-12 items-center z-10 pt-20 lg:pt-0">
        
        <!-- Columna de Texto -->
        <div class="order-2 lg:order-1 flex flex-col justify-center space-y-8 animate-fade-in-up">
            <div class="inline-flex items-center space-x-2 bg-gold-500/10 border border-gold-500/20 text-gold-400 px-4 py-2 rounded-full w-fit">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gold-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-gold-500"></span>
                </span>
                <span class="text-xs font-semibold tracking-wider uppercase">En Desarrollo</span>
            </div>

            <div class="space-y-4">
                <h1 class="text-5xl md:text-6xl font-bold tracking-tight leading-tight">
                    Estamos construyendo <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-gold-400 to-yellow-200">
                        el futuro digital.
                    </span>
                </h1>
                <p class="text-slate-400 text-lg md:text-xl font-light max-w-lg leading-relaxed">
                    Nuestra nueva experiencia web está siendo diseñada meticulosamente. Prepárate para algo extraordinario.
                </p>
            </div>

            <!-- Formulario de Suscripción -->
            <div class="w-full max-w-md bg-white/5 p-2 rounded-xl border border-white/10 focus-within:border-gold-500/50 transition-colors duration-300">
                <form class="flex flex-col sm:flex-row gap-2" action="controllers/SubController.php" method="POST">
                    <input type="email" id="email" name="email" required placeholder="Tu correo electrónico" 
                        class="flex-1 bg-transparent text-white placeholder-slate-500 px-4 py-3 outline-none rounded-lg focus:ring-0">
                    <button type="submit" 
                        class="bg-gold-500 hover:bg-gold-600 text-dark-900 font-bold py-3 px-6 rounded-lg transition-all transform hover:scale-105 shadow-lg shadow-gold-500/20 whitespace-nowrap cursor-pointer">
                        Notificarme
                    </button>
                </form>
            </div>
            <p id="feedback" class="text-green-400 text-sm hidden animate-pulse"><i class="fas fa-check-circle mr-2"></i>¡Registrado con éxito!</p>

            <div class="pt-8 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex space-x-6 text-slate-400">
                    <a href="#" class="hover:text-gold-400 transition-colors text-xl"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="hover:text-gold-400 transition-colors text-xl"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="hover:text-gold-400 transition-colors text-xl"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <p class="text-slate-600 text-[10px] uppercase font-bold tracking-widest">© 2025 Torre General</p>
            </div>
        </div>

        <!-- Columna Visual -->
        <div class="order-1 lg:order-2 relative flex justify-center animate-fade-in-up" style="animation-delay: 0.2s;">
            <div class="absolute inset-0 bg-gold-500/20 blur-[100px] rounded-full opacity-30 animate-pulse-slow"></div>
            <div class="relative glass-panel rounded-3xl p-4 shadow-2xl w-full max-w-lg transform rotate-2 hover:rotate-0 transition-transform duration-700 ease-out">
                <div class="flex items-center gap-2 mb-4 px-2 opacity-50">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <div class="bg-dark-900/50 rounded-xl overflow-hidden aspect-square flex items-center justify-center relative border border-white/5">
                    <img 
                        src="https://cdn.pixabay.com/animation/2023/08/11/21/18/21-18-05-265_512.gif" 
                        alt="Animación Construcción" 
                        class="w-full h-full object-cover opacity-90"
                    >
                </div>
                <div class="absolute -bottom-6 -right-6 glass-panel px-6 py-4 rounded-xl shadow-xl border border-gold-500/20 hidden sm:block">
                    <p class="text-xs text-slate-400 mb-2 uppercase tracking-wide">Fase de Lanzamiento</p>
                    <div class="flex items-center gap-3">
                        <div class="w-32 h-2 bg-dark-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gold-500 w-[85%] rounded-full shadow-[0_0_10px_rgba(234,179,8,0.5)]"></div>
                        </div>
                        <span class="font-mono text-gold-400 font-bold">85%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VENTANA EMERGENTE: LOGIN (MODAL) -->
    <div id="login-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-dark-900/90 backdrop-blur-md transition-opacity cursor-pointer" onclick="toggleModal('login-modal')"></div>
        
        <div class="relative glass-panel w-full max-w-md rounded-3xl p-8 lg:p-10 shadow-2xl animate-slide-down border border-white/10 z-10">
            <button onclick="toggleModal('login-modal')" class="absolute top-6 right-6 text-slate-500 hover:text-white transition-colors cursor-pointer">
                <i class="fas fa-times text-xl"></i>
            </button>
            
            <!--
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gold-500/10 text-gold-500 mb-4 border border-gold-500/20">
                    <i class="fas fa-fingerprint text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-white tracking-tight">Acceso Administrativo</h2>
                <p class="text-slate-400 text-sm mt-1">Identifíquese para gestionar la plataforma</p>
            </div>
            -->
            
            <!-- Encabezado -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gold-500/10 text-gold-500 mb-4">
                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gold-500 rounded-full opacity-25 group-hover:opacity-50 blur transition duration-200"></div>
                        <!-- 'rounded-full' hace el círculo. 'object-cover' asegura que la imagen llene el círculo sin estirarse -->
                        <img src="assets/img/torre_general_logo_290x290.png" 
                             alt="Logo"
                             class="relative rounded-full border-2 border-white/10 w-12 h-12 object-cover shadow-lg" 
                             onerror="this.src='https://ui-avatars.com/api/?name=Logo&background=EAB308&color=fff&rounded=true'">
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-white">Torre General</h2>
                <p class="text-slate-400 text-sm mt-1">Ingresa a tu cuenta </p>
            </div>

            <!-- Formulario de Login -->

            <!-- Ajustado para MVC: el action apunta al controlador de autenticación -->
            <form action="controllers/AuthController.php?action=login" method="POST" class="space-y-5">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-slate-300 ml-1">Correo Electrónico</label>
                    <div class="relative">
                        <i class="fas fa-at absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input type="email" name="email" placeholder="admin@sistema.com" 
                               class="w-full glass-input pl-11 pr-4 py-4 rounded-xl focus:ring-0 transition-all text-sm" required>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-slate-300 ml-1">Contraseña</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"></i>
                        <input type="password" name="password" laceholder="••••••••" 
                                class="w-full glass-input pl-10 pr-4 py-3 rounded-lg focus:ring-0 transition-all text-sm" required>
                    </div>
                    <div class="flex justify-end pt-1">
                        <a href="#" class="text-xs text-gold-500 hover:text-gold-400 hover:underline transition-colors">¿Olvidaste tu contraseña?</a>
                    </div>
                </div>


                <button type="submit" class="w-full bg-gold-500 hover:bg-gold-600 text-dark-900 font-black py-4 rounded-xl transition-all shadow-xl shadow-gold-500/20 mt-4 uppercase tracking-widest text-xs cursor-pointer">
                    Ingresar
                </button>
            </form>

            <!-- Pie del Modal -->
            <div class="mt-6 text-center pt-6 border-t border-white/10">
                <p class="text-slate-400 text-sm">
                    ¿No tienes una cuenta? 
                    <a href="#" class="text-white font-semibold hover:text-gold-400 transition-colors ml-1">Regístrate aquí</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            modal.classList.toggle('hidden');
        }

        function handleSubmit(event) {
            event.preventDefault();
            const emailInput = document.getElementById('email');
            const feedback = document.getElementById('feedback');
            if(emailInput.value) {
                emailInput.value = '';
                feedback.classList.remove('hidden');
                setTimeout(() => feedback.classList.add('hidden'), 5000);
            }
        }
    </script>
</body>
</html>