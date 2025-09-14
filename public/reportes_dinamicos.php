<?php
// Archivo: /public/reportes_dinamicos.php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Super') {
    header('Location: pos.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>Reportes Dinámicos</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- DataTables (SIN responsive) + Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <!-- Daterangepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <style>
        body {
            background: #0b1220;
            color: #e5e7eb
        }

        .card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: .75rem
        }

        .card-head {
            background: #0f172a;
            border-bottom: 1px solid #1f2937
        }

        .sticky-topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            background: #0b1220cc;
            backdrop-filter: blur(6px)
        }

        .scroll-y {
            max-height: 60vh;
            overflow: auto
        }

        /* === Scroll horizontal para resultados === */
        .dt-wrap {
            overflow-x: auto;
        }

        /* el contenedor scrollea */
        #dynamic-table {
            min-width: 1200px;
        }

        /* fuerza ancho mínimo para que aparezca el scroll */
        #dynamic-table th,
        #dynamic-table td {
            white-space: nowrap;
        }

        /* === Tema dark para DateRangePicker (números visibles) === */
        .daterangepicker {
            background: #0f172a !important;
            border: 1px solid #334155 !important;
            color: #e5e7eb !important;
            z-index: 10050;
            /* sobre cualquier overlay */
        }

        .daterangepicker .drp-calendar {
            background: #0f172a !important;
        }

        .daterangepicker .calendar-table {
            background: #0f172a !important;
            border-color: #334155 !important;
        }

        .daterangepicker .calendar-table th,
        .daterangepicker .calendar-table td {
            color: #cbd5e1 !important;
            /* <-- NÚMEROS Y DÍAS */
        }

        .daterangepicker td.off,
        .daterangepicker td.off.in-range,
        .daterangepicker td.off.start-date,
        .daterangepicker td.off.end-date {
            color: #475569 !important;
            /* días de otros meses */
            background: transparent !important;
        }

        .daterangepicker td.available:hover,
        .daterangepicker th.available:hover {
            background: #1e293b !important;
            color: #fff !important;
        }

        .daterangepicker td.in-range {
            background: rgba(99, 102, 241, .15) !important;
            color: #e5e7eb !important;
        }

        .daterangepicker td.start-date,
        .daterangepicker td.end-date,
        .daterangepicker td.active,
        .daterangepicker td.active:hover {
            background: #6366f1 !important;
            color: #fff !important;
        }

        .daterangepicker .ranges li {
            color: #cbd5e1 !important;
        }

        .daterangepicker .ranges li:hover {
            background: #1e293b !important;
            color: #fff !important;
        }

        .daterangepicker .ranges li.active {
            background: #374151 !important;
            color: #fff !important;
        }

        .daterangepicker .applyBtn {
            background: #6366f1 !important;
            border-color: #6366f1 !important;
            color: #fff !important;
        }

        .daterangepicker .cancelBtn {
            background: transparent !important;
            border-color: #334155 !important;
            color: #cbd5e1 !important;
        }

        .daterangepicker select.monthselect,
        .daterangepicker select.yearselect {
            background: #0b1220 !important;
            border: 1px solid #334155 !important;
            color: #e5e7eb !important;
        }

        /* Placeholder del input de rango (estético) */
        #daterange::placeholder {
            color: #94a3b8;
        }
    </style>
</head>

