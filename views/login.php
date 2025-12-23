<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0F172A;
            background-image: radial-gradient(at 0% 0%, rgba(234, 179, 8, 0.1) 0px, transparent 50%);
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 text-white">

    <div class="w-full max-w-md bg-slate-800/50 backdrop-blur-xl p-8 rounded-2xl border border-white/10 shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gold-500">Acceso Admin</h1>
            <p class="text-slate-400 text-sm mt-2">Introduce tus credenciales para continuar</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-400 px-4 py-3 rounded-lg text-sm mb-6 text-center">
                <?php 
                    echo ($_GET['error'] == 'credenciales_invalidas') ? 'Email o contraseña incorrectos.' : 'Error en el sistema.';
                ?>
            </div>
        <?php endif; ?>

        <form action="../controllers/AuthController.php?action=login" method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Correo Electrónico</label>
                <input type="email" name="email" required 
                    class="w-full bg-slate-900/50 border border-white/10 px-4 py-3 rounded-xl focus:outline-none focus:border-gold-500 transition-all text-white"
                    placeholder="admin@sistema.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Contraseña</label>
                <input type="password" name="password" required 
                    class="w-full bg-slate-900/50 border border-white/10 px-4 py-3 rounded-xl focus:outline-none focus:border-gold-500 transition-all text-white"
                    placeholder="••••••••">
            </div>

            <button type="submit" 
                class="w-full bg-gold-500 hover:bg-gold-600 text-slate-900 font-bold py-3 rounded-xl transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-gold-500/20">
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-8 text-center border-t border-white/5 pt-6">
            <a href="../index.php" class="text-slate-500 hover:text-white text-sm transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Volver al sitio
            </a>
        </div>
    </div>

</body>
</html>