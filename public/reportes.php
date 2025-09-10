<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        /* --- Estilos Generales y Scrollbar con Variables de Tema --- */
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

        /* --- Estilos para DataTables adaptados al tema --- */
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select,
        .dt-buttons .dt-button {
            background-color: var(--color-bg-primary) !important;
            color: var(--color-text-primary) !important;
            border: 1px solid var(--color-border) !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
        }

        .dt-buttons .dt-button:hover {
            background-color: var(--color-border) !important;
        }

        .dataTables_wrapper .dataTables_filter label,
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_info {
            color: var(--color-text-secondary) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: var(--color-text-primary) !important;
            border: 1px solid transparent;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: var(--color-text-secondary) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--color-accent) !important;
            border-color: var(--color-accent) !important;
            color: white !important;
        }

        table.dataTable thead th {
            border-bottom: 2px solid var(--color-border) !important;
        }

        table.dataTable tbody tr {
            background-color: var(--color-bg-secondary);
        }

        table.dataTable tbody tr:hover {
            background-color: var(--color-bg-primary);
        }

        table.dataTable tbody td {
            border-bottom: 1px solid var(--color-border);
        }

        table.dataTable.no-footer {
            border-bottom: 1px solid var(--color-border);
        }

        .dataTables_wrapper .dataTables_processing {
            background: var(--color-bg-secondary);
            color: var(--color-text-primary);
            border: 1px solid var(--color-border);
        }

        .ticket-id-cell {
            color: #818cf8;
            font-weight: 600;
        }
    </style>
    <!-- DataTables + Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

</head>

<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)]">

    <div class="flex h-screen">

        <?php include_once '../parciales/navegacion.php'; ?>

        <!-- Contenido Principal -->
        <main class="flex-1 p-8 overflow-y-auto">
            <header
                class="lg:hidden flex items-center justify-between bg-[var(--color-bg-secondary)] p-4 shadow-md flex-shrink-0">
                <button id="mobile-menu-button" class="text-[var(--color-text-primary)] focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-lg font-bold text-[var(--color-text-primary)]">Reportes</h1>
                <div class="w-8"></div>
            </header>
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Reportes y Análisis</h1>

                <button id="config-printer-btn"
                    class="text-[var(--color-text-secondary)] hover:text-white transition-colors duration-200">
                    <i class="fas fa-cog text-xl"></i>
                </button>
            </div>

            <!-- Sección de Reporte de Ventas -->
            <div class="bg-[var(--color-bg-secondary)] p-6 rounded-lg mb-8">
                <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4">Reporte de Ventas</h2>

                <!-- Tabla de Reporte -->
                <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow overflow-auto mb-8 p-4">
                    <table id="tablaVentas" class="min-w-full">
                        <thead class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase">
                            <tr>
                                <th class="py-3 px-6 text-left">Fecha</th>
                                <th class="py-3 px-6 text-left">Ticket ID</th>
                                <th class="py-3 px-6 text-left">Cliente</th>
                                <th class="py-3 px-6 text-left">Vendedor</th>
                                <th class="py-3 px-6 text-right">Total</th>
                                <th class="py-3 px-6 text-left">Estado</th>
                                <th class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs">
                            <!-- El contenido se carga vía server-side por DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sección de Corte de Caja -->
            <div class="bg-[var(--color-bg-secondary)] p-6 rounded-lg">
                <h2 class="text-xl font-semibold text-[var(--color-text-primary)] mb-4">Corte de Caja Diaria</h2>
                <!-- Filtros para el Corte de Caja -->
                <div class="flex flex-wrap items-end gap-4 mb-6">
                    <div>
                        <label for="cash-cut-date"
                            class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Fecha del
                            Corte</label>
                        <input type="date" id="cash-cut-date"
                            class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]">
                    </div>
                    <div>
                        <label for="initial-cash"
                            class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Caja
                            Inicial</label>
                        <input type="number" id="initial-cash" value="0.00" step="0.01"
                            class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] w-32">
                    </div>
                    <?php if (isset($_SESSION['rol']) && ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Super')): ?>
                        <div>
                            <label for="user-filter-select"
                                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Filtrar por
                                Usuario</label>
                            <select id="user-filter-select"
                                class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]">
                                <option value="all">General (Toda la Sucursal)</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <!-- Usuario actual para impresión -->
                    <input type="hidden" id="current-user-id" value="<?= (int) ($_SESSION['user_id'] ?? 0) ?>">
                    <input type="hidden" id="current-user-name"
                        value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">


                    <button id="generate-cash-cut-btn"
                        class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg">Generar Corte de
                        Caja</button>
                    <button id="print-cash-cut-btn"
                        class="bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                        <i class="fas fa-print mr-2"></i> Imprimir Corte
                    </button>
                </div>
                <!-- Aquí irán los resultados del corte -->
                <div id="cash-cut-results" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <p class="text-[var(--color-text-secondary)] col-span-full">Seleccione una fecha para generar el
                        corte de caja.</p>
                </div>
            </div>
            <div id="printer-config-modal"
                class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50">
                <div class="bg-[var(--color-bg-primary)] p-6 rounded-lg shadow-xl w-full max-w-md">
                    <h3 class="text-xl font-bold mb-4">Configurar Método de Impresión</h3>
                    <p class="text-sm text-[var(--color-text-secondary)] mb-6">
                        Elige el método que prefieras para imprimir tickets y reportes desde esta sección.
                    </p>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="radio" id="print-method-service" name="print_method_reportes" value="service"
                                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <label for="print-method-service" class="ml-3 block text-sm font-medium">
                                Servicio de Impresión Local (Recomendado)
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="print-method-qztray" name="print_method_reportes" value="qztray"
                                class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                            <label for="print-method-qztray" class="ml-3 block text-sm font-medium">
                                QZ Tray (Conexión directa a la impresora)
                            </label>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end space-x-3">
                        <button id="cancel-printer-config-btn" class="btn-secondary">Cancelar</button>
                        <button id="save-printer-config-btn" class="btn-primary">Guardar Cambios</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/js-sha256@0.9.0/src/sha256.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2/qz-tray.min.js"></script>
    <script src="js/qz-tray-handler.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    <script src="js/reportes.js"></script>
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