<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creditos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.75);
        }

        .modal-body {
            max-height: 65vh;
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

<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)]">
    <div class="flex h-screen">
        <?php include_once '../parciales/navegacion.php'; ?>
        <main class="flex-1 p-8 overflow-y-auto">
            <header
                class="lg:hidden flex items-center justify-between bg-[var(--color-bg-secondary)] p-4 shadow-md flex-shrink-0">
                <button id="mobile-menu-button" class="text-[var(--color-text-primary)] focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-lg font-bold text-[var(--color-text-primary)]">Inventario</h1>
                <div class="w-8"></div>
            </header>
            <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow overflow-auto mb-8 p-4">
                <h3 class="text-2xl font-bold text-[var(--color-text-primary)] mb-8">
                    <i class="fas fa-usd mr-3 text-blue-400"></i> Ventas a Credito
                </h3>
                <table id="tabla-ventas-credito" class="min-w-full">
                    <thead class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase">
                        <tr>
                            <th class="px-3 py-2">Cliente</th>
                            <th class="px-3 py-2">Venta</th>
                            <th class="px-3 py-2">Fecha</th>
                            <th class="px-3 py-2 text-right">Crédito</th>
                            <th class="px-3 py-2 text-right">Abonado</th>
                            <th class="px-3 py-2 text-right">Saldo</th>
                            <th class="px-3 py-2">Estatus</th>
                            <th class="px-3 py-2 text-center">Productos</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs"></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="px-3 py-2 text-right">Total (filtro):</th>
                            <th class="px-3 py-2 text-right">
                                <span id="sum-saldo">$0.00</span>
                            </th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>

            </div>

            <!-- MODAL Detalle de Productos -->
            <div id="modal-detalle-venta" class="fixed inset-0 z-50 hidden modal-overlay">
                <div class="absolute inset-0 " data-close-modal></div>
                <div
                    class="relative mx-auto mt-10 w-full max-w-3xl rounded-xl shadow-xl bg-[var(--color-bg-secondary)]">
                    <div class="flex items-center justify-between px-4 py-3 border-b">
                        <h3 class="font-bold">Detalle de Productos — Venta <span id="md-venta-id"></span></h3>
                        <button id="btn-cerrar-modal"
                            class="px-3 py-1 rounded-md bg-purple-800 text-white">Cerrar</button>
                    </div>
                    <div class="p-4">
                        <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow overflow-auto mb-8 p-4">
                            <table class="w-full">
                                <thead
                                    class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase">
                                    <tr>
                                        <th class="px-3 py-2">Producto</th>
                                        <th class="px-3 py-2 text-right">Cantidad</th>
                                        <th class="px-3 py-2 text-right">Precio</th>
                                        <th class="px-3 py-2 text-right">Importe</th>
                                    </tr>
                                </thead>
                                <tbody id="md-tbody" class="divide-y text-xs"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="px-3 py-2 text-right">Total</td>
                                        <td id="md-total" class="px-3 py-2 text-right">$0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow overflow-auto mb-8 p-4">
                <div class="mt-10 p-4">
                    <h3 class="text-2xl font-bold text-[var(--color-text-primary)] mb-8">
                        <i class="fas fa-usd mr-3 text-blue-400"></i> Aplicaciones de Pagos (pago → venta)
                    </h3>
                    <div class="overflow-x-auto">
                        <table id="tabla-aplicaciones" class="min-w-full text-xs">
                            <thead class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase">
                                <tr>
                                    <th class="px-3 py-2">#Pago</th>
                                    <th class="px-3 py-2">Cliente</th>
                                    <th class="px-3 py-2">Usuario</th>
                                    <th class="px-3 py-2">Fecha recibido</th>
                                    <th class="px-3 py-2 text-right">Monto pago</th>
                                    <th class="px-3 py-2">Método</th>
                                    <th class="px-3 py-2">Detalle</th>
                                    <th class="px-3 py-2">Venta</th>
                                    <th class="px-3 py-2">Fecha venta</th>
                                    <th class="px-3 py-2 text-right">Aplicado</th>
                                    <th class="px-3 py-2 text-right">Crédito venta</th>
                                    <th class="px-3 py-2 text-right">Saldo actual</th>
                                    <th class="px-3 py-2">Fecha sistema</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <script src="js/rutas.js"></script>
    <script src="js/creditos.js"></script>

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