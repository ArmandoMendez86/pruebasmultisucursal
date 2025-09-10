<?php
// Archivo: /public/views/pos.php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Punto de Venta - Sistema POS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");

    body {
      font-family: "Inter", sans-serif;
    }

    .text-xxs {
      font-size: 0.65rem;
      line-height: 0.8rem;
    }

    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: var(--color-bg-secondary);
    }

    ::-webkit-scrollbar-thumb {
      background: var(--color-border);
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--color-text-secondary);
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, auto));
      gap: 0.75rem;
      padding: 0.25rem;
      overflow-y: auto;
      flex: 1;
      align-content: start;
      align-items: start;
    }

    .product-card {
      background-color: var(--color-bg-secondary);
      border: 1px solid var(--color-border);
    }

    .product-card:hover {
      background-color: var(--color-bg-primary);
    }

    .product-card.out-of-stock {
      background-color: var(--color-bg-primary);
    }

    .product-card.out-of-stock:hover {
      background-color: var(--color-bg-primary);
    }

    .product-card-stock.zero-stock {
      color: #ef4444;
    }

    .product-card-image {
      border: 1px solid var(--color-border);
    }

    .product-card-name {
      color: var(--color-text-primary);
    }

    .product-card-stock {
      color: var(--color-text-secondary);
    }

    .cart-item {
      border-bottom: 1px solid var(--color-border);
      background-color: var(--color-bg-secondary);
    }

    .cart-item:hover {
      background-color: var(--color-bg-primary);
    }

    .cart-item-image {
      border: 1px solid var(--color-border);
    }

    .quantity-controls {
      background-color: var(--color-bg-primary);
    }

    .quantity-controls button {
      background-color: var(--color-bg-secondary);
      color: var(--color-text-primary);
    }

    .quantity-controls button:hover {
      background-color: var(--color-border);
    }

    .quantity-controls input {
      background-color: var(--color-bg-secondary);
      color: var(--color-text-primary);
    }

    .modal-overlay {
      background-color: rgba(0, 0, 0, 0.75);
    }

    .price-type-btn {
      color: var(--color-text-secondary);
    }

    .price-type-btn.active-price-type {
      background-color: var(--color-accent);
      color: white;
    }

    .price-type-btn:not(.active-price-type):hover {
      background-color: var(--color-border);
    }

    .select2-container--default .select2-selection--single {
      background-color: var(--color-bg-secondary) !important;
      border: 1px solid var(--color-border) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
      color: var(--color-text-primary) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow b {
      border-color: var(--color-text-secondary) transparent transparent transparent !important;
    }

    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
      border-color: transparent transparent var(--color-text-secondary) transparent !important;
    }

    .select2-dropdown {
      background-color: var(--color-bg-secondary) !important;
      border: 1px solid var(--color-border) !important;
    }

    .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
      background-color: var(--color-accent) !important;
      color: white !important;
    }

    .select2-container--default .select2-results__option--selectable {
      color: var(--color-text-primary) !important;
    }

    .select2-search--dropdown .select2-search__field {
      background-color: var(--color-bg-primary) !important;
      border: 1px solid var(--color-border) !important;
      color: var(--color-text-primary) !important;
    }

    .select2-results__message {
      color: var(--color-text-secondary) !important;
    }

    #toggle-negative-stock:checked+.block {
      background-color: #22c55e;
    }

    #toggle-negative-stock:checked~.dot,
    #toggle-negative-stock:checked~div>.dot {
      transform: translateX(1rem);
    }

    /* Glow rojo cuando se excede el límite */
    @keyframes pulseGlow {
      from {
        box-shadow: 0 0 6px rgba(244, 63, 94, .35);
      }

      to {
        box-shadow: 0 0 18px rgba(244, 63, 94, .85);
      }
    }

    .over-limit .card-shell {
      animation: pulseGlow .9s ease-in-out infinite alternate;
    }

    /* Solo si no lo tienes ya */
    @keyframes pulseGlow {
      from {
        box-shadow: 0 0 6px rgba(244, 63, 94, .35);
      }

      to {
        box-shadow: 0 0 18px rgba(244, 63, 94, .85);
      }
    }

    .over-limit .card-shell {
      animation: pulseGlow .9s ease-in-out infinite alternate;
    }

    .bar-striped {
      background-image: linear-gradient(45deg, rgba(255, 255, 255, .35) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, .35) 50%, rgba(255, 255, 255, .35) 75%, transparent 75%, transparent);
      background-size: 1rem 1rem;
    }

    @keyframes moveStripes {
      from {
        background-position: 0 0;
      }

      to {
        background-position: 2rem 0;
      }
    }

    .bar-animated {
      animation: moveStripes .8s linear infinite;
    }

    /* Compacta verticalmente la sección de totales sin tocar el HTML interno */
    .totals-dense {
      /* padding externo pequeño */
      padding-top: .5rem !important;
      padding-bottom: .5rem !important;
    }

    /* Quita márgenes verticales entre elementos de primer nivel */
    .totals-dense>* {
      margin-top: .25rem !important;
      margin-bottom: .25rem !important;
    }

    /* Filas típicas (labels/valores) más pegadas */
    .totals-dense .flex,
    .totals-dense .grid,
    .totals-dense .row {
      padding-top: .25rem !important;
      /* py-1 */
      padding-bottom: .25rem !important;
      margin-top: 0 !important;
      margin-bottom: 0 !important;
      line-height: 1.1 !important;
      /* leading-tight */
    }

    /* Checkbox IVA más compacto */
    .totals-dense label,
    .totals-dense .form-check,
    .totals-dense .iva-row {
      padding-top: .25rem !important;
      padding-bottom: .25rem !important;
      margin: 0 !important;
      line-height: 1.1 !important;
    }

    /* Tipografías: etiquetas pequeñas, totales destacados pero sin ocupar de más */
    .totals-dense .label,
    .totals-dense .text-muted,
    .totals-dense .subtitle {
      font-size: .78rem !important;
      /* ~text-xs */
      opacity: .85;
    }

    .totals-dense .amount,
    .totals-dense .money,
    .totals-dense [data-money] {
      font-size: .95rem !important;
      /* ~text-sm */
      line-height: 1.1 !important;
    }

    .totals-dense .amount-total,
    .totals-dense .money-total,
    .totals-dense [data-total] {
      font-size: 1.25rem !important;
      /* ~text-xl compacto */
      line-height: 1.15 !important;
      margin-top: .1rem !important;
    }

    /* Si tienes un divisor entre Subtotal y Total, que sea mínimo */
    .totals-dense .divider {
      margin: .25rem 0 !important;
      border-color: rgba(255, 255, 255, .08) !important;
    }
  </style>