<body class="bg-slate-950 text-slate-100">
    <?php include __DIR__ . '/../parciales/header.php'; ?>

    <!-- Barra superior fija -->
    <div class="sticky-topbar">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-2">
            <h1 class="text-lg font-semibold me-auto">Reportes Dinámicos</h1>
            <button id="toggle-campos"
                class="lg:hidden px-3 py-2 rounded-lg border border-slate-700 hover:bg-slate-800">Campos</button>

            <!-- NUEVOS -->
            <button id="btn-open" class="px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700">Abrir…</button>
            <button id="btn-save" class="px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700">Guardar</button>

            <button id="run-report"
                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white">Ejecutar</button>
        </div>
    </div>


    <div class="max-w-7xl mx-auto p-4 space-y-4">

        <!-- Controles globales -->
        <section class="card">
            <div class="card-head px-4 py-3 flex flex-wrap items-center gap-3">
                <span class="text-sm text-slate-400">Rango de fechas</span>

                <!-- columna de fecha -->
                <select id="date-col" class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-sm"></select>

                <!-- rango -->
                <input id="daterange" class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-sm w-60"
                    placeholder="YYYY-MM-DD - YYYY-MM-DD">

                <!-- limpiar rango -->
                <button id="clear-date" type="button"
                    class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-sm">
                    Limpiar
                </button>

                <!-- activar/desactivar filtro por fecha -->
                <label class="ms-2 inline-flex items-center gap-2">
                    <input type="checkbox" id="use-date-filter" class="accent-indigo-500">
                    <span class="text-sm text-slate-300">Filtrar por fecha</span>
                </label>

                <div class="ms-auto flex items-center gap-2">
                    <button id="add-filter" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-sm">+
                        Filtro</button>
                    <button id="add-agg" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-sm">+
                        Cálculo</button>
                </div>
            </div>
        </section>

        <!-- ===== SQL LIBRE (tipo Workbench) ===== -->
        <section class="card">
            <!--  <div class="card-head px-4 py-3 flex items-center gap-2">
                <h2 class="font-semibold">SQL libre (solo SELECT)</h2>
                <div class="ms-auto flex items-center gap-2 text-sm">
                    <label class="flex items-center gap-1">
                        <span>Límite</span>
                        <input id="sql-limit" type="number" min="1" value="1000"
                            class="w-24 bg-slate-950 border border-slate-700 rounded px-2 py-1">
                    </label>
                    <button id="run-sql" class="px-3 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white">
                        Ejecutar SQL
                    </button>
                </div>
            </div> -->
            <div class="card-head px-4 py-3 flex flex-wrap items-center gap-2">
                <strong class="font-semibold">SQL libre (solo SELECT)</strong>

                <div class="ms-auto flex items-center gap-2">
                    <span class="text-sm text-slate-400">Límite</span>
                    <input id="sql-limit" type="number" min="1" value="1000"
                        class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-sm w-24"
                        placeholder="Límite">
                    <button id="run-sql" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white">
                        Ejecutar SQL
                    </button>
                </div>

                <div class="w-full"></div>

                <!-- Presets SQL -->
                <input id="sql-name" class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-sm w-56"
                    placeholder="Nombre del preset">

                <input id="sql-desc" class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-sm w-72"
                    placeholder="Descripción (opcional)">

                <select id="sql-presets" class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-sm w-72">
                    <option value="">(Cargar preset SQL...)</option>
                </select>

                <button id="sql-load" class="px-3 py-1 rounded bg-slate-800 hover:bg-slate-700">
                    Abrir
                </button>

                <button id="sql-save" class="px-3 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white">
                    Guardar
                </button>

                <button id="sql-del" class="px-3 py-1 rounded bg-rose-600 hover:bg-rose-500 text-white">
                    Eliminar
                </button>
            </div>


            <div class="p-3">
                <textarea id="sql-textarea" rows="6"
                    class="w-full bg-slate-950 border border-slate-700 rounded p-2 font-mono text-sm" placeholder="Ejemplo: SELECT v.id, v.fecha, c.nombre, v.total
            FROM ventas v
            LEFT JOIN clientes c ON c.id = v.cliente_id
            WHERE v.fecha BETWEEN '2025-01-01' AND '2025-12-31';"></textarea>
            </div>

            <div class="p-2 dt-wrap">
                <table id="sql-table" class="display w-full text-sm"></table>
            </div>
        </section>



        <!-- Layout responsive (paneles) -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- COLUMNA 1: Campos -->
            <div id="col-campos" class="block">
                <div class="card h-full">
                    <div class="card-head px-4 py-3 flex items-center justify-between">
                        <h2 class="font-semibold">Campos</h2>
                        <input id="buscar-campos" type="search"
                            class="hidden lg:block bg-slate-950 border border-slate-700 rounded px-2 py-1 text-sm w-48"
                            placeholder="Buscar…">
                    </div>
                    <div id="columns-container" class="p-4 space-y-2 text-sm scroll-y"></div>
                </div>
            </div>

            <!-- COLUMNA 2: Filtros + Agrupar -->
            <div class="space-y-4">
                <div class="card">
                    <div class="card-head px-4 py-3">
                        <h2 class="font-semibold">Filtros</h2>
                    </div>
                    <div id="filters-container" class="p-4 space-y-2 text-sm"></div>
                </div>

                <div class="card">
                    <div class="card-head px-4 py-3">
                        <h2 class="font-semibold">Agrupar</h2>
                    </div>
                    <div id="groupby-container" class="p-4 space-y-2 text-sm"></div>
                </div>
            </div>

            <!-- COLUMNA 3: Agregaciones -->
            <div>
                <div class="card h-full">
                    <div class="card-head px-4 py-3">
                        <h2 class="font-semibold">Cálculos (SUM, AVG…)</h2>
                    </div>
                    <div id="aggs-container" class="p-4 space-y-2 text-sm"></div>
                </div>
            </div>
        </section>

        <!-- Tabla de resultados con SCROLL HORIZONTAL -->
        <section class="card">
            <div class="card-head px-4 py-3 flex items-center justify-between">
                <h2 class="font-semibold">Resultados</h2>
                <input id="buscarTablaBridge" type="search"
                    class="bg-slate-950 border border-slate-700 rounded px-2 py-1 text-sm w-56"
                    placeholder="Buscar en resultados…">
            </div>
            <div class="p-2 dt-wrap">
                <table id="dynamic-table" class="display w-full text-sm"></table>
            </div>
        </section>
    </div>

    <!-- Modal: Presets -->
    <div id="presets-modal" class="fixed inset-0 hidden items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/60"></div>
        <div class="relative bg-slate-900 border border-slate-800 rounded-xl w-full max-w-lg p-4">
            <div class="flex items-center gap-2 mb-3">
                <h3 class="font-semibold me-auto">Reportes guardados</h3>
                <button id="close-presets" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700">Cerrar</button>
            </div>
            <div class="mb-3">
                <input id="preset-search" type="search"
                    class="w-full bg-slate-950 border border-slate-700 rounded px-2 py-1"
                    placeholder="Buscar por nombre…">
            </div>
            <div id="presets-list" class="space-y-2 max-h-80 overflow-y-auto"></div>
        </div>
    </div>


    <!-- JS libs -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <!-- Tu entorno -->
    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    <script src="js/reportes_dinamicos.js"></script>

    <!-- Helpers de interfaz (no tocan tu lógica) -->
    <script>
        // Toggle del panel de campos en móvil
        document.getElementById('toggle-campos')?.addEventListener('click', () => {
            const panel = document.getElementById('col-campos');
            panel.classList.toggle('hidden');
            panel.classList.toggle('block');
        });

        // Bridge de búsqueda a DataTables
        const bridge = document.getElementById('buscarTablaBridge');
        bridge?.addEventListener('input', () => {
            if ($.fn.dataTable.isDataTable('#dynamic-table')) {
                $('#dynamic-table').DataTable().search(bridge.value).draw();
            }
        });
    </script>
</body>

</html>