<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Sistema POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!--  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css"> -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css">
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

        .stock-adjust-input {
            width: 60px;
            text-align: center;
            -moz-appearance: textfield;
            background-color: var(--color-bg-primary);
            color: var(--color-text-primary);
            border: 1px solid var(--color-border);
        }

        .stock-adjust-input::-webkit-outer-spin-button,
        .stock-adjust-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #barcode-print-area,
            #barcode-print-area * {
                visibility: visible;
            }

            #barcode-print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
        }

        .dropzone .dz-preview .dz-image {
            /* Define el tamaño del contenedor de la imagen */
            width: 150px;
            height: 150px;
            border-radius: 8px;
            /* Opcional: para bordes redondeados */
            overflow: hidden;
            /* Oculta lo que se salga del borde */
        }

        .dropzone .dz-preview .dz-image img {
            /* Ajusta la imagen dentro del contenedor */
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* ¡Esta es la propiedad clave! */
        }

        /* Contenedor principal de cada archivo para posicionar el botón dentro */
        .dropzone .dz-preview {
            position: relative;
        }

        /* --- 1. ESTILOS PARA EL CONTENEDOR PRINCIPAL DE DROPZONE --- */
        .dropzone {
            border: 2px dashed #e0e0e0;
            border-radius: 12px;
            background: #fafafa;
            padding: 20px;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .dropzone:hover,
        .dropzone.dz-drag-hover {
            border-color: #007bff;
            /* Color azul al pasar el mouse o arrastrar */
            background: #f0f8ff;
        }

        /* --- 2. ESTILOS PARA CADA VISTA PREVIA (IMAGEN) --- */
        .dropzone .dz-preview {
            position: relative;
            /* Esencial para posicionar el botón de eliminar */
            margin: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            /* Oculta partes del botón que se salgan */
            transition: transform 0.2s ease;
        }

        .dropzone .dz-preview:hover {
            transform: translateY(-5px);
            /* Pequeño efecto de elevación al pasar el mouse */
        }

        /* Contenedor de la imagen para asegurar que ocupe todo el espacio */
        .dropzone .dz-preview .dz-image {
            width: 160px;
            height: 160px;
            border-radius: 12px;
        }

        /* La imagen en sí, para que no se deforme */
        .dropzone .dz-preview .dz-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- 3. EL BOTÓN DE ELIMINAR (LA 'X') - LA PARTE CLAVE ✨ --- */
        .dropzone .dz-preview .dz-remove {
            /* Apariencia del botón */
            background: rgba(220, 53, 69, 0.85);
            /* Rojo con transparencia */
            backdrop-filter: blur(3px);
            /* Efecto de desenfoque del fondo (opcional) */
            color: white;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            line-height: 28px;
            /* Ayuda a centrar verticalmente la 'X' */

            /* Forma y tamaño */
            width: 32px;
            height: 32px;
            border-radius: 50%;
            /* Círculo perfecto */
            border: 2px solid rgba(255, 255, 255, 0.8);

            /* Posicionamiento en la parte inferior central */
            position: absolute;
            bottom: 10px;
            /* Distancia desde abajo */
            left: 50%;
            transform: translateX(-50%);
            /* Truco para centrar horizontalmente */
            z-index: 20;

            /* Oculto por defecto, con transición suave */
            opacity: 0;
            transition: opacity 0.2s ease-in-out, transform 0.2s ease-in-out;
        }

        /* --- 4. EFECTOS HOVER --- */
        /* Mostrar el botón cuando el cursor está sobre la vista previa */
        .dropzone .dz-preview:hover .dz-remove {
            opacity: 1;
            /* Hacer visible */
            transform: translateX(-50%) scale(1.05);
            /* Centrar y hacer un poco más grande */
        }

        /* Opcional: Oscurecer la imagen al pasar el mouse para que la 'X' resalte más */
        .dropzone .dz-preview .dz-image:after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.5), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .dropzone .dz-preview:hover .dz-image:after {
            opacity: 1;
        }

        /* --- Centro códigos de barras y descripción --- */
        #barcode-svg {
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        #barcode-desc {
            text-align: center;
        }

        #print-barcode-btn {
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)]"
    data-user-role="<?php echo htmlspecialchars($_SESSION['rol'] ?? 'user'); ?>">

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

            <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto mb-8 md:justify-end">
                <button id="add-product-btn"
                    class="bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-2 px-4 rounded-lg flex items-center w-full md:w-auto justify-center">
                    <i class="fas fa-plus mr-2"></i> Añadir Producto
                </button>
                <button id="manage-categories-btn"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-4 rounded-lg flex items-center w-full md:w-auto justify-center">
                    <i class="fas fa-tags mr-2"></i> Gestionar Categorías
                </button>
                <button id="manage-brands-btn"
                    class="bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 px-4 rounded-lg flex items-center w-full md:w-auto justify-center">
                    <i class="fas fa-copyright mr-2"></i> Gestionar Marcas
                </button>
            </div>

            <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow overflow-auto mb-8 p-4">
                <h3 class="text-2xl font-bold text-[var(--color-text-primary)] mb-8">
                    <i class="fas fa-box mr-3 text-blue-400"></i> Gestión de Inventario
                </h3>

                <table id="productsTable" class="min-w-full">
                    <thead class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase">
                        <tr>
                            <th class="py-3 px-6 text-left">SKU</th>
                            <th class="py-3 px-6 text-left">Nombre</th>
                            <th class="py-3 px-6 text-left">Códigos de Barras</th>
                            <th class="py-3 px-6 text-left">Categoría</th>
                            <th class="py-3 px-6 text-center">Stock</th>
                            <th class="py-3 px-6 text-right">Precio Menudeo</th>
                            <th class="py-3 px-6 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs"></tbody>
                </table>
            </div>

            <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow overflow-hidden p-6">
                <h3 class="text-2xl font-bold text-[var(--color-text-primary)] mb-8">
                    <i class="fas fa-history mr-3 text-blue-400"></i> Historial de Movimientos
                </h3>

                <table class="min-w-full" id="historyTable">
                    <thead class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase">
                        <tr>
                            <th class="py-3 px-6 text-left">Fecha</th>
                            <th class="py-3 px-6 text-left">Producto</th>
                            <th class="py-3 px-6 text-left">Tipo</th>
                            <th class="py-3 px-6 text-center">Cantidad</th>
                            <th class="py-3 px-6 text-center">Stock Anterior</th>
                            <th class="py-3 px-6 text-center">Stock Nuevo</th>
                            <th class="py-3 px-6 text-left">Motivo / Ref.</th>
                            <th class="py-3 px-6 text-left">Usuario</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs"></tbody>
                </table>

            </div>
        </main>
    </div>

    <!-- Modal de producto -->
    <div id="product-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-2xl transform transition-all duration-300 ease-in-out">
            <div class="px-6 py-4 border-b border-[var(--color-border)] flex justify-between items-center">
                <h2 id="modal-title" class="text-xl font-bold text-[var(--color-text-primary)] flex items-center"><i
                        class="fas fa-box-open mr-3"></i>Añadir Nuevo Producto</h2>
                <button id="close-modal-btn"
                    class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl leading-none">&times;</button>
            </div>

            <form id="product-form">
                <input type="hidden" id="product-id" name="id">

                <div id="clone-section"
                    class="px-6 pt-4 pb-2 bg-[var(--color-bg-primary)] border-b border-[var(--color-border)]">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[var(--color-text-secondary)]">¿Crear a partir de un
                            producto existente?</span>
                        <button type="button" id="toggle-clone-btn"
                            class="text-sm text-[var(--color-accent)] hover:underline">Clonar producto</button>
                    </div>
                    <div id="clone-controls" class="hidden mt-3">
                        <label for="clone-source-product"
                            class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Selecciona un
                            producto para clonar</label>
                        <div class="flex gap-2">
                            <select id="clone-source-product"
                                class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
                            <button type="button" id="load-clone-data-btn"
                                class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-4 rounded-lg flex-shrink-0">Cargar
                                Datos</button>
                        </div>
                    </div>
                </div>

                <div class="p-6 modal-body overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div class="md:col-span-2">
                            <label for="nombre"
                                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Nombre del
                                Producto</label>
                            <input type="text" id="nombre" name="nombre"
                                class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                                required>
                        </div>

                        <div>
                            <label for="sku"
                                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">SKU / Código
                                Interno</label>
                            <input type="text" id="sku" name="sku"
                                class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                                required>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-[var(--color-text-secondary)] mb-2">Códigos de
                                Barras</label>
                            <div id="barcodes-container"></div>
                            <button type="button" id="add-barcode-btn"
                                class="mt-2 bg-blue-600 hover:bg-blue-500 text-white font-bold py-1 px-3 rounded-lg text-sm flex items-center"><i
                                    class="fas fa-plus mr-2"></i>Añadir Código</button>
                        </div>

                        <div>
                            <label for="id_categoria"
                                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Categoría</label>
                            <select id="id_categoria" name="id_categoria"
                                class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
                        </div>

                        <div>
                            <label for="id_marca"
                                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Marca</label>
                            <select id="id_marca" name="id_marca"
                                class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
                        </div>
                    </div>

                    <hr class="border-[var(--color-border)] my-6">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                        <!-- PRECIOS -->
                        <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4">
                            <div>
                                <label for="costo"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Costo</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3 pr-2 flex items-center text-[var(--color-text-secondary)]">$</span>
                                    <input type="text" id="costo" name="costo" placeholder="0.00"
                                        class="w-full bg-[var(--color-bg-primary)] text-white border border-[var(--color-border)] rounded-md pl-8 p-2 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                                        required>
                                </div>
                            </div>

                            <div>
                                <label for="precio_menudeo"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Precio
                                    Menudeo</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3 pr-2 flex items-center text-[var(--color-text-secondary)]">$</span>
                                    <input type="text" id="precio_menudeo" name="precio_menudeo" placeholder="0.00"
                                        class="w-full bg-[var(--color-bg-primary)] text-white border border-[var(--color-border)] rounded-md pl-8 p-2 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                                        required>
                                </div>
                            </div>

                            <div>
                                <label for="precio_mayoreo"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Precio
                                    Mayoreo</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3 pr-2 flex items-center text-[var(--color-text-secondary)]">$</span>
                                    <input type="text" id="precio_mayoreo" name="precio_mayoreo" placeholder="0.00"
                                        class="w-full bg-[var(--color-bg-primary)] text-white border border-[var(--color-border)] rounded-md pl-8 p-2 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                                        required>
                                </div>
                            </div>
                        </div>

                        <!-- PRECIOS NIVELADOS P1…P5 -->
                        <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-5 gap-x-6 gap-y-4">
                            <div>
                                <label for="precio_1"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Precio
                                    1</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3 pr-2 flex items-center text-[var(--color-text-secondary)]">$</span>
                                    <input type="text" id="precio_1" name="precio_1" placeholder="0.00"
                                        class="w-full bg-[var(--color-bg-primary)] text-white border border-[var(--color-border)] rounded-md pl-8 p-2 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                                </div>
                            </div>
                            <div>
                                <label for="precio_2"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Precio
                                    2</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3 pr-2 flex items-center text-[var(--color-text-secondary)]">$</span>
                                    <input type="text" id="precio_2" name="precio_2" placeholder="0.00"
                                        class="w-full bg-[var(--color-bg-primary)] text-white border border-[var(--color-border)] rounded-md pl-8 p-2 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                                </div>
                            </div>
                            <div>
                                <label for="precio_3"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Precio
                                    3</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3 pr-2 flex items-center text-[var(--color-text-secondary)]">$</span>
                                    <input type="text" id="precio_3" name="precio_3" placeholder="0.00"
                                        class="w-full bg-[var(--color-bg-primary)] text-white border border-[var(--color-border)] rounded-md pl-8 p-2 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                                </div>
                            </div>
                            <div>
                                <label for="precio_4"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Precio
                                    4</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3 pr-2 flex items-center text-[var(--color-text-secondary)]">$</span>
                                    <input type="text" id="precio_4" name="precio_4" placeholder="0.00"
                                        class="w-full bg-[var(--color-bg-primary)] text-white border border-[var(--color-border)] rounded-md pl-8 p-2 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                                </div>
                            </div>
                            <div>
                                <label for="precio_5"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Precio
                                    5</label>
                                <div class="relative">
                                    <span
                                        class="absolute inset-y-0 left-0 pl-3 pr-2 flex items-center text-[var(--color-text-secondary)]">$</span>
                                    <input type="text" id="precio_5" name="precio_5" placeholder="0.00"
                                        class="w-full bg-[var(--color-bg-primary)] text-white border border-[var(--color-border)] rounded-md pl-8 p-2 focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 mt-4">
                            <div>
                                <label for="stock"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Stock
                                    Inicial</label>
                                <input type="number" id="stock" name="stock" value="0" min="0"
                                    class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                            </div>
                            <div>
                                <label for="stock_minimo"
                                    class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Stock
                                    Mínimo</label>
                                <input type="number" id="stock_minimo" name="stock_minimo" value="5" min="0"
                                    class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                            </div>
                        </div>
                    </div>

                    <hr class="border-[var(--color-border)] my-6">
                    <div>
                        <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">Imágenes del Producto
                        </h3>
                        <div id="product-dropzone"
                            class="dropzone border-2 border-dashed rounded-lg p-4 bg-[var(--color-bg-secondary)]"
                            data-dz-no-auto="true"></div>
                    </div>
                    <hr class="border-[var(--color-border)] my-6">

                    <div>
                        <label for="descripcion"
                            class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Descripción
                            (Opcional)</label>
                        <textarea id="descripcion" name="descripcion" rows="3"
                            class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-[var(--color-bg-primary)] rounded-b-lg flex justify-end items-center gap-4">
                    <button type="button" id="cancel-btn"
                        class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg transition-colors duration-200">Cancelar</button>
                    <button type="submit"
                        class="bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200">
                        <i class="fas fa-save"></i> Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Otros Modales -->
    <div id="adjust-stock-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-md transform transition-all duration-300 ease-in-out">
            <div class="p-6 border-b border-[var(--color-border)] flex justify-between items-center">
                <h2 id="adjust-modal-title" class="text-2xl font-bold text-[var(--color-text-primary)]">Ajustar Stock
                </h2>
                <button id="close-adjust-modal-btn"
                    class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)]">&times;</button>
            </div>
            <div class="p-6">
                <input type="hidden" id="adjust-product-id">
                <input type="hidden" id="adjust-action">
                <input type="hidden" id="adjust-current-stock-value">
                <div class="mb-4">
                    <p class="text-[var(--color-text-secondary)]">Producto: <span id="adjust-product-name"
                            class="font-bold text-[var(--color-text-primary)]"></span></p>
                    <p class="text-[var(--color-text-secondary)]">Stock Actual: <span id="adjust-current-stock-display"
                            class="font-bold text-[var(--color-text-primary)]"></span></p>
                </div>

                <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'Super'): ?>
                    <div id="branch-selector-container" class="mb-4">
                        <label for="adjust-branch-select"
                            class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Abastecer a
                            Sucursal</label>
                        <select id="adjust-branch-select"
                            class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <label id="adjust-quantity-label" for="adjust-quantity"
                        class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Cantidad</label>
                    <input type="number" id="adjust-quantity"
                        class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                        placeholder="0" min="1">
                </div>
                <div class="mb-4">
                    <label for="adjust-stock-reason"
                        class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Motivo del
                        Ajuste</label>
                    <textarea id="adjust-stock-reason" rows="3"
                        class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
                        required></textarea>
                </div>
            </div>
            <div class="p-6 border-t border-[var(--color-border)] flex justify-end">
                <button type="button" id="cancel-adjust-btn"
                    class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg mr-2">Cancelar</button>
                <button type="button" id="confirm-adjust-btn"
                    class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg">Confirmar</button>
            </div>
        </div>
    </div>

    <div id="barcode-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-lg transform transition-all duration-300 ease-in-out">
            <div class="px-6 py-4 border-b border-[var(--color-border)] flex justify-between items-center">
                <h2 class="text-xl font-bold text-[var(--color-text-primary)] flex items-center"><i
                        class="fas fa-barcode mr-3"></i>Generar Código de Barras</h2>
                <button id="close-barcode-modal-btn"
                    class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <p class="text-[var(--color-text-secondary)]">Producto: <span id="barcode-product-name"
                            class="font-bold text-[var(--color-text-primary)]"></span></p>
                </div>
                <div class="mb-4">
                    <label for="barcode-data-select"
                        class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Selecciona el código a
                        generar</label>
                    <select id="barcode-data-select"
                        class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
                </div>
                <div class="mb-4">
                    <label for="barcode-format-select"
                        class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Formato del
                        Código</label>
                    <select id="barcode-format-select"
                        class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                        <option value="CODE128" selected>CODE 128 (Recomendado)</option>
                        <option value="EAN13">EAN-13</option>
                    </select>
                </div>
                <div class="flex justify-center my-4">
                    <button id="generate-barcode-btn"
                        class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-6 rounded-lg">Generar</button>
                </div>
                <div id="barcode-feedback" class="text-center text-sm text-yellow-400 my-2 h-4"></div>
                <!-- <div id="barcode-display-container"
                    class="bg-white p-4 rounded-lg min-h-[120px] flex-column text-cente items-center justify-center">
                    <svg id="barcode-svg"></svg>
                    <div id="barcode-desc" class="text-center text-gray-700 text-sm mt-2"></div>
                </div> -->

                <div class="bg-white rounded-lg p-4 shadow border w-full max-w-xl text-center">
                    <svg id="barcode-svg" class="mx-auto block"></svg>
                    <div id="barcode-desc" class="text-center text-gray-700 text-sm mt-2"></div>
                </div>
            </div>
            <div class="px-6 py-4 bg-[var(--color-bg-primary)] rounded-b-lg flex justify-end items-center gap-4">
                <button type="button" id="print-barcode-btn"
                    class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200 disabled:bg-gray-500"
                    disabled>
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>
    </div>

    <div id="category-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-2xl transform transition-all duration-300 ease-in-out">
            <div class="px-6 py-4 border-b border-[var(--color-border)] flex justify-between items-center">
                <h2 id="category-modal-title"
                    class="text-xl font-bold text-[var(--color-text-primary)] flex items-center"><i
                        class="fas fa-tags mr-3"></i>Gestionar Categorías</h2>
                <button id="close-category-modal-btn"
                    class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 modal-body overflow-y-auto">
                <form id="category-form"
                    class="mb-6 p-4 border border-[var(--color-border)] rounded-lg bg-[var(--color-bg-primary)]">
                    <input type="hidden" id="category-id" name="id">
                    <div class="mb-4">
                        <label for="category-name"
                            class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Nombre de la
                            Categoría</label>
                        <input type="text" id="category-name" name="nombre"
                            class="w-full bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="category-description"
                            class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Descripción
                            (Opcional)</label>
                        <textarea id="category-description" name="descripcion" rows="2"
                            class="w-full bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancel-category-edit-btn"
                            class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg transition-colors duration-200 hidden">Cancelar
                            Edición</button>
                        <button type="submit" id="save-category-btn"
                            class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200">
                            <i class="fas fa-plus-circle"></i> Añadir Categoría
                        </button>
                    </div>
                </form>
                <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-3 flex items-center"><i
                        class="fas fa-list mr-2"></i>Categorías Existentes</h3>
                <div class="max-h-[30vh] overflow-y-auto overflow-x-auto rounded-lg">
                    <table class="min-w-full">
                        <thead
                            class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase sticky top-0">
                            <tr>
                                <th class="py-3 px-6 text-left">Nombre</th>
                                <th class="py-3 px-6 text-left">Descripción</th>
                                <th class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="categories-table-body" class="divide-y divide-[var(--color-border)]"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="brand-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
        <div
            class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-xl transform transition-all duration-300 ease-in-out">
            <div class="px-6 py-4 border-b border-[var(--color-border)] flex justify-between items-center">
                <h2 id="brand-modal-title" class="text-xl font-bold text-[var(--color-text-primary)] flex items-center">
                    <i class="fas fa-copyright mr-3"></i>Gestionar Marcas
                </h2>
                <button id="close-brand-modal-btn"
                    class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6 modal-body overflow-y-auto">
                <form id="brand-form"
                    class="mb-6 p-4 border border-[var(--color-border)] rounded-lg bg-[var(--color-bg-primary)]">
                    <input type="hidden" id="brand-id" name="id">
                    <div class="mb-4">
                        <label for="brand-name"
                            class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Nombre de la
                            Marca</label>
                        <input type="text" id="brand-name" name="nombre"
                            class="w-full bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-purple-500 focus:border-purple-500"
                            required>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="cancel-brand-edit-btn"
                            class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg transition-colors duration-200 hidden">Cancelar
                            Edición</button>
                        <button type="submit" id="save-brand-btn"
                            class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200">
                            <i class="fas fa-plus-circle"></i> Añadir Marca
                        </button>
                    </div>
                </form>
                <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-3 flex items-center"><i
                        class="fas fa-list mr-2"></i>Marcas Existentes</h3>
                <div class="max-h-[30vh] overflow-y-auto overflow-x-auto rounded-lg">
                    <table class="min-w-full">
                        <thead
                            class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase sticky top-0">
                            <tr>
                                <th class="py-3 px-6 text-left">Nombre</th>
                                <th class="py-3 px-6 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="brands-table-body" class="divide-y divide-[var(--color-border)]"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="barcode-print-area" class="hidden"></div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!--   <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script> -->
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
    <script>try { Dropzone.autoDiscover = false; } catch (e) { }</script>


    <script src="js/rutas.js"></script>
    <script src="js/toast.js"></script>
    <script src="js/confirm.js"></script>
    <script src="js/inventario.js"></script>
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