<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }

        /* --- INICIO: Animaciones --- */
        /* Keyframes para la animación de entrada */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Clases de utilidad para aplicar las animaciones */
        .animate-fadeInDown {
            animation: fadeInDown 0.6s ease-out forwards;
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        /* CORRECCIÓN: Ocultar solo los elementos que se van a animar */
        .form-container .animate-fadeInUp,
        .form-container .animate-fadeInDown {
            opacity: 0;
        }
        /* --- FIN: Animaciones --- */

    </style>
</head>
<body class="bg-[#0f172a] flex items-center justify-center h-screen">

    <!-- Se añade la clase 'form-container' para gestionar las animaciones de los hijos -->
    <div class="w-full max-w-md p-8 space-y-8 bg-[#1e293b] rounded-xl shadow-2xl form-container">
        
        <!-- Animación para el logo y el título -->
        <div class="text-center animate-fadeInDown" style="animation-delay: 0.1s;">
            <div class="mx-auto mb-4 w-24 h-24 flex items-center justify-center bg-gradient-to-br from-slate-700 to-slate-800 rounded-full shadow-lg border-2 border-slate-600">
                <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <h1 class="text-3xl font-bold text-white">
                Bienvenido de Nuevo
            </h1>
            <p class="text-gray-400 mt-2">
                Inicia sesión para acceder a tu sucursal
            </p>
        </div>

        <!-- Se aplica la animación a cada elemento del formulario con un retraso escalonado -->
        <form class="mt-8 space-y-6" action="#" method="POST">
            <div class="rounded-md shadow-sm -space-y-px">
                <div class="animate-fadeInUp" style="animation-delay: 0.3s;">
                    <label for="username" class="sr-only">Usuario</label>
                    <input id="username" name="username" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-600 bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-[#6a5acd] focus:border-[#6a5acd] focus:z-10 sm:text-sm rounded-t-md transition-all" placeholder="Nombre de usuario">
                </div>
                <div class="animate-fadeInUp" style="animation-delay: 0.4s;">
                    <label for="password" class="sr-only">Contraseña</label>
                    <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-3 border border-gray-600 bg-gray-700 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-[#6a5acd] focus:border-[#6a5acd] focus:z-10 sm:text-sm rounded-b-md transition-all" placeholder="Contraseña">
                </div>
            </div>

            <div class="animate-fadeInUp" style="animation-delay: 0.5s;">
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-[#6a5acd] hover:bg-[#5a4cad] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white transition-all duration-300 ease-in-out transform hover:scale-105">
                    Iniciar Sesión
                </button>
            </div>
        </form>
    </div>

    <!-- Estos scripts no se modifican -->
    <script src="js/rutas.js"></script>
    <script src="js/login.js"></script>
</body>
</html>
