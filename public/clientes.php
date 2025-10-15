<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.75);
        }

        .modal-body {
            /* Se ajusta la altura para dar más espacio a las pestañas y el footer */
            max-height: 60vh;
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

        /* --- Tabs: estado visible --- */
        .tab-button {
            color: var(--color-text-secondary);
            border-bottom: 2px solid transparent;
            padding: 0.5rem 1rem;
            /* respeta tu layout */
            transition: color .15s ease, border-color .15s ease;
            cursor: pointer;
        }

        .tab-button:hover {
            color: var(--color-text-primary);
            border-color: var(--color-border);
        }

        .tab-button.active {
            color: var(--color-text-primary);
            border-color: var(--color-accent);
            font-weight: 700;
            /* resalta la activa */
        }

        /* --- Botón cerrar del modal (X) --- */
        #close-modal-btn {
            font-size: 1.75rem;
            /* ~text-3xl */
            width: 2.5rem;
            /* 40px: mejor área clicable */
            height: 2.5rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .5rem;
            /* rounded-lg */
            color: var(--color-text-secondary);
        }

        #close-modal-btn:hover {
            background: var(--color-bg-primary);
            color: var(--color-text-primary);
        }

        /* Estilo del Contenedor de Bloqueo (Overlay) */
        #global-loader-overlay {
            display: none;
            /* Por defecto, oculto */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Fondo oscuro y semitransparente para bloquear la pantalla */
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            /* Centrar contenido (el spinner y el texto) */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            /* Color del texto */
            font-family: sans-serif;
            font-size: 1.2em;
        }

        /* Estilo del Spinner (Animación de carga) */
        .spinner {
            border: 6px solid rgba(255, 255, 255, 0.3);
            /* Color claro para el cuerpo del aro */
            border-top: 6px solid #3498db;
            /* Color de contraste (azul) para la parte móvil */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            margin-bottom: 15px;
            /* Espacio entre spinner y texto */
            animation: spin 1s linear infinite;
        }

        /* Animación de rotación */
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
</head>