</head>

<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] flex h-screen">

  <?php include_once '../parciales/navegacion.php'; ?>

  <main class="flex-1 flex flex-col overflow-hidden">

    <header
      class="lg:hidden flex items-center justify-between bg-[var(--color-bg-secondary)] p-4 shadow-md flex-shrink-0">
      <button id="mobile-menu-button" class="text-[var(--color-text-primary)] focus:outline-none">
        <i class="fas fa-bars text-2xl"></i>
      </button>
      <h1 class="text-lg font-bold text-[var(--color-text-primary)]">Punto de Venta</h1>
      <div class="w-8"></div>
    </header>

    <div class="flex justify-between border-b border-[var(--color-border)]">
      <div class="px-4 py-2">
        <div class="flex items-center gap-3">
          <label class="text-sm text-[var(--color-text-secondary)]">Impresión</label>
          <div
            class="flex items-center bg-[var(--color-bg-primary)] border border-[var(--color-border)] rounded-lg overflow-hidden">
            <button id="pm-service" class="px-3 py-1 text-sm bg-[var(--color-accent)] text-white">Servicio</button>
            <button id="pm-qztray" class="px-3 py-1 text-sm text-[var(--color-text-primary)]">QZ Tray</button>
          </div>
          <button id="btn-qz-connect"
            class="hidden px-3 py-1 text-sm bg-emerald-600 hover:bg-emerald-500 text-white rounded">
            Conectar QZ
          </button>
          <span id="qz-status" class="text-xs text-[var(--color-text-secondary)]">QZ: <span>Desconectado</span></span>
        </div>
      </div>

      <div class="flex items-center space-x-2 pr-3 p-3">
        <span class="text-sm font-medium text-yellow-400">Vender sin Stock</span>
        <label for="toggle-negative-stock" class="flex items-center cursor-pointer">
          <div class="relative">
            <input type="checkbox" id="toggle-negative-stock" class="sr-only">
            <div class="block bg-[var(--color-border)] w-10 h-6 rounded-full"></div>
            <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition"></div>
          </div>
        </label>
      </div>
    </div>

    <div class="flex-1 flex lg:flex-row flex-col overflow-y-auto">

      <div class="lg:w-2/5 w-full flex flex-col p-4">
        <div class="mb-4 flex gap-2">
          <input type="text" id="search-product" placeholder="Buscar producto en esta sucursal..."
            class="w-full bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] rounded-md p-3 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" />
          <button id="openCashModalBtn" title="Abrir Caja"
            class="flex-shrink-0 bg-blue-600 hover:bg-blue-500 text-white font-bold p-3 rounded-md text-lg flex items-center justify-center">
            <i class="fas fa-cash-register"></i>
          </button>
          <button id="open-stock-checker-btn" title="Buscar stock en todas las sucursales"
            class="flex-shrink-0 bg-teal-600 hover:bg-teal-500 text-white font-bold p-3 rounded-md text-lg flex items-center justify-center">
            <i class="fas fa-globe"></i>
          </button>
        </div>
        <div id="product-list" class="product-grid"></div>
      </div>

      <div class="lg:w-3/5 w-full bg-[var(--color-bg-secondary)] flex flex-col p-2 shadow-lg">
        <div class="mb-2">
          <input type="text" id="search-cart-item" placeholder="Buscar artículo en el carrito..."
            class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" />
        </div>

        <div id="cart-items" class="flex-1 overflow-y-auto border-t border-b border-[var(--color-border)] py-2">
          <!-- El contenido del carrito se genera con JS -->
        </div>

        <div class="pos-form-dense rounded-lg bg-slate-800/40 md:p-3">
          <div class="flex flex-col sm:flex-row gap-3 items-center sm:items-end">
            <div class="flex-grow w-full">
              <label for="search-client"
                class="block text-xs font-medium mb-1 text-[var(--color-text-secondary)]">Cliente</label>
              <div class="flex gap-2">
                <select id="search-client" class="w-full text-xs">
                  <option value="1" selected>Público en General</option>
                </select>
                <button id="add-new-client-btn" title="Añadir nuevo cliente"
                  class="flex-shrink-0 bg-blue-600 hover:bg-blue-500 text-white font-bold p-2 rounded-md h-[38px] w-[38px] text-xs flex items-center justify-center">
                  <i class="fas fa-user-plus"></i>
                </button>
              </div>
            </div>

            <div class="flex-shrink-0">
              <label class="block text-xs text-center font-medium mb-1 text-[var(--color-text-secondary)]">Tipo de
                Precio</label>
              <div id="price-type-selector"
                class="flex items-center bg-[var(--color-bg-primary)] rounded-lg p-1 border border-[var(--color-border)]">
                <button data-level="1"
                  class="price-type-btn active-price-type px-3 py-2 text-xs font-semibold rounded-md">P1</button>
                <button data-level="2" class="price-type-btn px-3 py-2 text-xs font-semibold rounded-md">P2</button>
                <button data-level="3" class="price-type-btn px-3 py-2 text-xs font-semibold rounded-md">P3</button>
                <button data-level="4" class="price-type-btn px-3 py-2 text-xs font-semibold rounded-md">P4</button>
                <button data-level="5" class="price-type-btn px-3 py-2 text-xs font-semibold rounded-md">P5</button>
              </div>
              <input type="hidden" id="price-type-value" value="1">
            </div>
          </div>

          <div id="address-selection-container" class="hidden">
            <label for="client-address-select" class="block text-xs font-medium mb-1">Dirección de Envío</label>
            <select id="client-address-select"
              class="w-full text-xs bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]"></select>
          </div>

        </div>

        <!-- Fila: Ventas Pendientes (izq) + Crédito (der flotante que no ocupa altura en md+) -->
        <div class="m-4 relative">
          <!-- IZQUIERDA: pega aquí tu botón/enlace EXISTENTE (no cambies sus IDs/clases) -->
          <div class="flex items-center gap-2 md:pr-[380px]">
            <button id="open-pending-sales-btn"
              class="text-sm text-blue-400 hover:text-blue-300 font-semibold py-2 px-3 bg-[var(--color-bg-primary)]/[0.5] hover:bg-[var(--color-bg-primary)] rounded-lg flex items-center justify-start gap-2 mt-0">
              <i class="fas fa-folder-open"></i>
              <span>Ver Ventas Pendientes</span>
            </button>
          </div>

          <!-- DERECHA: CRÉDITO (mismo diseño, flotante en md+) -->
          <div id="credit-widget" class="hidden w-full max-w-full shrink-0
                    md:absolute md:right-0 md:top-1/2 md:-translate-y-1/2 md:w-[280px]">
            <div
              class="card-shell rounded-xl shadow-md ring-1 ring-white/10 bg-gradient-to-br from-slate-800 to-slate-900 text-white">
              <div class="p-1.5 text-[10px] leading-tight">
                <div class="flex items-center gap-1">
                  <span id="credit-badge"
                    class="inline-flex items-center gap-1 rounded-full bg-amber-400/90 text-slate-900 px-1.5 py-0.5 text-[9px] font-semibold whitespace-nowrap">
                    <svg viewBox="0 0 24 24" aria-hidden="true" class="w-3 h-3">
                      <path fill="currentColor"
                        d="M2 6.5A2.5 2.5 0 0 1 4.5 4h15A2.5 2.5 0 0 1 22 6.5v11A2.5 2.5 0 0 1 19.5 20h-15A2.5 2.5 0 0 1 2 17.5v-11Zm2.5-.5a.5.5 0 0 0-.5.5V8h18V6.5a.5.5 0 0 0-.5-.5h-17Zm17 6H4v5.5a.5.5 0 0 0 .5.5h15a.5.5 0 0 0 .5-.5V12Z" />
                    </svg>
                    Activo
                  </span>
                  <h6 class="font-bold whitespace-nowrap">Crédito</h6>
                  <div id="credit-customer-name" class="ml-auto text-[9px] opacity-75 truncate max-w-[100px]"></div>
                </div>

                <div class="grid grid-cols-12 gap-1 mt-1.5">
                  <div class="col-span-12 sm:col-span-4">
                    <div class="p-1 rounded-md bg-rose-500/10">
                      <div class="text-[8px] opacity-75">Deuda</div>
                      <div id="credit-debt" class="m-0 font-bold text-sm whitespace-nowrap">$0.00</div>
                    </div>
                  </div>

                  <div class="col-span-6 sm:col-span-4">
                    <div class="p-1 rounded-md bg-slate-500/15">
                      <div class="text-[8px] opacity-75">Límite</div>
                      <div id="credit-limit" class="m-0 font-semibold text-xs whitespace-nowrap">$0.00</div>
                    </div>
                  </div>

                  <div class="col-span-6 sm:col-span-4">
                    <div class="p-1 rounded-md bg-emerald-500/10">
                      <div class="text-[8px] opacity-75">Disp.</div>
                      <div id="credit-available" class="m-0 font-semibold text-xs whitespace-nowrap">$0.00</div>
                    </div>
                  </div>
                </div>

                <div class="mt-1.5">
                  <div class="h-1 w-full rounded-full bg-white/10 overflow-hidden">
                    <div id="credit-progress" class="h-1 w-0 rounded-full bg-emerald-500 transition-all duration-300">
                    </div>
                  </div>
                  <div id="credit-projected" class="hidden text-[9px] mt-1">
                    Proyectado:
                    <span id="credit-projected-amount" class="font-bold">$0.00</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="totals-dense rounded-lg bg-slate-800/60 p-2 md:p-3 leading-tight">
          <div class="flex items-center justify-between text-sm">
            <label for="toggle-iva" class="font-medium text-[var(--color-text-primary)] cursor-pointer">
              <input type="checkbox" id="toggle-iva"
                class="mr-2 h-4 w-4 text-[var(--color-accent)] focus:ring-[var(--color-accent)] rounded border-[var(--color-border)] bg-[var(--color-bg-secondary)]" />
              Aplicar IVA (16%)
            </label>
            <span id="cart-tax">$0.00</span>
          </div>
          <div class="flex justify-between text-sm">
            <span>Subtotal</span><span id="cart-subtotal">$0.00</span>
          </div>
          <div class="flex justify-between text-lg font-bold text-[var(--color-text-primary)]">
            <span>Total</span><span id="cart-total">$0.00</span>
          </div>
        </div>

        <div class="grid grid-cols-3 gap-5 pt-1 border-t border-[var(--color-border)]">
          <button id="cancel-sale-btn" class="bg-red-800 hover:bg-red-500 text-white font-bold py-1 rounded-lg">
            Cancelar
          </button>
          <button id="save-sale-btn"
            class="bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-1 rounded-lg disabled:bg-gray-500 disabled:cursor-not-allowed"
            disabled>
            Guardar
          </button>
          <button id="charge-btn" class="bg-green-800 hover:bg-green-500 text-white font-bold py-1 rounded-lg">
            Cobrar
          </button>
        </div>

      </div>
  </main>

  <!-- Modales -->
  <div id="charge-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-md">
      <div class="p-6 border-b border-[var(--color-border)]">
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Procesar Venta</h2>
      </div>
      <div class="p-6">
        <div class="text-center mb-6">
          <p class="text-[var(--color-text-secondary)] text-lg">Total a Pagar</p>
          <p id="modal-total" class="text-5xl font-bold text-green-400">
            $0.00
          </p>
        </div>
        <div id="payment-methods-container" class="space-y-4 mb-4"></div>
        <button id="add-payment-method-btn"
          class="w-full bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-2 rounded-md mb-4">
          <i class="fas fa-plus mr-2"></i> Añadir Método de Pago
        </button>
        <div class="space-y-2 text-lg">
          <div class="flex justify-between text-[var(--color-text-primary)]">
            <span>Monto Pagado:</span>
            <span id="modal-amount-paid">$0.00</span>
          </div>
          <div class="flex justify-between font-bold" id="modal-change-row">
            <span>Cambio:</span>
            <span id="modal-change">$0.00</span>
          </div>
          <div class="flex justify-between font-bold text-red-400" id="modal-pending-row">
            <span>Pendiente:</span>
            <span id="modal-pending">$0.00</span>
          </div>
        </div>
      </div>
      <div class="p-6 bg-[var(--color-bg-primary)] flex justify-end space-x-4 rounded-b-lg">
        <button type="button" id="modal-cancel-btn"
          class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg">
          Cancelar
        </button>
        <button type="button" id="modal-confirm-btn"
          class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-6 rounded-lg">
          Confirmar Venta
        </button>
      </div>
    </div>
  </div>
  <div id="add-client-modal" class="fixed inset-0 z-[120] flex items-center justify-center modal-overlay hidden"
    role="dialog" aria-modal="true">
    <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-lg">
      <div class="p-6 border-b border-[var(--color-border)] flex justify-between items-center">
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Añadir Nuevo Cliente</h2>
        <button id="close-add-client-modal-btn"
          class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl">&times;</button>
      </div>
      <div class="p-6">
        <form id="add-client-form" class="space-y-4">
          <div>
            <label for="client-name" class="block text-sm font-medium text-[var(--color-text-secondary)]">Nombre del
              Cliente <span class="text-red-500">*</span></label>
            <input type="text" id="client-name" name="nombre" required
              class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
          </div>
          <div>
            <label for="client-rfc" class="block text-sm font-medium text-[var(--color-text-secondary)]">RFC</label>
            <input type="text" id="client-rfc" name="rfc"
              class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
          </div>
          <div>
            <label for="client-phone"
              class="block text-sm font-medium text-[var(--color-text-secondary)]">Teléfono</label>
            <input type="tel" id="client-phone" name="telefono"
              class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
          </div>
          <div>
            <label for="client-email" class="block text-sm font-medium text-[var(--color-text-secondary)]">Email</label>
            <input type="email" id="client-email" name="email"
              class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
          </div>
          <div class="flex items-center">
            <input type="checkbox" id="client-has-credit" name="tiene_credito" value="1"
              class="h-4 w-4 text-[var(--color-accent)] focus:ring-[var(--color-accent)] border-[var(--color-border)] rounded bg-[var(--color-bg-primary)]">
            <label for="client-has-credit" class="ml-2 block text-sm text-[var(--color-text-secondary)]">Tiene
              Crédito</label>
          </div>
          <div id="credit-limit-container" class="hidden">
            <label for="client-credit-limit" class="block text-sm font-medium text-[var(--color-text-secondary)]">Límite
              de Crédito</label>
            <input type="number" step="0.01" id="client-credit-limit" name="limite_credito" value="0.00"
              class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
          </div>
          <div class="flex justify-end space-x-4 pt-4">
            <button type="button" id="cancel-add-client-btn"
              class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg">
              Cancelar
            </button>
            <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-6 rounded-lg">
              Guardar Cliente
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal: Seleccionar cliente para duplicar -->
  <div id="duplicate-client-modal" class="fixed inset-0 z-[100] flex items-center justify-center modal-overlay hidden">
    <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-lg">
      <div class="p-6 border-b border-[var(--color-border)]">
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Duplicar venta</h2>
      </div>
      <div class="p-6 space-y-4">
        <p class="text-[var(--color-text-secondary)]">Selecciona el cliente destino para la nueva venta. También puedes
          crear uno nuevo.</p>
        <div>
          <label for="duplicate-client-select"
            class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Cliente</label>
          <select id="duplicate-client-select" class="w-full"></select>
        </div>
      </div>
      <div class="p-6 bg-[var(--color-bg-primary)] flex justify-between items-center rounded-b-lg">
        <button type="button" id="duplicate-add-new-client-btn"
          class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg">Añadir nuevo cliente</button>
        <div class="space-x-3">
          <button type="button" id="duplicate-modal-cancel-btn"
            class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg">Cancelar</button>
          <button type="button" id="duplicate-modal-confirm-btn"
            class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-6 rounded-lg">Continuar</button>
        </div>
      </div>
    </div>
  </div>
  <div id="pending-sales-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-4xl">
      <div class="p-6 border-b border-[var(--color-border)] flex justify-between items-center">
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Ventas Guardadas</h2>
        <button id="close-pending-sales-modal-btn"
          class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl">&times;</button>
      </div>
      <div class="p-6">
        <input type="text" id="search-pending-sale" placeholder="Buscar por folio o cliente..."
          class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 mb-4 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" />
        <div class="max-h-[60vh] overflow-y-auto pending-sales-table-wrapper">
          <table class="min-w-full">
            <thead
              class="bg-[var(--color-bg-primary)] text-xs text-[var(--color-text-secondary)] uppercase sticky top-0">
              <tr>
                <th class="py-2 px-4 text-left">Folio</th>
                <th class="py-2 px-4 text-left">Fecha</th>
                <th class="py-2 px-4 text-left">Cliente</th>
                <th class="py-2 px-4 text-right">Total</th>
                <th class="py-2 px-4 text-center w-48">Acciones</th> <!-- Ancho ajustado -->
              </tr>
            </thead>
            <tbody id="pending-sales-table-body" class="divide-y divide-[var(--color-border)]">
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div id="stock-checker-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
      <div class="p-6 border-b border-[var(--color-border)] flex justify-between items-center">
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Consultar Stock en Sucursales</h2>
        <button id="close-stock-checker-modal-btn"
          class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl">&times;</button>
      </div>
      <div class="p-6">
        <input type="text" id="stock-checker-search-input" placeholder="Buscar por nombre, SKU o código de barras..."
          class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-3 mb-4 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" />
      </div>
      <div id="stock-checker-results" class="flex-1 overflow-y-auto px-6 pb-6">
        <div class="text-center text-[var(--color-text-secondary)] py-10">
          Introduce un término de búsqueda para ver el stock.
        </div>
      </div>
    </div>
  </div>
  <div id="cashOpeningModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
    <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl p-8 w-full max-w-md">
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-bold text-[var(--color-text-primary)]">Registrar Apertura de Caja</h3>
        <button id="closeCashModalBtn"
          class="text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl">&times;</button>
      </div>
      <div id="modal-error-message"
        class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 hidden" role="alert">
        <span class="block sm:inline"></span>
      </div>
      <form id="cashOpeningForm">
        <div class="mb-4">
          <label for="monto_inicial" class="block text-[var(--color-text-secondary)] text-sm font-bold mb-2">Monto
            Inicial:</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-[var(--color-text-secondary)]">$</span>
            <input type="text" id="monto_inicial" name="monto_inicial" required
              class="shadow appearance-none border rounded w-full py-2 px-3 pl-8 bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] border-[var(--color-border)] leading-tight focus:outline-none focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
              placeholder="0.00">
          </div>
        </div>
        <div class="mb-6">
          <label for="fecha_apertura" class="block text-[var(--color-text-secondary)] text-sm font-bold mb-2">Fecha de
            Apertura:</label>
          <input type="date" id="fecha_apertura" name="fecha_apertura" required
            class="shadow appearance-none border rounded w-full py-2 px-3 bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] border-[var(--color-border)] leading-tight focus:outline-none focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
        </div>
        <div class="flex items-center justify-end">
          <button type="button" id="cancelCashOpeningBtn"
            class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg mr-2">
            Cancelar
          </button>
          <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg">
            Registrar Apertura
          </button>
        </div>
      </form>
    </div>
  </div>

  <div id="qz-confirm" class="hidden fixed inset-0 bg-black/50 items-center justify-center">
    <div class="bg-white rounded p-4 w-80 text-slate-900">
      <h3 class="font-semibold mb-2">Usar QZ Tray</h3>
      <p class="text-sm mb-4">QZ solicitará permisos del sistema. ¿Deseas continuar?</p>
      <div class="flex justify-end gap-2">
        <button id="qz-cancel" class="px-3 py-1 border rounded">Cancelar</button>
        <button id="qz-accept" class="px-3 py-1 bg-slate-800 text-white rounded">Conectar</button>
      </div>
    </div>
  </div>

  <!-- Modal: descripción para productos que inician con # -->
  <div id="desc-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-md">
      <div class="p-6 border-b border-[var(--color-border)]">
        <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">Descripción del producto</h2>
      </div>
      <div class="p-6">
        <input id="desc-input" type="text"
          class="w-full bg-[var(--color-bg-primary)] border border-[var(--color-border)] rounded p-2"
          placeholder="Escribe la descripción">
      </div>
      <div class="p-6 bg-[var(--color-bg-primary)] flex justify-end items-center rounded-b-lg">
        <button type="button" id="desc-cancel-btn"
          class="bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg mr-2">
          Cancelar
        </button>
        <button type="button" id="desc-confirm-btn"
          class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-6 rounded-lg">
          Aceptar
        </button>
      </div>
    </div>
  </div>



  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/autonumeric@4.6.0/dist/autoNumeric.min.js"></script>
  <script src="js/rutas.js"></script>
  <script src="js/toast.js"></script>
  <script src="js/confirm.js"></script>
  <script src="js/pos.js"></script>
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