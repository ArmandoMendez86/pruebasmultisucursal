<?php
// Archivo: /public/reporte_global.php
require_once __DIR__ . '/../parciales/verificar_sesion.php';

// Solo los super administradores pueden ver esta página
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Super') {
    header('Location: pos.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Global de Ventas - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        /* --- Estilos para DataTables adaptados al tema --- */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input,
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
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label,
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
        table.dataTable thead th,
        table.dataTable tfoot th {
            border-bottom: 2px solid var(--color-border) !important;
        }
        table.dataTable tbody tr {
            background-color: var(--color-bg-secondary);
        }
        table.dataTable tbody tr:hover {
            background-color: var(--color-bg-primary);
        }
        table.dataTable tbody td {
            border-top: 1px solid var(--color-border) !important;
        }
        .dataTables_wrapper .dataTables_processing {
            background: var(--color-bg-secondary);
            color: var(--color-text-primary);
            border: 1px solid var(--color-border);
        }
        tfoot {
            background-color: var(--color-bg-primary);
            font-weight: bold;
        }

        .ticket-id-cell {
            color: #818cf8;
            font-weight: 600;
        }

        /* --- Estilos para Daterangepicker adaptados al tema --- */
        .daterangepicker {
            background-color: var(--color-bg-secondary);
            border-color: var(--color-border);
        }
        .daterangepicker .ranges li {
            color: var(--color-text-primary);
            background-color: var(--color-bg-primary);
            border: 1px solid var(--color-border);
        }
        .daterangepicker .ranges li:hover {
            background-color: var(--color-accent);
            color: #ffffff;
        }
        .daterangepicker .ranges li.active {
            background-color: var(--color-accent-hover);
            color: #ffffff;
        }
        .daterangepicker .calendar-table {
            background-color: var(--color-bg-secondary);
        }
        .daterangepicker .month {
            color: var(--color-text-primary);
        }
        .daterangepicker th,
        .daterangepicker td {
            color: var(--color-text-secondary);
        }
        .daterangepicker td.available:hover {
            background-color: var(--color-bg-primary);
            color: var(--color-text-primary);
        }
        .daterangepicker td.off,
        .daterangepicker td.off.in-range,
        .daterangepicker td.off.start-date,
        .daterangepicker td.off.end-date {
            background-color: transparent;
            color: var(--color-border);
        }
        .daterangepicker td.in-range {
            background-color: rgba(from var(--color-accent) r g b / 0.4);
            color: var(--color-text-primary);
        }
        .daterangepicker td.active,
        .daterangepicker td.active:hover {
            background-color: var(--color-accent);
            color: #ffffff;
        }
        .daterangepicker .drp-buttons .btn {
            background-color: var(--color-bg-primary);
            border-color: var(--color-border);
            color: var(--color-text-primary);
        }
        .daterangepicker .drp-buttons .applyBtn {
            background-color: var(--color-accent);
            border-color: var(--color-accent);
            color: #ffffff;
        }
        .daterangepicker .drp-buttons .applyBtn:hover {
            background-color: var(--color-accent-hover);
        }
        .daterangepicker:after,
        .daterangepicker:before {
            border-bottom-color: var(--color-bg-secondary) !important;
        }

        .dt-top-controls, .dt-bottom-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
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
            <header class="lg:hidden flex items-center justify-between bg-[var(--color-bg-secondary)] p-4 shadow-md flex-shrink-0">
                <button id="mobile-menu-button" class="text-[var(--color-text-primary)] focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-lg font-bold text-[var(--color-text-primary)]">Reporte Global</h1>
                <div class="w-8"></div>
            </header>
            <h1 class="text-3xl font-bold text-[var(--color-text-primary)] mb-8">Reporte Global de Ventas</h1>

            <div class="bg-[var(--color-bg-secondary)] p-4 rounded-lg mb-6">
                <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-3">Filtros Personalizados</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="daterange-filter" class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Rango de Fechas</label>
                        <div class="relative">
                            <i class="fas fa-calendar-alt absolute top-1/2 left-3 transform -translate-y-1/2 text-[var(--color-text-secondary)]"></i>
                            <input type="text" id="daterange-filter" class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 pl-10 border border-[var(--color-border)] w-full cursor-pointer" placeholder="Selecciona un rango de fechas">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-[var(--color-bg-secondary)] p-6 rounded-lg">
                <table id="global-sales-table" class="display responsive nowrap text-sm" style="width:100%">
                    <thead class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase">
                        <tr>
                            <th>Fecha</th>
                            <th>Ticket ID</th>
                            <th>Sucursal</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th class="text-right">Total</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm"></tbody>
                    <tfoot class="text-[var(--color-text-primary)]">
                        <tr>
                            <th colspan="5" class="text-right">Total de la Vista Actual:</th>
                            <th id="current-view-total" class="text-right"></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/reporte_global.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