<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)]">

    <div id="global-loader-overlay">
        <div class="spinner"></div>
        <p>Cargando cliente...</p>
    </div>
    <div class="flex h-screen">
        <?php include_once '../parciales/navegacion.php'; ?>

        <main class="flex-1 p-8 overflow-y-auto">
            <header
                class="lg:hidden flex items-center justify-between bg-[var(--color-bg-secondary)] p-4 shadow-md flex-shrink-0">
                <button id="mobile-menu-button" class="text-[var(--color-text-primary)] focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <h1 class="text-lg font-bold text-[var(--color-text-primary)]">Punto de Venta</h1>
                <div class="w-8"></div>
            </header>
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-[var(--color-text-primary)]">Gestión de Clientes</h1>
                <button id="add-client-btn"
                    class="bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-2 px-4 rounded-lg flex items-center shadow">
                    <i class="fas fa-plus mr-2"></i> Añadir Cliente
                </button>
            </div>

            <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto mb-8 p-4">
                    <table id="clientesTable" class="min-w-full">
                        <thead
                            class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase">
                            <tr>
                                <th class="py-3 px-6 text-left">Nombre</th>
                                <th class="py-3 px-6 text-left">Teléfono</th>
                                <th class="py-3 px-6 text-left">Email</th>
                                <th class="py-3 px-6 text-left">Tipo</th> <!-- NUEVA -->
                                <th class="py-3 px-6 text-right">Deuda Actual</th>
                                <th class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-[var(--color-border)] text-sm"></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Cliente con Tema Dinámico -->
    <div id="client-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] rounded-lg shadow-xl w-full max-w-4xl">
            <div class="p-6 border-b border-[var(--color-border)] flex justify-between items-center">
                <h2 id="modal-title" class="text-2xl font-bold">Añadir Nuevo Cliente</h2>
                <button id="close-modal-btn"
                    class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">&times;</button>
            </div>

            <div class="px-6 pt-4">
                <div class="flex border-b border-[var(--color-border)]">
                    <button data-tab="personales"
                        class="tab-button active text-sm font-medium py-2 px-4 border-b-2 border-transparent hover:border-[var(--color-text-secondary)] focus:outline-none">Datos
                        Personales</button>
                    <button data-tab="direcciones"
                        class="tab-button text-sm font-medium py-2 px-4 border-b-2 border-transparent hover:border-[var(--color-text-secondary)] focus:outline-none">Direcciones</button>
                    <button data-tab="credito"
                        class="tab-button text-sm font-medium py-2 px-4 border-b-2 border-transparent hover:border-[var(--color-text-secondary)] focus:outline-none">Crédito
                        y Precios</button>
                    <button data-tab="envio"
                        class="tab-button text-sm font-medium py-3 px-4 border-b-2 border-transparent text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:border-[var(--color-text-secondary)]">
                        Formato de Envío
                    </button>

                </div>
            </div>

            <form id="client-form">
                <div class="modal-body overflow-y-auto p-6">
                    <input type="hidden" id="client-id" name="id">

                    <!-- PESTAÑA 1: DATOS PERSONALES -->
                    <div id="tab-content-personales" class="tab-content active">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="nombre"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Nombre
                                    Completo</label>
                                <input type="text" id="nombre" name="nombre"
                                    class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                                    required>
                            </div>
                            <div>
                                <label for="rfc"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">RFC</label>
                                <input type="text" id="rfc" name="rfc"
                                    class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                            </div>
                            <div>
                                <label for="telefono"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono"
                                    class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                            </div>
                            <div class="md:col-span-2">
                                <label for="email"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Email</label>
                                <input type="email" id="email" name="email"
                                    class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑA 2: DIRECCIONES -->
                    <div id="tab-content-direcciones" class="tab-content">
                        <div id="addresses-container" class="space-y-4 mb-4"></div>
                        <button type="button" id="add-address-btn"
                            class="text-sm bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                            <i class="fas fa-plus mr-2"></i>Añadir Dirección
                        </button>
                    </div>

                    <!-- PESTAÑA 3: CRÉDITO Y PRECIOS -->
                    <div id="tab-content-credito" class="tab-content">
                        <h3 class="text-lg font-semibold mb-4 border-b border-[var(--color-border)] pb-2">Crédito</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="md:col-span-2">
                                <label class="flex items-center">
                                    <input type="checkbox" id="tiene_credito" name="tiene_credito"
                                        class="h-4 w-4 text-[var(--color-accent)] bg-[var(--color-bg-primary)] border-[var(--color-border)] rounded focus:ring-[var(--color-accent)]">
                                    <span class="ml-2 text-sm">Habilitar línea de crédito</span>
                                </label>
                            </div>
                            <div id="limite-credito-container" class="hidden">
                                <label for="limite_credito"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Límite de
                                    Crédito</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3 flex items-center text-[var(--color-text-secondary)]">$</span>
                                    <input type="text" id="limite_credito" name="limite_credito" placeholder="0.00"
                                        class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 pl-7 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                                </div>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold mb-4 border-b border-[var(--color-border)] pb-2">Precios
                            Especiales por Producto</h3>
                        <div class="mb-4 relative">
                            <input type="text" id="product-search-input"
                                placeholder="Buscar producto por nombre o SKU para añadir..."
                                class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                            <div id="product-search-results"
                                class="absolute z-10 w-full bg-[var(--color-bg-secondary)] border border-[var(--color-border)] rounded-md mt-1 max-h-60 overflow-y-auto hidden shadow-lg">
                            </div>
                        </div>
                        <div id="special-prices-container" class="space-y-3"></div>
                    </div>
                    <!-- PESTAÑA: FORMATO DE ENVÍO -->
                    <div id="tab-content-envio" class="tab-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="space-y-4">
                                <label for="obs_envio"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)]">
                                    Observaciones de envío (aparecen en el recuadro amarillo)
                                </label>
                                <textarea id="obs_envio" name="obs_envio" rows="4" class="w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)]
                                focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                                    placeholder="Ej.: Transportes de Ochoa&#10;Calle Juan R. Zavala #512, San Juan Bosco, 44360 Guadalajara, Jal."></textarea>

                                <div class="flex items-center gap-3">
                                    <button type="button" id="preview-shipping-btn"
                                        class="rounded-lg bg-[var(--color-accent)] px-4 py-2 font-bold text-white hover:bg-[var(--color-accent-hover)]">
                                        Vista previa
                                    </button>
                                    <button type="button" id="print-shipping-btn"
                                        class="rounded-lg bg-blue-600 px-4 py-2 font-bold text-white hover:bg-blue-500">
                                        Imprimir
                                    </button>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div
                                class="bg-white rounded-lg border border-[var(--color-border)] p-6 text-black md:h-full">
                                <div id="shipping-preview" class="space-y-3 text-[15px]">
                                    <p class="text-sm text-gray-600">Genera una vista previa…</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6 border-t border-[var(--color-border)]">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div class="w-full sm:w-auto sm:flex-1 sm:max-w-xs">
                            <select id="id_tipo" name="id_tipo"
                                class="w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg-primary)] p-2 focus:border-[var(--color-accent)] focus:ring-[var(--color-accent)]">
                                <option value="">Seleccione tipo de cliente</option>
                            </select>
                        </div>
                        <div class="flex items-center justify-end gap-3">
                            <button type="button" id="cancel-btn"
                                class="rounded-lg bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-600">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="rounded-lg bg-[var(--color-accent)] px-4 py-2 font-bold text-white hover:bg-[var(--color-accent-hover)]">
                                Guardar Cliente
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Pago con Tema Dinámico -->
    <div id="payment-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6 border-b border-[var(--color-border)] flex justify-between items-center">
                <h2 id="payment-modal-title" class="text-2xl font-bold">Registrar Abono</h2>
                <button id="close-payment-modal-btn"
                    class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">&times;</button>
            </div>
            <form id="payment-form">
                <div class="p-6">
                    <input type="hidden" id="payment-client-id" name="id_cliente">
                    <div class="space-y-4">
                        <div>
                            <label for="payment-client-name"
                                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Cliente</label>
                            <input type="text" id="payment-client-name"
                                class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 border border-[var(--color-border)]"
                                readonly>
                        </div>
                        <div>
                            <label for="payment-client-debt"
                                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Deuda
                                Actual</label>
                            <input type="text" id="payment-client-debt"
                                class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 border border-[var(--color-border)]"
                                readonly>
                        </div>
                        <div>
                            <label for="monto_abono"
                                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Monto a
                                Abonar</label>
                            <input type="text" id="monto_abono" name="monto" placeholder="0.00"
                                class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                                required>
                        </div>
                        <div>
                            <label for="metodo_pago_abono"
                                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Método de
                                Pago</label>
                            <select id="metodo_pago_abono" name="metodo_pago"
                                class="w-full bg-[var(--color-bg-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                                required>
                                <option>Efectivo</option>
                                <option>Tarjeta</option>
                                <option>Transferencia</option>
                            </select>
                        </div>
                        <!-- Detalle del Pago/Abono -->
                        <div class="mt-3">
                            <label for="detalle_abono"
                                class="text-sm font-medium text-[var(--color-text-secondary)]">Detalle del
                                Pago/Abono</label>
                            <textarea id="detalle_abono" name="detalle_abono" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)]
                   focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)] text-sm" rows="3"
                                maxlength="300" placeholder="Detalle del Pago/Abono"></textarea>
                            <p class="text-xs mt-1 opacity-70">Máx. 300 caracteres</p>
                        </div>

                        <!-- Fecha de Recibido (editable) -->
                        <div class="mt-3">
                            <label for="fecha_recibido_abono"
                                class="text-sm font-medium text-[var(--color-text-secondary)]">Fecha de Recibido</label>
                            <div class="flex items-center gap-2">
                                <input type="date" id="fecha_recibido_abono" name="fecha_recibido_abono" class="mt-1 w-[180px] bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)]
                  focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)] text-sm">
                                <!-- (opcional) checkbox para “hoy” -->
                                <label class="inline-flex items-center gap-2 text-xs opacity-80">
                                    <input type="checkbox" id="fecha_recibido_hoy" class="h-4 w-4">
                                    Hoy
                                </label>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="p-6 border-t border-[var(--color-border)] flex justify-end">
                    <button type="button" id="cancel-payment-btn"
                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg mr-2">Cancelar</button>
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg">Registrar
                        Abono</button>
                </div>
            </form>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    <script src="js/clientes.js"></script>


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