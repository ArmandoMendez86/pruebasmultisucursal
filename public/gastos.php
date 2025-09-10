<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.75);
        }

        /* --- Estilos para DataTables adaptados al tema --- */
        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background-color: var(--color-bg-primary);
            color: var(--color-text-primary);
            border: 1px solid var(--color-border);
            border-radius: 0.375rem;
            padding: 0.5rem;
        }

        .dataTables_wrapper .dataTables_filter label,
        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_info {
            color: var(--color-text-secondary);
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

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--color-bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--color-text-secondary); }
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
                <h1 class="text-lg font-bold text-[var(--color-text-primary)]">Gestión de Gastos</h1>
                <div class="w-8"></div>
            </header>
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Gestión de Gastos</h1>
                <button id="add-expense-btn" class="bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-2 px-4 rounded-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i> Añadir Gasto
                </button>
            </div>

            <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow overflow-hidden p-4">
                <table id="gastosTable" class="min-w-full">
                    <thead class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase">
                        <tr>
                            <th class="py-3 px-6 text-left">Fecha</th>
                            <th class="py-3 px-6 text-left">Categoría</th>
                            <th class="py-3 px-6 text-left">Descripción</th>
                            <th class="py-3 px-6 text-right">Monto</th>
                            <th class="py-3 px-6 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <!-- El contenido será cargado por DataTables -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="expense-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-lg">
            <div class="p-6 border-b border-[var(--color-border)] flex justify-between items-center">
                <h2 id="modal-title" class="text-2xl font-bold text-[var(--color-text-primary)]">Registrar Nuevo Gasto</h2>
                <button id="close-modal-btn" class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">&times;</button>
            </div>
            <form id="expense-form" class="p-6 space-y-4">
                <input type="hidden" id="expense-id" name="id">
                <div>
                    <label for="categoria_gasto" class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Categoría</label>
                    <input type="text" id="categoria_gasto" name="categoria_gasto" placeholder="Ej: Renta, Servicios, Proveedores" class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" required>
                </div>
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" required></textarea>
                </div>
                <div>
                    <label for="monto" class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Monto</label>
                     <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-[var(--color-text-secondary)]">$</span>
                        <input type="text" id="monto" name="monto" class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 pl-7 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" required placeholder="0.00">
                    </div>
                </div>
                <div class="pt-4 flex justify-end">
                    <button type="button" id="cancel-btn" class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg mr-2">Cancelar</button>
                    <button type="submit" class="bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-2 px-4 rounded-lg">Guardar Gasto</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>

    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    <script src="js/gastos.js"></script>
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
