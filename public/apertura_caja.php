<?php
// Archivo: /public/apertura_caja.php
// Esta página se mostrará al administrador si la caja no ha sido abierta para el día.

// Se asume que la sesión ya está iniciada y verificada por LoginController
// y que el usuario es un administrador.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirigir si no hay sesión o no es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'Administrador') { // <-- CORREGIDO: Usar 'rol'
    header('Location: login.php'); // O la página principal de login
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apertura de Caja - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Estilos para el modal */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }
        .modal-content {
            z-index: 1001;
        }
    </style>
</head>
<body class="bg-[#0f172a] text-gray-300 flex items-center justify-center h-screen">

    <div class="modal-overlay fixed inset-0 flex items-center justify-center">
        <div class="modal-content bg-[#1e293b] p-8 rounded-lg shadow-xl w-full max-w-md mx-4">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">Apertura de Caja Diaria</h2>
            <p class="text-gray-400 text-center mb-6">Por favor, ingresa el monto inicial con el que abres la caja para el día de hoy.</p>

            <form id="cash-opening-form" class="space-y-6">
                <div>
                    <label for="monto-inicial" class="block text-sm font-medium text-gray-300 mb-2">Monto Inicial en Caja</label>
                    <input type="number" id="monto-inicial" name="monto_inicial" step="0.01" min="0" value="0.00" required
                           class="w-full p-3 rounded-md bg-gray-700 border border-gray-600 text-white focus:ring-blue-500 focus:border-blue-500 text-lg text-center">
                </div>
                <button type="submit" id="open-cash-btn"
                        class="w-full bg-[#4f46e5] hover:bg-[#4338ca] text-white font-bold py-3 px-4 rounded-lg transition duration-200 ease-in-out">
                    <i class="fas fa-cash-register mr-2"></i> Abrir Caja
                </button>
            </form>
            <div id="message-area" class="mt-4 text-center text-sm"></div>
        </div>
    </div>

    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/apertura_caja.js"></script>
</body>
</html>
