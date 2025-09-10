<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
if ($_SESSION['rol'] !== 'Super') {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración del Sistema</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");

        body {
            font-family: 'Inter', sans-serif;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.75);
        }

        /* Estilos de Pestañas con Variables de Tema */
        .tab-btn {
            background-color: transparent;
            color: var(--color-text-secondary);
            border-color: transparent;
            transition: all 0.2s ease-in-out;
        }

        .tab-btn:hover {
            color: var(--color-text-primary);
        }

        .tab-btn.active {
            background-color: var(--color-accent);
            color: white;
            border-color: var(--color-accent);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--color-bg-primary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--color-border);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--color-text-secondary);
        }
    </style>
</head>

<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] flex h-screen overflow-hidden">

    <?php $currentPage = 'admin.php'; ?>
    <?php include_once __DIR__ . '/../parciales/navegacion.php'; ?>

    <main class="flex-1 p-6 overflow-y-auto">

        <header
            class="lg:hidden flex items-center justify-between bg-[var(--color-bg-secondary)] p-4 shadow-md flex-shrink-0">
            <button id="mobile-menu-button" class="text-[var(--color-text-primary)] focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <h1 class="text-lg font-bold text-[var(--color-text-primary)]">Administración</h1>
            <div class="w-8"></div>
        </header>
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-[var(--color-text-primary)] mb-6">Administración del Sistema</h1>

            <div class="mb-6">
                <div class="border-b border-[var(--color-border)]">
                    <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                        <button
                            class="tab-btn active whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm rounded-t-lg"
                            data-tab="sucursales">
                            <i class="fas fa-building mr-2"></i>Sucursales
                        </button>
                        <button class="tab-btn whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm rounded-t-lg"
                            data-tab="usuarios">
                            <i class="fas fa-users-cog mr-2"></i>Usuarios
                        </button>
                    </nav>
                </div>
            </div>

            <div id="tab-sucursales" class="tab-content active">
                <?php include_once __DIR__ . '/../parciales/admin_sucursales.php'; ?>
            </div>

            <div id="tab-usuarios" class="tab-content">
                <?php include_once __DIR__ . '/../parciales/admin_usuarios.php'; ?>
            </div>
        </div>
    </main>

    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    <script src="js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (mobileMenuButton && sidebar && overlay) {
                mobileMenuButton.addEventListener('click', (e) => {
                    e.stopPropagation();
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                });

                overlay.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                });
            }
        });
    </script>
</body>

</html>