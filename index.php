<?php
session_start();
require_once 'includes/seguridad.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generarTokenCSRF();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torre General | Soluciones Empresariales</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter & Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        montserrat: ['Montserrat', 'sans-serif'],
                    },
                    colors: {
                        gold: {
                            400: '#FACC15',
                            500: '#EAB308',
                            600: '#CA8A04',
                            700: '#A16207',
                        },
                        navy: {
                            800: '#1E293B',
                            900: '#0F172A',
                        },
                        slate: {
                            100: '#F1F5F9',
                            200: '#E2E8F0',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 1s ease-in-out',
                        'slide-up': 'slideUp 0.8s ease-out',
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(40px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #ffffff;
            color: #1f2937;
            overflow-x: hidden;
        }
        
        /* Fondo con imagen */
        .hero-bg {
            background-image: url('assets/img/dibujo-edificio-GENERAL-1000x960.jpg');
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            position: relative;
        }
        
        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /** background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.85) 100%); **/
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 0.6) 100%);
        }

        /* Efectos de tarjetas */
        .card-hover {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: #EAB308;
        }
        
        /* Animaciones personalizadas */
        .animate-delay-200 {
            animation-delay: 0.2s;
        }
        
        .animate-delay-400 {
            animation-delay: 0.4s;
        }
        
        /* Líneas decorativas */
        .divider {
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, #EAB308, #FACC15);
            margin: 20px auto;
            border-radius: 2px;
        }
    </style>
</head>
<body class="font-sans">

    <!-- Navbar Superior -->
    <nav class="fixed top-0 left-0 w-full bg-white/90 backdrop-blur-md z-50 border-b border-gray-200 shadow-sm">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="absolute -inset-1 bg-gold-500 rounded-full opacity-20 blur-sm"></div>
                        <img src="assets/img/torre_general_logo_290x290.png" 
                             alt="Torre General Logo"
                             class="relative w-12 h-12 object-contain rounded-full border-2 border-gold-500/30 shadow-md"
                             onerror="this.src='https://ui-avatars.com/api/?name=TG&background=EAB308&color=fff&rounded=true&size=128'">
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 font-montserrat tracking-tight">TORRE GENERAL</h1>
                        <p class="text-xs text-gray-500 uppercase tracking-wider">Soluciones Integrales</p>
                    </div>
                </div>

                <!-- Enlaces de navegación -->
                <div class="hidden lg:flex items-center space-x-8">
                    <a href="#home" class="text-gray-700 hover:text-gold-600 font-medium transition-colors relative group">
                        <span>Inicio</span>
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gold-500 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="#about" class="text-gray-700 hover:text-gold-600 font-medium transition-colors relative group">
                        <span>Quiénes Somos</span>
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gold-500 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="#services" class="text-gray-700 hover:text-gold-600 font-medium transition-colors relative group">
                        <span>Servicios</span>
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gold-500 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <a href="#contact" class="text-gray-700 hover:text-gold-600 font-medium transition-colors relative group">
                        <span>Contáctenos</span>
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gold-500 group-hover:w-full transition-all duration-300"></span>
                    </a>
                    <!-- a href="controllers/AuthController.php?action=login" class="text-gray-700 hover:text-gold-600 font-medium transition-colors relative group" -->
                    <a href="#" onclick="toggleModal('login-modal'); return false;" class="text-gray-700 hover:text-gold-600 font-medium transition-colors relative group" >
                        <span>Portal Empresarial</span>
                        <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gold-500 group-hover:w-full transition-all duration-300"></span>
                    </a>
                </div>

                <!-- Botón Login & Menú móvil -->
                <div class="flex items-center space-x-4">
                    <!-- 
                    <button onclick="toggleModal('login-modal')" 
                            class="px-6 py-2.5 bg-gold-500 text-white font-semibold rounded-lg hover:bg-gold-600 transition-all shadow-md hover:shadow-lg flex items-center space-x-2">
                        <i class="fas fa-sign-in-alt"></i>
                        <span class="hidden sm:inline">Acceso</span>
                    </button>
                    -->
                    
                    <button id="mobile-menu-button" class="lg:hidden text-gray-700">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Menú móvil -->
        <div id="mobile-menu" class="lg:hidden hidden bg-white border-t border-gray-200 px-6 py-4 shadow-lg">
            <div class="flex flex-col space-y-4">
                <a href="#home" class="text-gray-700 hover:text-gold-600 font-medium py-2 border-b border-gray-100">Inicio</a>
                <a href="#about" class="text-gray-700 hover:text-gold-600 font-medium py-2 border-b border-gray-100">Quiénes Somos</a>
                <a href="#services" class="text-gray-700 hover:text-gold-600 font-medium py-2 border-b border-gray-100">Servicios</a>
                <a href="#contact" class="text-gray-700 hover:text-gold-600 font-medium py-2 border-b border-gray-100">Contáctenos</a>
                <!-- a href="controllers/AuthController.php?action=login" class="text-gray-700 hover:text-gold-600 font-medium py-2">Portal Empresarial</a -->
                <a onclick="toggleModal('login-modal')" class="text-gray-700 hover:text-gold-600 font-medium py-2">Portal Empresarial</a>
                 
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-bg min-h-screen flex items-center pt-20 relative">
        <div class="container mx-auto px-6 py-20 relative z-10">
            <div class="max-w-3xl animate__animated animate__fadeInUp">
                <!-- div class="inline-flex items-center space-x-2 bg-white/80 backdrop-blur-sm border border-gold-200 text-gold-700 px-5 py-2.5 rounded-full mb-8 shadow-sm">
                    <i class="fas fa-building text-gold-500"></i>
                    <span class="text-sm font-semibold uppercase tracking-wider">Liderazgo Empresarial</span>
                </div -->
                
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-gray-900 leading-tight mb-6 font-montserrat">
                    <span class="text-gray-800">Impulsando</span><br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-gold-500 to-yellow-600">
                        Innovación Corporativa
                    </span>
                </h1>
                
                <p class="text-xl text-gray-600 mb-10 max-w-2xl leading-relaxed">
                    En <strong class="text-gold-600">Torre General</strong> transformamos visiones en resultados tangibles. 
                    Más de 15 años de excelencia en gestión empresarial, creando soluciones que permean.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-6 mb-16">
                    <a href="#contact" 
                       class="px-8 py-4 bg-gold-500 text-white font-bold rounded-xl hover:bg-gold-600 transition-all shadow-xl hover:shadow-2xl hover:scale-105 flex items-center justify-center space-x-3 group">
                        <span>Iniciar Proyecto</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                    </a>
                    
                    <a href="#about" 
                       class="px-8 py-4 bg-white text-gray-800 font-bold rounded-xl border-2 border-gray-300 hover:border-gold-500 transition-all hover:shadow-xl flex items-center justify-center space-x-3">
                        <i class="fas fa-play-circle text-gold-500"></i>
                        <span>Conocer Más</span>
                    </a>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 pt-8 border-t border-gray-200">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gold-600 mb-2">15+</div>
                        <div class="text-sm text-gray-600 uppercase tracking-wider">Años Experiencia</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gold-600 mb-2">200+</div>
                        <div class="text-sm text-gray-600 uppercase tracking-wider">Proyectos Completados</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gold-600 mb-2">98%</div>
                        <div class="text-sm text-gray-600 uppercase tracking-wider">Satisfacción Clientes</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gold-600 mb-2">24/7</div>
                        <div class="text-sm text-gray-600 uppercase tracking-wider">Soporte Dedicado</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Elementos decorativos flotantes -->
        <div class="absolute top-20 right-10 w-32 h-32 bg-gold-500/10 rounded-full blur-3xl animate-float hidden lg:block"></div>
        <div class="absolute bottom-20 left-10 w-40 h-40 bg-blue-500/5 rounded-full blur-3xl animate-float animate-delay-200 hidden lg:block"></div>
    </section>

    <!-- Sección Quiénes Somos -->
    <section id="about" class="py-20 bg-slate-100">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-6 font-montserrat">Nuestra Filosofía Corporativa</h2>
                <div class="divider"></div>
                <p class="text-gray-600 text-lg max-w-3xl mx-auto">
                    Creamos ecosistemas empresariales donde la eficiencia y la innovación convergen para generar valor sostenible.
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="card-hover bg-white p-8 rounded-2xl shadow-lg">
                    <div class="w-16 h-16 bg-gold-500/10 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-chart-line text-gold-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Gestión Estratégica</h3>
                    <p class="text-gray-600 mb-6">
                        Optimizamos recursos y procesos para maximizar la rentabilidad y el crecimiento sostenible.
                    </p>
                    <a href="#" class="text-gold-600 font-semibold hover:text-gold-700 inline-flex items-center">
                        Explorar <i class="fas fa-chevron-right ml-2 text-sm"></i>
                    </a>
                </div>
                
                <div class="card-hover bg-white p-8 rounded-2xl shadow-lg">
                    <div class="w-16 h-16 bg-gold-500/10 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-users-cog text-gold-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Soluciones Personalizadas</h3>
                    <p class="text-gray-600 mb-6">
                        Desarrollamos estrategias a medida que se adaptan a las necesidades específicas de cada cliente.
                    </p>
                    <a href="#" class="text-gold-600 font-semibold hover:text-gold-700 inline-flex items-center">
                        Descubrir <i class="fas fa-chevron-right ml-2 text-sm"></i>
                    </a>
                </div>
                
                <div class="card-hover bg-white p-8 rounded-2xl shadow-lg">
                    <div class="w-16 h-16 bg-gold-500/10 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-shield-alt text-gold-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Seguridad y Confianza</h3>
                    <p class="text-gray-600 mb-6">
                        Implementamos los más altos estándares de seguridad y confidencialidad en todas nuestras operaciones.
                    </p>
                    <a href="#" class="text-gold-600 font-semibold hover:text-gold-700 inline-flex items-center">
                        Conocer <i class="fas fa-chevron-right ml-2 text-sm"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección Servicios -->
    <section id="services" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-6 font-montserrat">Áreas de Especialización</h2>
                <div class="divider"></div>
                <p class="text-gray-600 text-lg max-w-3xl mx-auto">
                    Nuestro expertise se extiende a través de múltiples dominios empresariales, ofreciendo soluciones integrales.
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-slate-100 to-white p-6 rounded-xl border border-gray-200 hover:border-gold-500 transition-colors">
                    <i class="fas fa-handshake text-gold-600 text-3xl mb-4"></i>
                    <h4 class="font-bold text-gray-900 mb-2">Consultoría Empresarial</h4>
                    <p class="text-gray-600 text-sm">Análisis estratégico y planes de crecimiento personalizados.</p>
                </div>
                
                <div class="bg-gradient-to-br from-slate-100 to-white p-6 rounded-xl border border-gray-200 hover:border-gold-500 transition-colors">
                    <i class="fas fa-chart-pie text-gold-600 text-3xl mb-4"></i>
                    <h4 class="font-bold text-gray-900 mb-2">Análisis Financiero</h4>
                    <p class="text-gray-600 text-sm">Optimización de recursos y planificación fiscal estratégica.</p>
                </div>
                
                <div class="bg-gradient-to-br from-slate-100 to-white p-6 rounded-xl border border-gray-200 hover:border-gold-500 transition-colors">
                    <i class="fas fa-network-wired text-gold-600 text-3xl mb-4"></i>
                    <h4 class="font-bold text-gray-900 mb-2">Gestión de Proyectos</h4>
                    <p class="text-gray-600 text-sm">Implementación y seguimiento de iniciativas corporativas.</p>
                </div>
                
                <div class="bg-gradient-to-br from-slate-100 to-white p-6 rounded-xl border border-gray-200 hover:border-gold-500 transition-colors">
                    <i class="fas fa-user-tie text-gold-600 text-3xl mb-4"></i>
                    <h4 class="font-bold text-gray-900 mb-2">Desarrollo Organizacional</h4>
                    <p class="text-gray-600 text-sm">Fortalecimiento de equipos y cultura corporativa.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección Contacto -->
    <section id="contact" class="py-20 bg-navy-900 text-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-6 font-montserrat">Conectemos Nuestras Visiones</h2>
                <div class="divider"></div>
                <p class="text-gray-300 text-lg max-w-2xl mx-auto">
                    Estamos aquí para transformar sus desafíos en oportunidades. Permítanos ser su aliado estratégico.
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-gold-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-map-marker-alt text-gold-400 text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-xl mb-4">Ubicación Central</h4>
                    <p class="text-gray-400">Av. La Estancia, Edificio General<br>Urb. Chuao, Caracas - Venezuela</p>
                </div>
                
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-gold-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-phone text-gold-400 text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-xl mb-4">Contacto Directo</h4>
                    <p class="text-gray-400">+58 (414) 123-4567<br>info@torregeneral.com</p>
                </div>
                
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-gold-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-clock text-gold-400 text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-xl mb-4">Horario de Atención</h4>
                    <p class="text-gray-400">Lun-Vie: 8:00 AM - 6:00 PM<br>Sábados: 9:00 AM - 1:00 PM</p>
                </div>
            </div>
            
            <div class="mt-12 max-w-2xl mx-auto bg-white/5 backdrop-blur-sm p-8 rounded-2xl border border-white/10">
                <h3 class="text-2xl font-bold mb-6 text-center">Envíenos un Mensaje</h3>
                <form class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <input type="text" placeholder="Nombre Completo" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:border-gold-500">
                        <input type="email" placeholder="Correo Electrónico" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:border-gold-500">
                    </div>
                    <textarea placeholder="Su Mensaje..." rows="4" class="w-full bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:border-gold-500"></textarea>
                    <button type="submit" class="w-full bg-gold-500 hover:bg-gold-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg hover:shadow-xl">
                        Enviar Mensaje
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-navy-800 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-8 md:mb-0">
                    <div class="flex items-center space-x-3 mb-4">
                        <img src="assets/img/torre_general_logo_290x290.png" 
                             alt="Logo"
                             class="w-10 h-10 object-contain rounded-full border border-gold-500/30"
                             onerror="this.src='https://ui-avatars.com/api/?name=TG&background=EAB308&color=fff&rounded=true'">
                        <div>
                            <h3 class="font-bold text-xl">TORRE GENERAL</h3>
                            <p class="text-gray-400 text-sm">Excelencia Empresarial</p>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm max-w-md">
                        Transformando visiones corporativas en realidades tangibles desde 2008.
                    </p>
                </div>
                
                <div class="flex space-x-6 mb-8 md:mb-0">
                    <a href="#" class="text-gray-400 hover:text-gold-400 transition-colors text-xl">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gold-400 transition-colors text-xl">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gold-400 transition-colors text-xl">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gold-400 transition-colors text-xl">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                </div>
                
                <div class="text-center md:text-right">
                    <p class="text-gray-400 text-sm mb-2">© 2025 Torre General. Todos los derechos reservados.</p>
                    <p class="text-gray-500 text-xs">
                        <a href="#" class="hover:text-gold-400 transition-colors">Política de Privacidad</a> • 
                        <a href="#" class="hover:text-gold-400 transition-colors">Términos de Servicio</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modal de Login (mantenido de la versión anterior) -->
    <!-- Modal de Login (sección actualizada con ojito) -->
    <div id="login-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/90 backdrop-blur-md transition-opacity cursor-pointer" onclick="toggleModal('login-modal')"></div>
        
        <div class="relative bg-white w-full max-w-md rounded-3xl p-8 lg:p-10 shadow-2xl animate-slide-down border border-gray-200 z-10">
            <button onclick="toggleModal('login-modal')" class="absolute top-6 right-6 text-gray-500 hover:text-gray-700 transition-colors cursor-pointer">
                <i class="fas fa-times text-xl"></i>
            </button>
            
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gold-500/10 text-gold-500 mb-4">
                    <div class="relative group">
                        <div class="absolute -inset-1 bg-gold-500 rounded-full opacity-25 group-hover:opacity-50 blur transition duration-200"></div>
                        <img src="assets/img/torre_general_logo_290x290.png" 
                             alt="Logo"
                             class="relative rounded-full border-2 border-gray-200 w-12 h-12 object-cover shadow-lg" 
                             onerror="this.src='https://ui-avatars.com/api/?name=Logo&background=EAB308&color=fff&rounded=true'">
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Portal Empresarial</h2>
                <p class="text-gray-600 text-sm mt-1">Acceso exclusivo para miembros autorizados</p>
            </div>
    
            <form action="controllers/AuthController.php?action=login" method="POST" class="space-y-5" id="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700 ml-1">Correo Electrónico</label>
                    <div class="relative">
                        <i class="fas fa-at absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="email" name="email" placeholder="usuario@torregeneral.com" 
                               class="w-full bg-gray-50 border border-gray-300 text-gray-900 pl-11 pr-4 py-4 rounded-xl focus:ring-2 focus:ring-gold-500 focus:border-transparent transition-all text-sm" required>
                    </div>
                </div>
    
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700 ml-1">Contraseña</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="password" name="password" id="login-password" placeholder="••••••••" 
                                class="w-full bg-gray-50 border border-gray-300 text-gray-900 pl-11 pr-12 py-4 rounded-xl focus:ring-2 focus:ring-gold-500 focus:border-transparent transition-all text-sm" required>
                        <button type="button" id="toggle-password" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="flex justify-between pt-1">
                        <a href="views/recuperar.php" class="text-xs text-gold-600 hover:text-gold-700 hover:underline transition-colors">
                            ¿Olvidaste tu contraseña?
                        </a>
                        <a href="views/registro_publico.php" class="text-xs text-gray-600 hover:text-gray-800 hover:underline transition-colors">
                            Crear nueva cuenta
                        </a>
                    </div>
                </div>
    
                <button type="submit" class="w-full bg-gold-500 hover:bg-gold-600 text-white font-bold py-4 rounded-xl transition-all shadow-lg hover:shadow-xl mt-4 uppercase tracking-wider">
                    <i class="fas fa-sign-in-alt mr-2"></i>Ingresar al Sistema
                </button>
            </form>
    
            <div class="mt-6 text-center pt-6 border-t border-gray-200">
                <p class="text-gray-500 text-sm">
                    Acceso restringido. Solo personal autorizado.<br>
                    <span class="text-xs text-gray-400">Problemas técnicos? Contacte al departamento de IT.</span>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle modal
        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            modal.classList.toggle('hidden');
        }
        
        // Toggle mobile menu
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
            this.innerHTML = menu.classList.contains('hidden') 
                ? '<i class="fas fa-bars text-xl"></i>' 
                : '<i class="fas fa-times text-xl"></i>';
        });
        
        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                    
                    // Close mobile menu if open
                    const mobileMenu = document.getElementById('mobile-menu');
                    if (!mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                        document.getElementById('mobile-menu-button').innerHTML = '<i class="fas fa-bars text-xl"></i>';
                    }
                }
            });
        });
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('shadow-lg', 'bg-white');
                nav.classList.remove('bg-white/90');
            } else {
                nav.classList.remove('shadow-lg');
                nav.classList.add('bg-white/90');
            }
        });
        
        // Toggle password visibility
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('login-password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Validación de formulario de login
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]').value;
            const password = this.querySelector('input[name="password"]').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Por favor complete todos los campos');
                return false;
            }
            
            // Validación básica de email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Por favor ingrese un correo electrónico válido');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>