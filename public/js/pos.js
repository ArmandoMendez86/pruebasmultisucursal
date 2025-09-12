// Archivo: /public/js/pos.js

document.addEventListener("DOMContentLoaded", function () {
  // --- Referencias a elementos del DOM ---
  const productListContainer = document.getElementById("product-list");
  const cartItemsContainer = document.getElementById("cart-items");
  const subtotalElem = document.getElementById("cart-subtotal");
  const taxElem = document.getElementById("cart-tax");
  const totalElem = document.getElementById("cart-total");
  const searchProductInput = document.getElementById("search-product");
  const chargeBtn = document.getElementById("charge-btn");
  const cancelSaleBtn = document.getElementById("cancel-sale-btn");
  const saveSaleBtn = document.getElementById("save-sale-btn");
  const chargeModal = document.getElementById("charge-modal");
  const modalTotalElem = document.getElementById("modal-total");
  const modalCancelBtn = document.getElementById("modal-cancel-btn");
  const modalConfirmBtn = document.getElementById("modal-confirm-btn");
  const priceTypeSelector = document.getElementById("price-type-selector");
  const priceTypeValueInput = document.getElementById("price-type-value");
  const addressContainer = document.getElementById("address-selection-container");
  const addressSelect = document.getElementById("client-address-select");
  const openPendingSalesBtn = document.getElementById("open-pending-sales-btn");
  const pendingSalesModal = document.getElementById("pending-sales-modal");
  const closePendingSalesModalBtn = document.getElementById("close-pending-sales-modal-btn");
  const pendingSalesTableBody = document.getElementById("pending-sales-table-body");
  const searchPendingSaleInput = document.getElementById("search-pending-sale");
  const searchCartInput = document.getElementById("search-cart-item");
  const toggleIvaCheckbox = document.getElementById("toggle-iva");
  const searchClientSelect = $("#search-client");
  const paymentMethodsContainer = document.getElementById("payment-methods-container");
  const addPaymentMethodBtn = document.getElementById("add-payment-method-btn");
  const modalAmountPaidElem = document.getElementById("modal-amount-paid");
  const modalChangeElem = document.getElementById("modal-change");
  const modalPendingElem = document.getElementById("modal-pending");
  const modalChangeRow = document.getElementById("modal-change-row");
  const modalPendingRow = document.getElementById("modal-pending-row");
  const addClientModal = document.getElementById("add-client-modal");
  const addNewClientBtn = document.getElementById("add-new-client-btn");
  const closeAddClientModalBtn = document.getElementById("close-add-client-modal-btn");
  const addClientForm = document.getElementById("add-client-form");
  const cancelAddClientBtn = document.getElementById("cancel-add-client-btn");
  const clientHasCreditCheckbox = document.getElementById("client-has-credit");
  const creditLimitContainer = document.getElementById("credit-limit-container");
  const stockCheckerModal = document.getElementById("stock-checker-modal");
  const openStockCheckerBtn = document.getElementById("open-stock-checker-btn");
  const closeStockCheckerModalBtn = document.getElementById("close-stock-checker-modal-btn");
  const stockCheckerSearchInput = document.getElementById("stock-checker-search-input");
  const stockCheckerResultsContainer = document.getElementById("stock-checker-results");
  let stockSearchTimer;
  const openCashModalBtn = document.getElementById('openCashModalBtn');
  const cashOpeningModal = document.getElementById('cashOpeningModal');
  const closeCashModalBtn = document.getElementById('closeCashModalBtn');
  const cancelCashOpeningBtn = document.getElementById('cancelCashOpeningBtn');
  const cashOpeningForm = document.getElementById('cashOpeningForm');
  const montoInput = document.getElementById('monto_inicial');
  const fechaInput = document.getElementById('fecha_apertura');
  const modalErrorMessage = document.getElementById('modal-error-message');

  const descModal = document.getElementById("desc-modal");
  const descInput = document.getElementById("desc-input");
  const descConfirmBtn = document.getElementById("desc-confirm-btn");
  const descCancelBtn = document.getElementById("desc-cancel-btn");

  // === [CRÉDITO] Referencias del widget (pos.php) ===
  const creditWidget = document.getElementById('credit-widget');
  const creditBadge = document.getElementById('credit-badge');
  const creditDebt = document.getElementById('credit-debt');
  const creditLimit = document.getElementById('credit-limit');
  const creditAvailable = document.getElementById('credit-available');
  const creditProgress = document.getElementById('credit-progress');
  const creditProjected = document.getElementById('credit-projected');
  const creditProjectedAmount = document.getElementById('credit-projected-amount');
  const creditCustomerName = document.getElementById('credit-customer-name');

  // Proyección de crédito durante el cobro (monto que se enviaría a crédito)
  let projectedCreditIncrease = 0;

  // Formato moneda robusto
  function formatMoney(n) {
    if (typeof formatNumber === 'function') return `$${formatNumber(n)}`;
    return `$${Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  }



  let montoInicialAn;

  let allProducts = [];
  const PRODUCTS_TO_SHOW = 20;
  let skuBarcodeMap = {};
  let searchAbortController = null;
  let searchTimer = null;
  const SEARCH_LIMIT = 50;
  let cart = [];
  let lastCartSnapshot = null; // snapshot del carrito al confirmar la venta (para imprimir)


  //Agregar diferentes productos con #
  let cartUid = 0; // identificador único por línea del carrito


  let selectedClient = {
    id: 1,
    nombre: "Público en General",
    tiene_credito: 0,
    limite_credito: 0.0,
    deuda_actual: 0.0,
  };
  let currentSaleId = null;
  let configuredPrinter = null;
  let applyIVA = false;
  let allPendingSales = [];
  let paymentInputs = [];
  let currentPriceLevel = parseInt((priceTypeValueInput && priceTypeValueInput.value) || "1", 10);

  let allowNegativeStock = false;

  const negativeStockToggle = document.getElementById('toggle-negative-stock');
  if (negativeStockToggle) {
    negativeStockToggle.addEventListener('change', (event) => {
      allowNegativeStock = event.target.checked;
      updateProductViewability();
      const statusText = allowNegativeStock ? 'Venta sin stock ACTIVADA' : 'Venta sin stock DESACTIVADA';
      showToast(statusText, 'info');
    });
  }

  function formatNumber(value) {
    const number = parseFloat(value) || 0;
    return number.toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function updateCreditUI() {
    if (!creditWidget) return;

    const isPublic = selectedClient && String(selectedClient.id) === "1";
    const hasCredit = selectedClient && Number(selectedClient.tiene_credito) === 1;

    if (!selectedClient || isPublic || !hasCredit) {
      creditWidget.classList.add('hidden');
      creditWidget.classList.remove('over-limit');
      return;
    }

    // Mostrar
    creditWidget.classList.remove('hidden');

    const deuda = parseFloat(selectedClient.deuda_actual || 0);
    const limite = parseFloat(selectedClient.limite_credito || 0);
    const disponible = Math.max(0, limite - deuda);

    if (creditCustomerName) creditCustomerName.textContent = selectedClient.nombre || "";
    if (creditDebt) creditDebt.textContent = formatMoney(deuda);
    if (creditLimit) creditLimit.textContent = formatMoney(limite);
    if (creditAvailable) creditAvailable.textContent = formatMoney(disponible);

    // % consumido para la barra
    let pct = 0;
    if (limite > 0) pct = Math.min(100, Math.round((deuda / limite) * 100));
    else if (deuda > 0) pct = 100;

    // Reset de clases de color/animación
    creditProgress.classList.remove('bg-emerald-500', 'bg-yellow-400', 'bg-rose-500', 'bar-striped', 'bar-animated');
    creditWidget.classList.remove('over-limit');

    // Color según nivel
    if (pct < 60) creditProgress.classList.add('bg-emerald-500');
    else if (pct < 90) creditProgress.classList.add('bg-yellow-400');
    else creditProgress.classList.add('bg-rose-500');

    creditProgress.style.width = `${pct}%`;
    creditProgress.setAttribute('aria-valuenow', String(pct));

    // Proyección: si hay parte a crédito en el pago
    if (projectedCreditIncrease > 0) {
      const proyectada = deuda + projectedCreditIncrease;
      creditProjected.classList.remove('hidden');
      creditProjectedAmount.textContent = formatMoney(proyectada);

      // Activa rayas animadas mientras hay proyección
      creditProgress.classList.add('bar-striped', 'bar-animated');

      // Glow si la proyección rebasa el límite
      if (limite > 0 && proyectada > limite) {
        creditWidget.classList.add('over-limit');
        creditProgress.classList.remove('bg-emerald-500', 'bg-yellow-400');
        creditProgress.classList.add('bg-rose-500');
      }
    } else {
      creditProjected.classList.add('hidden');
      // Glow si ya no hay disponible
      if (limite > 0 && disponible <= 0) {
        creditWidget.classList.add('over-limit');
        creditProgress.classList.remove('bg-emerald-500', 'bg-yellow-400');
        creditProgress.classList.add('bg-rose-500');
      }
    }

    // Badge (estático en Tailwind)
    if (creditBadge) creditBadge.innerHTML = `
    <svg viewBox="0 0 24 24" aria-hidden="true" class="w-4 h-4"><path fill="currentColor" d="M2 6.5A2.5 2.5 0 0 1 4.5 4h15A2.5 2.5 0 0 1 22 6.5v11A2.5 2.5 0 0 1 19.5 20h-15A2.5 2.5 0 0 1 2 17.5v-11Zm2.5-.5a.5.5 0 0 0-.5.5V8h18V6.5a.5.5 0 0 0-.5-.5h-17Zm17 6H4v5.5a.5.5 0 0 0 .5.5h15a.5.5 0 0 0 .5-.5V12Z"/></svg>
    Crédito activo
  `;
  }


  function getPriceForProduct(product, level = currentPriceLevel) {
    if (product.tipo_precio_aplicado === "Especial" && product.precio_final != null) {
      return parseFloat(product.precio_final) || 0;
    }
    const key = `precio_${level}`;
    let v = product[key];
    if (v == null) {
      if (level === 2 && product.precio_mayoreo != null) v = product.precio_mayoreo;
      else if (product.precio_menudeo != null) v = product.precio_menudeo;
    }
    return parseFloat(v) || 0;
  }

  function buildSkuBarcodeMap(products) {
    skuBarcodeMap = {};
    products.forEach((product) => {
      if (product.sku) {
        const skuKey = String(product.sku).trim();
        skuBarcodeMap[skuKey] = product;
      }
      if (product.codigos_barras) {
        product.codigos_barras
          .split(',')
          .map((c) => c.trim())
          .filter((c) => c.length > 0)
          .forEach((code) => {
            skuBarcodeMap[code] = product;
          });
      }
    });
  }

  async function searchProductsBackend(query) {
    if (searchAbortController) {
      try { searchAbortController.abort(); } catch (_) { }
    }
    searchAbortController = new AbortController();
    const signal = searchAbortController.signal;
    try {
      const url = `${BASE_URL}/searchProducts?q=${encodeURIComponent(query)}&limit=${SEARCH_LIMIT}`;
      const resp = await fetch(url, { signal });
      const result = await resp.json();
      if (result.success) {
        allProducts = result.data;
        renderProducts(allProducts);
      } else {
        productListContainer.innerHTML = `<p class="text-[var(--color-text-secondary)]">Sin resultados.</p>`;
      }
    } catch (e) {
      if (e.name !== 'AbortError') {
        console.error('Error buscando productos en backend', e);
        productListContainer.innerHTML = `<p class="text-red-500">Error al buscar.</p>`;
      }
    }
  }

  function updateProductViewability() {
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
      const productId = parseInt(card.dataset.productId, 10);
      const productData = allProducts.find(p => p.id === productId);
      if (productData) {
        const isOutOfStock = productData.stock <= 0 && !allowNegativeStock;
        if (isOutOfStock) {
          card.classList.add('out-of-stock');
        } else {
          card.classList.remove('out-of-stock');
        }
      }
    });
  }

  async function fetchProducts() {
    try {
      const response = await fetch(`${BASE_URL}/getProducts?limit=20`);
      const result = await response.json();
      if (result.success) {
        allProducts = result.data;
        buildSkuBarcodeMap(allProducts);
        renderProducts(allProducts.slice(0, PRODUCTS_TO_SHOW));
      } else {
        productListContainer.innerHTML = `<p class="text-red-500">Error al cargar productos.</p>`;
      }
    } catch (error) {
      console.error("Error fetching products:", error);
      productListContainer.innerHTML = `<p class="text-red-500">No se pudo conectar para cargar productos.</p>`;
    }
  }

  // Reemplaza esta función en tu archivo /public/js/pos.js

  function renderProducts(products) {
    productListContainer.innerHTML = "";
    if (products.length === 0) {
      productListContainer.innerHTML = `<p class="text-[var(--color-text-secondary)]">No se encontraron productos.</p>`;
      return;
    }
    products.forEach((product) => {
      const productCard = document.createElement("div");
      const stock = product.stock || 0;
      const isOutOfStock = stock <= 0 && !allowNegativeStock;
      productCard.className = `product-card p-3 rounded-lg text-center cursor-pointer transition-all duration-200 ease-in-out shadow-md flex flex-col justify-between h-full ${isOutOfStock ? 'out-of-stock' : ''}`;
      productCard.dataset.productId = product.id;
      productCard.title = String(product.nombre || '');
      productCard.setAttribute('aria-label', productCard.title);

      // ----- LÍNEA MODIFICADA -----
      // Si product.url_imagen existe, la usamos. Si no, usamos un placeholder.
      const imageUrl = product.imagen_url
        ? `${BASE_URL}/public/${product.imagen_url}`
        : `https://placehold.co/100x100/334155/E2E8F0?text=No+Img`;

      const stockClass = isOutOfStock ? 'zero-stock' : '';
      const stockText = isOutOfStock ? 'Agotado' : `Stock: ${stock}`;

      productCard.innerHTML = `
        <img src="${imageUrl}" alt="${product.nombre}" class="product-card-image w-[70px] h-[70px] object-cover rounded-md mx-auto mb-2 shadow-sm">
        <div class="flex-1 flex flex-col justify-between">
          <div class="product-card-name font-bold text-sm mb-1 truncate">${product.nombre}</div>
          <div class="product-card-stock text-xs mb-2 ${stockClass}">${stockText}</div>
          <div class="font-mono text-green-400 text-xs font-bold">$${formatNumber(getPriceForProduct(product))}</div>
        </div>`;
      productCard.addEventListener("click", () => addProductToCart(product.id));
      productListContainer.appendChild(productCard);
    });
  }


  function renderCart() {
    cartItemsContainer.innerHTML = "";
    const searchTerm = searchCartInput.value.toLowerCase();
    const filteredCart = cart.filter(
      (item) =>
        item.nombre.toLowerCase().includes(searchTerm) ||
        (item.sku && item.sku.toLowerCase().includes(searchTerm)) ||
        (item.codigos_barras && item.codigos_barras.toLowerCase().includes(searchTerm))
    );

    if (filteredCart.length === 0) {
      cartItemsContainer.innerHTML = `<div class="text-center text-[var(--color-text-secondary)] py-10">El carrito está vacío</div>`;
    } else {
      filteredCart.forEach((item) => {
        const cartItem = document.createElement("div");
        cartItem.className = "cart-item flex items-center p-2 mb-1 rounded-md shadow-sm";

        // *** UID por línea (para productos que inician con "#")
        const dataUid = (item.uid != null ? item.uid : item.id);

        // Usamos la imagen real del item si existe.
        const imageUrl = item.imagen_url
          ? `${BASE_URL}/public/${item.imagen_url}`
          : `https://placehold.co/50x50/334155/E2E8F0?text=No+Img`;

        let priceTypeLabel = "";
        if (item.tipo_precio_aplicado === "Especial") {
          priceTypeLabel = '<span class="text-xxs text-yellow-400">Especial</span> ';
        } else if (item.tipo_precio_aplicado === "Guardado") {
          priceTypeLabel = '<span class="text-xxs text-yellow-400">Guardado</span> ';
        } else if (String(item.tipo_precio_aplicado || '').startsWith('P')) {
          priceTypeLabel = `<span class="text-xxs text-blue-400">${item.tipo_precio_aplicado}</span> `;
        }

        cartItem.innerHTML = `
        <img src="${imageUrl}" alt="${item.nombre}" class="cart-item-image w-10 h-10 object-cover rounded mr-2">
        <div class="flex-1">
            <p class="text-sm font-semibold text-[var(--color-text-primary)] truncate">${item.nombre}</p>
            ${item.descripcion !== undefined && item.descripcion !== null
            ? `<p class="text-xxs italic text-[var(--color-text-secondary)]">
                    <span class="editable-desc" data-id="${item.id}" data-uid="${dataUid}">
                      ${item.descripcion === "" ? "[sin descripción]" : item.descripcion}
                    </span>
                  </p>`
            : ""
          }
            <p class="text-xs text-[var(--color-text-secondary)]">
                ${priceTypeLabel}
                <!-- Precio por PRODUCTO: se mantiene data-id -->
                <select class="price-level-select bg-[var(--color-bg-primary)] border border-[var(--color-border)] rounded text-xs mr-2" data-id="${item.id}" data-uid="${dataUid}">
                    <option value="Especial" ${item.tipo_precio_aplicado === 'Especial' ? 'selected' : ''}>Especial</option>
                    <option value="P1" ${item.tipo_precio_aplicado === 'P1' ? 'selected' : ''}>P1</option>
                    <option value="P2" ${item.tipo_precio_aplicado === 'P2' ? 'selected' : ''}>P2</option>
                    <option value="P3" ${item.tipo_precio_aplicado === 'P3' ? 'selected' : ''}>P3</option>
                    <option value="P4" ${item.tipo_precio_aplicado === 'P4' ? 'selected' : ''}>P4</option>
                    <option value="P5" ${item.tipo_precio_aplicado === 'P5' ? 'selected' : ''}>P5</option>
                </select>
                <span class="editable-price" data-id="${item.id}" data-uid="${dataUid}" data-price="${item.precio_final}">
                    $${formatNumber(item.precio_final)}
                </span>
            </p>
        </div>
        <div class="flex items-center ml-2">
            <div class="quantity-controls flex items-center gap-px rounded-md overflow-hidden">
                <!-- Cantidad por LÍNEA: usar también data-uid -->
                <button data-id="${item.id}" data-uid="${dataUid}" class="quantity-change p-1 text-sm font-bold">-</button>
                <input type="number" min="1"
                  class="quantity-input w-12 text-center text-sm px-1 mx-px focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]"
                  data-id="${item.id}" data-uid="${dataUid}" value="${item.quantity}" />
                <button data-id="${item.id}" data-uid="${dataUid}" class="quantity-change p-1 text-sm font-bold">+</button>
            </div>
            <div class="text-right font-mono text-base ml-2 line-total w-24" data-id="${item.id}" data-uid="${dataUid}">$${formatNumber(item.quantity * item.precio_final)}</div>
            <button data-id="${item.id}" data-uid="${dataUid}" class="remove-item-btn text-red-400 hover:text-red-300 p-1 ml-1 rounded-full">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>`;
        cartItemsContainer.appendChild(cartItem);
      });
    }
    updateTotals();
    toggleSaveButton();
    addPriceEditListeners();
    addDescEditListeners();
  }


  async function addProductToCart(productId) {
    const productInfo = allProducts.find((p) => p.id == productId);
    if (!productInfo) { showToast("Producto no encontrado.", "error"); return; }
    if (productInfo.stock <= 0 && !allowNegativeStock) { showToast("Producto agotado.", "error"); return; }

    const startsHash = (s) => String(s || "").trim().startsWith("#");
    const isCustom = startsHash(productInfo.nombre) || startsHash(productInfo.sku);

    // SOLO agrupamos si NO es custom (#...)
    if (!isCustom) {
      const existing = cart.find((item) => item.id == productId);
      if (existing) {
        if (allowNegativeStock || (productInfo && existing.quantity < productInfo.stock)) {
          existing.quantity++; renderCart();
        } else { showToast("Stock máximo alcanzado en el carrito.", "error"); }
        return;
      }
    }

    try {
      const response = await fetch(`${BASE_URL}/getProductForPOS?id_producto=${productId}&id_cliente=${selectedClient.id}`);
      const result = await response.json();
      if (!result.success) { showToast(result.message, "error"); return; }
      const product = result.data;

      if ((product.stock || 0) <= 0 && !allowNegativeStock) { showToast("Producto sin stock.", "error"); return; }
      if (product.tipo_precio_aplicado !== "Especial") {
        product.precio_final = getPriceForProduct(product, currentPriceLevel);
        product.tipo_precio_aplicado = `P${currentPriceLevel}`;
      }

      let descripcion = null;
      // Si es custom, pedimos descripción SIEMPRE; cada línea tendrá la suya.
      if (isCustom || startsHash(product.nombre) || startsHash(product.sku)) {
        const typed = await promptForDescription();
        if (typed === null) return;  // cancelado
        descripcion = typed;         // '' -> backend guardará NULL
      }

      cart.push({ ...product, quantity: 1, id: product.id, descripcion, uid: (++cartUid) });
      renderCart();
      cartItemsContainer.scrollTop = cartItemsContainer.scrollHeight;
    } catch (_) {
      showToast("Error de conexión al añadir el producto.", "error");
    }
  }


  function filterProducts() {
    const query = searchProductInput.value.trim();
    if (!query) {
      renderProducts(allProducts.slice(0, PRODUCTS_TO_SHOW));
      return;
    }
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      searchProductsBackend(query);
    }, 250);
  }

  async function handleBarcodeScan(event) {
    if (event.key !== "Enter") return;
    event.preventDefault();
    const code = searchProductInput.value.trim();
    if (code === "") return;
    let product = skuBarcodeMap[code] || null;
    if (product) {
      addProductToCart(product.id);
      searchProductInput.value = "";
      filterProducts();
      return;
    }
    try {
      const resp = await fetch(`${BASE_URL}/getProductByBarcode?code=${encodeURIComponent(code)}`);
      const json = await resp.json();
      if (json.success && json.data && json.data.id) {
        addProductToCart(json.data.id);
        searchProductInput.value = "";
        filterProducts();
      } else {
        showToast("Producto no encontrado por SKU o código de barras.", "error");
      }
    } catch (e) {
      showToast("Error al buscar el producto por código.", "error");
    }
  }

  function openStockCheckerModal() {
    stockCheckerModal.classList.remove("hidden");
    stockCheckerSearchInput.focus();
  }

  function closeStockCheckerModal() {
    stockCheckerModal.classList.add("hidden");
    stockCheckerSearchInput.value = "";
    stockCheckerResultsContainer.innerHTML = `<div class="text-center text-[var(--color-text-secondary)] py-10">Introduce un término de búsqueda para ver el stock.</div>`;
  }

  async function searchStockAcrossBranches() {
    const searchTerm = stockCheckerSearchInput.value.trim();
    if (searchTerm.length < 3) {
      stockCheckerResultsContainer.innerHTML = `<div class="text-center text-[var(--color-text-secondary)] py-10">Introduce al menos 3 caracteres para buscar.</div>`;
      return;
    }
    stockCheckerResultsContainer.innerHTML = `<div class="text-center text-[var(--color-text-secondary)] py-10">Buscando...</div>`;
    try {
      const response = await fetch(`${BASE_URL}/getStockAcrossBranches?term=${encodeURIComponent(searchTerm)}`);
      const result = await response.json();
      if (result.success) {
        renderStockResults(result.data);
      } else {
        stockCheckerResultsContainer.innerHTML = `<div class="text-center text-red-500 py-10">${result.message}</div>`;
      }
    } catch (error) {
      console.error("Error al buscar stock:", error);
      stockCheckerResultsContainer.innerHTML = `<div class="text-center text-red-500 py-10">Error de conexión.</div>`;
    }
  }

  function renderStockResults(products) {
    if (products.length === 0) {
      stockCheckerResultsContainer.innerHTML = `<div class="text-center text-[var(--color-text-secondary)] py-10">No se encontraron productos que coincidan con la búsqueda.</div>`;
      return;
    }
    let html = "";
    products.forEach((product) => {
      html += `
        <div class="bg-[var(--color-bg-primary)] p-4 rounded-lg mb-3">
          <h3 class="text-lg font-bold text-[var(--color-text-primary)]">${product.producto_nombre}</h3>
          <p class="text-sm text-[var(--color-text-secondary)] mb-3">SKU: ${product.sku}</p>
          <ul class="space-y-2">`;
      product.sucursales.forEach((sucursal) => {
        const stockClass = sucursal.stock > 0 ? "text-green-400" : "text-red-400";
        const formattedStock = parseInt(sucursal.stock).toLocaleString('es-MX');
        const stockText = sucursal.stock > 0 ? `${formattedStock} en stock` : "Agotado";
        html += `
          <li class="flex justify-between items-center text-sm bg-[var(--color-bg-secondary)]/[0.5] px-3 py-2 rounded-md">
            <span><i class="fas fa-store mr-2 text-[var(--color-text-secondary)]"></i>${sucursal.nombre}</span>
            <span class="font-semibold ${stockClass}">${stockText}</span>
          </li>`;
      });
      html += `</ul></div>`;
    });
    stockCheckerResultsContainer.innerHTML = html;
  }

  function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + item.quantity * item.precio_final, 0);
    let tax = 0;
    if (applyIVA) {
      tax = subtotal * 0.16;
    }
    const total = subtotal + tax;
    subtotalElem.textContent = `$${formatNumber(subtotal)}`;
    taxElem.textContent = `$${formatNumber(tax)}`;
    totalElem.textContent = `$${formatNumber(total)}`;
  }

  function addPriceEditListeners() {
    document.querySelectorAll(".editable-price").forEach((priceSpan) => {
      if (priceSpan.dataset.hasListener === "true") return;
      priceSpan.dataset.hasListener = "true";



      // ... dentro de addPriceEditListeners

      priceSpan.addEventListener("click", function () {
        const currentPrice = parseFloat(this.dataset.price);
        const productId = this.dataset.id;
        const uid = this.dataset.uid; // <-- 1. CAPTURAMOS EL UID DEL SPAN

        const input = document.createElement("input");
        input.type = "number";
        input.step = "0.01";
        input.value = currentPrice.toFixed(2);
        input.className = "w-24 bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded text-center text-sm px-1 focus:outline-none focus:ring-1 focus:ring-[var(--color-accent)]";

        input.dataset.id = productId;
        input.dataset.uid = uid; // <-- 2. ASIGNAMOS EL UID AL NUEVO INPUT

        this.replaceWith(input);
        input.focus();

        let savingPrice = false;
        const saveNewPrice = async () => {
          if (savingPrice) return;
          savingPrice = true;
          let newPrice = parseFloat(input.value);
          if (isNaN(newPrice) || newPrice <= 0) {
            showToast("El precio debe ser un número positivo.", "error");
            newPrice = currentPrice;
          }

          // Ahora `input.dataset.uid` sí tiene el valor correcto
          const key = input.dataset.uid;
          const cartItem = cart.find((item) =>
            (item.uid != null ? String(item.uid) === String(key) : String(item.id) === String(productId))
          );

          if (cartItem) {
            // ... resto de la función (esta parte ya estaba bien)

            cartItem.precio_final = newPrice;
            cartItem.tipo_precio_aplicado = "Especial";
            if (selectedClient.id !== 1) {
              try {
                const response = await fetch(`${BASE_URL}/saveSpecialClientPrice`, {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({
                    id_cliente: selectedClient.id,
                    id_producto: productId,
                    precio_especial: newPrice,
                  }),
                });
                const result = await response.json();
                if (result.success) {
                  showToast("Precio especial guardado para " + selectedClient.nombre, "success");
                } else {
                  showToast("Error al guardar precio especial: " + result.message, "error");
                }
              } catch (error) {
                console.error("Error al guardar precio especial:", error);
                showToast("Error de conexión al guardar precio especial.", "error");
              }
            }
          }
          renderCart();
          savingPrice = false;
        };
        input.addEventListener("blur", saveNewPrice);
        input.addEventListener("keydown", function (event) {
          if (event.key === "Enter") {
            event.preventDefault();
            saveNewPrice();
          }
        });
      });
    });
  }

  function addDescEditListeners() {
    document.querySelectorAll(".editable-desc").forEach((span) => {
      if (span.dataset.hasListener === "true") return;
      span.dataset.hasListener = "true";
      span.addEventListener("click", async () => {
        const key = span.dataset.uid || span.dataset.id;
        const item = cart.find((i) =>
          (i.uid != null ? String(i.uid) === String(key) : String(i.id) === String(key))
        );
        if (!item) return;
        const typed = await promptForDescription(item.descripcion || "");
        if (typed !== null) { item.descripcion = typed; renderCart(); }
      });
    });
  }



  function populateAddresses(addresses) {
    addressSelect.innerHTML = "";
    if (addresses && addresses.length > 0) {
      addresses.forEach((addr) => {
        const option = document.createElement("option");
        option.value = addr.id;
        option.textContent = addr.direccion;
        if (addr.principal == 1) {
          option.selected = true;
        }
        addressSelect.appendChild(option);
      });
      addressContainer.classList.remove("hidden");
    } else {
      addressContainer.classList.add("hidden");
    }
  }

  function handleQuantityInput(event) {
    const input = event.target.closest(".quantity-input");
    if (!input) return;
    const key = input.dataset.uid || input.dataset.id;
    let val = parseInt(input.value, 10);
    if (isNaN(val)) return;
    if (val < 1) val = 1;

    const item = cart.find((i) =>
      (i.uid != null ? String(i.uid) === String(key) : String(i.id) === String(key))
    );
    if (!item) return;

    const product = allProducts.find((p) => p.id == item.id);
    if (product && typeof product.stock === "number" && !allowNegativeStock) {
      if (val > product.stock) val = product.stock;
    }
    item.quantity = val;

    const line = cartItemsContainer.querySelector(`.line-total[data-uid="${item.uid}"]`)
      || cartItemsContainer.querySelector(`.line-total[data-id="${item.id}"]`);
    if (line) line.textContent = `$${(item.quantity * item.precio_final).toFixed(2)}`;
    updateTotals();
  }


  function handleQuantityCommit(event) {
    const input = event.target.closest(".quantity-input");
    if (!input) return;
    const key = input.dataset.uid || input.dataset.id;
    let val = parseInt(input.value, 10);
    if (isNaN(val) || val < 1) val = 1;

    const item = cart.find((i) =>
      (i.uid != null ? String(i.uid) === String(key) : String(i.id) === String(key))
    );
    if (!item) return;

    const product = allProducts.find((p) => p.id == item.id);
    if (product && typeof product.stock === "number" && val > product.stock && !allowNegativeStock) {
      val = product.stock;
      showToast("Stock máximo alcanzado en el carrito.", "error");
    }
    if (val === 0) {
      cart = cart.filter((it) => (it.uid != null ? String(it.uid) !== String(key) : String(it.id) !== String(key)));
      renderCart(); return;
    }
    item.quantity = val;
    renderCart();
  }



  function handleQuantityChange(event) {
    const button = event.target.closest(".quantity-change");
    if (!button) return;
    const key = button.dataset.uid || button.dataset.id;
    const action = button.textContent.trim(); // '+' o '-'

    const cartItem = cart.find((i) =>
      (i.uid != null ? String(i.uid) === String(key) : String(i.id) === String(key))
    );
    if (!cartItem) return;

    const product = allProducts.find((p) => p.id == cartItem.id);
    if (action === "+") {
      if (allowNegativeStock || (product && cartItem.quantity < product.stock)) {
        cartItem.quantity++;
      } else {
        showToast("Stock máximo alcanzado en el carrito.", "error");
      }
    } else if (action === "-") {
      cartItem.quantity--;
      if (cartItem.quantity === 0) {
        cart = cart.filter((it) => (it.uid != null ? it.uid !== cartItem.uid : it.id !== cartItem.id));
      }
    }
    renderCart();
  }


  function removeProductFromCart(key) {
    cart = cart.filter((item) =>
      (item.uid != null ? String(item.uid) !== String(key) : String(item.id) !== String(key))
    );
    renderCart();
    showToast("Producto eliminado del carrito.", "info");
  }


  async function selectClient(client, confirmAction = true) {
    if (confirmAction && cart.length > 0) {
      const confirmed = await showConfirm("Al cambiar de cliente, el carrito se vaciará para recalcular los precios. ¿Desea continuar?");
      if (!confirmed) {
        searchClientSelect.val(selectedClient.id).trigger("change");
        return;
      }
    }
    try {
      const response = await fetch(`${BASE_URL}/getClient?id=${client.id}`);
      const result = await response.json();
      if (result.success) {
        // Debe venir: id, nombre, tiene_credito (0/1), limite_credito, deuda_actual, direcciones[]
        selectedClient = result.data;

        if (confirmAction) {
          cart = [];
          renderCart();
        }
        populateAddresses(selectedClient.direcciones || []);

        projectedCreditIncrease = 0;
        updateCreditUI();
      } else {
        showToast("No se pudieron obtener los detalles del cliente.", "error");
      }
    } catch (error) {
      showToast("Error de conexión al obtener datos del cliente.", "error");
    }
  }


  function handlePriceTypeChange() {
    currentPriceLevel = parseInt(priceTypeValueInput.value || "1", 10);
    if (cart.length === 0) return;
    cart.forEach((item) => {
      if (item.tipo_precio_aplicado !== "Especial" && item.tipo_precio_aplicado !== "Guardado") {
        item.precio_final = getPriceForProduct(item, currentPriceLevel);
        item.tipo_precio_aplicado = `P${currentPriceLevel}`;
      }
    });
    renderCart();
  }

  function resetSale() {
    cart = [];
    selectedClient = { id: 1, nombre: "Público en General", tiene_credito: 0, limite_credito: 0.0, deuda_actual: 0.0 };
    populateAddresses([]);
    renderCart();
    searchClientSelect.val("1").trigger("change");
    searchProductInput.value = "";
    currentSaleId = null;
    searchCartInput.value = "";
    toggleIvaCheckbox.checked = false;
    applyIVA = false;
    if (priceTypeSelector) {
      priceTypeSelector.querySelector(".price-type-btn.active-price-type")?.classList.remove("active-price-type");
      priceTypeSelector.querySelector('button[data-level="1"]')?.classList.add("active-price-type");
    }
    if (priceTypeValueInput) {
      priceTypeValueInput.value = "1";
    }

    projectedCreditIncrease = 0;
    updateCreditUI();


  }

  async function cancelSale() {
    if (cart.length > 0) {
      const confirmed = await showConfirm("¿Desea cancelar la venta? Se limpiará el carrito.");
      if (confirmed) resetSale();
    }
  }

  function addPaymentMethodInput(method = "Efectivo", amount = 0) {
    const paymentMethodDiv = document.createElement("div");
    paymentMethodDiv.className = "flex items-center space-x-2 mb-2 payment-input-row";
    const paymentMethods = ["Efectivo", "Tarjeta", "Transferencia", "Crédito"];
    const optionsHtml = paymentMethods.map((m) => `<option value="${m}" ${m === method ? "selected" : ""}>${m}</option>`).join("");
    paymentMethodDiv.innerHTML = `
        <select class="payment-method-select w-1/2 bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]">${optionsHtml}</select>
        <input type="number" step="0.01" value="${amount.toFixed(2)}" placeholder="Monto" class="payment-amount-input w-1/2 bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" />
        <button class="remove-payment-method-btn text-red-400 hover:text-red-300 p-2"><i class="fas fa-times"></i></button>`;
    paymentMethodsContainer.appendChild(paymentMethodDiv);
    const amountInput = paymentMethodDiv.querySelector(".payment-amount-input");
    const methodSelect = paymentMethodDiv.querySelector(".payment-method-select");
    amountInput.addEventListener("input", updatePaymentTotals);
    methodSelect.addEventListener("change", () => {
      if (methodSelect.value === "Crédito") {
        const totalToPay = parseFloat(totalElem.textContent.replace(/[\$,]/g, ""));
        const currentNonCreditPaid = getCurrentNonCreditPaidAmount(amountInput);
        const remainingToPay = totalToPay - currentNonCreditPaid;
        if (selectedClient.id === 1) {
          showToast("El crédito no se aplica al cliente 'Público en General'. Por favor, selecciona otro método de pago o un cliente registrado.", "error");
          amountInput.value = (0).toFixed(2);
          methodSelect.value = "Efectivo";
        } else if (selectedClient.tiene_credito === 0) {
          showToast(`El cliente '${selectedClient.nombre}' no tiene una línea de crédito activada.`, "error");
          amountInput.value = (0).toFixed(2);
          methodSelect.value = "Efectivo";
        } else {
          const availableCredit = selectedClient.limite_credito - selectedClient.deuda_actual;
          if (availableCredit <= 0) {
            showToast(`El cliente '${selectedClient.nombre}' no tiene crédito disponible. Deuda actual: $${selectedClient.deuda_actual.toFixed(2)} / Límite: $${selectedClient.limite_credito.toFixed(2)}`, "error");
            amountInput.value = (0).toFixed(2);
            methodSelect.value = "Efectivo";
          } else {
            amountInput.value = Math.min(remainingToPay, availableCredit).toFixed(2);
          }
        }
      } else {
        const hasCreditPaymentAlready = Array.from(document.querySelectorAll(".payment-method-select")).some((select) => select.value === "Crédito" && select !== methodSelect);
        if (paymentInputs.length === 1 || !hasCreditPaymentAlready) {
          const totalToPay = parseFloat(totalElem.textContent.replace(/[\$,]/g, ""));
          const currentNonCreditPaid = getCurrentNonCreditPaidAmount(amountInput);
          amountInput.value = (totalToPay - currentNonCreditPaid).toFixed(2);
        }
      }
      updatePaymentTotals();
    });
    paymentMethodDiv.querySelector(".remove-payment-method-btn").addEventListener("click", () => {
      paymentMethodDiv.remove();
      updatePaymentTotals();
    });
    paymentInputs.push(amountInput);
    updatePaymentTotals();
  }

  function getCurrentNonCreditPaidAmount(excludeInput = null) {
    let currentNonCreditPaid = 0;
    document.querySelectorAll(".payment-input-row").forEach((row) => {
      const amountInput = row.querySelector(".payment-amount-input");
      const methodSelect = row.querySelector(".payment-method-select");
      if (methodSelect.value !== "Crédito" && amountInput !== excludeInput) {
        currentNonCreditPaid += parseFloat(amountInput.value) || 0;
      }
    });
    return currentNonCreditPaid;
  }

  function updatePaymentTotals() {
    let totalPaid = 0;
    let creditAmount = 0;
    let hasCreditPayment = false;
    let creditExceeded = false;
    let creditNotAllowed = false;
    paymentInputs = [];
    document.querySelectorAll(".payment-input-row").forEach((row) => {
      const amountInput = row.querySelector(".payment-amount-input");
      const methodSelect = row.querySelector(".payment-method-select");
      const amount = parseFloat(amountInput.value) || 0;
      totalPaid += amount;
      paymentInputs.push(amountInput);
      if (methodSelect.value === "Crédito") {
        hasCreditPayment = true;
        creditAmount += amount;
        if (selectedClient.id === 1) {
          creditNotAllowed = true;
        } else if (selectedClient.tiene_credito === 0) {
          creditNotAllowed = true;
        } else {
          const availableCredit = selectedClient.limite_credito - selectedClient.deuda_actual;
          if (creditAmount > availableCredit) {
            creditExceeded = true;
          }
        }
      }
    });
    const totalToPay = parseFloat(totalElem.textContent.replace(/[\$,]/g, "")) || 0;
    const change = totalPaid - totalToPay;
    const pending = totalToPay - totalPaid;

    modalAmountPaidElem.textContent = `$${formatNumber(totalPaid)}`;
    if (change >= 0) {
      modalChangeElem.textContent = `$${formatNumber(change)}`;
      modalChangeRow.classList.remove("hidden");
      modalPendingRow.classList.add("hidden");
    } else {
      modalPendingElem.textContent = `$${formatNumber(Math.abs(pending))}`;
      modalChangeRow.classList.add("hidden");
      modalPendingRow.classList.remove("hidden");
    }
    modalConfirmBtn.disabled = totalPaid < totalToPay || creditExceeded || creditNotAllowed;
    if (modalConfirmBtn.disabled && hasCreditPayment) {
      if (creditNotAllowed) {
        if (selectedClient.id === 1) {
          showToast("El crédito no se aplica al cliente 'Público en General'.", "error");
        } else {
          showToast(`El cliente '${selectedClient.nombre}' no tiene una línea de crédito activada.`, "error");
        }
      } else if (creditExceeded) {
        const availableCredit = selectedClient.limite_credito - selectedClient.deuda_actual;
        showToast(`Crédito insuficiente para '${selectedClient.nombre}'. Disponible: $${Math.max(0, availableCredit).toFixed(2)}`, "error");
      }
    }
    // === [CRÉDITO] Proyección en UI ===
    projectedCreditIncrease = creditAmount || 0; // 'creditAmount' debe ser lo que quedará a crédito
    updateCreditUI();

  }

  function showChargeModal() {
    if (cart.length === 0) {
      showToast("El carrito está vacío.", "error");
      return;
    }
    modalTotalElem.textContent = totalElem.textContent;
    paymentMethodsContainer.innerHTML = "";
    paymentInputs = [];
    const totalAmount = parseFloat(totalElem.textContent.replace(/[\$,]/g, ""));
    addPaymentMethodInput("Efectivo", totalAmount);
    updatePaymentTotals();
    chargeModal.classList.remove("hidden");
  }

  function hideChargeModal() {
    chargeModal.classList.add("hidden");
  }

  async function processSale() {
    const payments = [];
    document.querySelectorAll(".payment-input-row").forEach((row) => {
      const method = row.querySelector(".payment-method-select").value;
      const amount = parseFloat(row.querySelector(".payment-amount-input").value) || 0;
      if (amount > 0) {
        payments.push({ method: method, amount: amount });
      }
    });
    if (payments.length === 0) {
      showToast("Debe añadir al menos un método de pago.", "error");
      return;
    }
    let creditPaymentAmount = 0;
    for (const p of payments) {
      if (p.method === "Crédito") {
        creditPaymentAmount += p.amount;
      }
    }
    if (creditPaymentAmount > 0) {
      if (selectedClient.id === 1) {
        showToast("Error: El crédito no se aplica al cliente 'Público en General'. Por favor, selecciona otro método de pago o un cliente registrado.", "error");
        return;
      }
      if (selectedClient.tiene_credito === 0) {
        showToast(`Error: El cliente '${selectedClient.nombre}' no tiene una línea de crédito activada.`, "error");
        return;
      }
      const availableCredit = selectedClient.limite_credito - selectedClient.deuda_actual;
      if (creditPaymentAmount > availableCredit) {
        showToast(`Error: El monto de crédito excede el disponible para '${selectedClient.nombre}'. Disponible: $${availableCredit.toFixed(2)}`, "error");
        return;
      }
    }
    const saleData = {
      id_cliente: selectedClient.id,
      id_direccion_envio: addressSelect.value || null,
      cart: cart,
      total: parseFloat(totalElem.textContent.replace(/[\$,]/g, "")),
      payments: payments,
      estado: "Completada",
      iva_aplicado: applyIVA ? 1 : 0,
      allow_negative_stock: allowNegativeStock,
    };
    if (currentSaleId) {
      saleData.id_venta = currentSaleId;
    }
    try {
      const response = await fetch(`${BASE_URL}/processSale`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(saleData),
      });
      const result = await response.json();
      if (result.success) {
        showToast(currentSaleId ? "Venta completada exitosamente." : "Venta registrada exitosamente.", "success");

        // >>> Snapshot del carrito para sustituir el nombre por la descripción en productos '#'
        lastCartSnapshot = Array.isArray(cart) ? cart.map(it => ({
          id: it.id,
          nombre: it.nombre,
          sku: it.sku,
          descripcion: (it.descripcion ?? null),
          quantity: it.quantity,
          precio_final: it.precio_final,
          uid: it.uid
        })) : null;
        // <<<

        if (result.id_venta) {
          await triggerPrint(result.id_venta);
        }
        resetSale();
        fetchProducts();
      }
      else {
        showToast(result.message, "error");
      }
    } catch (error) {
      showToast("Error de conexión al procesar la venta.", "error");
    } finally {
      hideChargeModal();
    }
  }

  async function handleSaveSale() {
    if (cart.length === 0) {
      showToast("Debe tener productos en el carrito para guardar la venta.", "error");
      return;
    }
    const saleData = {
      id_cliente: selectedClient.id,
      id_direccion_envio: addressSelect.value || null,
      cart: cart,
      total: parseFloat(totalElem.textContent.replace(/[\$,]/g, "")),
      estado: "Pendiente",
      iva_aplicado: applyIVA ? 1 : 0,
      payments: [],
    };
    if (currentSaleId) {
      saleData.id_venta = currentSaleId;
    }
    try {
      const response = await fetch(`${BASE_URL}/saveSale`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(saleData),
      });
      const result = await response.json();
      if (result.success) {
        showToast(currentSaleId ? "Venta actualizada exitosamente." : "Venta guardada exitosamente.", "success");
        resetSale();
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      showToast("Error de conexión al guardar la venta.", "error");
    }
  }

  function toggleSaveButton() {
    saveSaleBtn.disabled = cart.length === 0;
  }

  async function openPendingSalesModal() {
    pendingSalesTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-[var(--color-text-secondary)]">Cargando...</td></tr>`;
    pendingSalesModal.classList.remove("hidden");
    try {
      const response = await fetch(`${BASE_URL}/listPendingSales`);
      const result = await response.json();
      if (result.success) {
        allPendingSales = result.data;
        filterAndRenderPendingSales();
      } else {
        pendingSalesTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-red-500">${result.message}</td></tr>`;
      }
    } catch (error) {
      pendingSalesTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-red-500">Error de conexión.</td></tr>`;
    }
  }

  function closePendingSalesModal() {
    pendingSalesModal.classList.add("hidden");
    searchPendingSaleInput.value = "";
  }

  function filterAndRenderPendingSales() {
    const searchTerm = searchPendingSaleInput.value.toLowerCase();
    const filteredSales = allPendingSales.filter(
      (sale) =>
        sale.id.toString().includes(searchTerm) ||
        sale.cliente_nombre.toLowerCase().includes(searchTerm)
    );
    renderPendingSales(filteredSales);
  }

  function renderPendingSales(sales) {
    if (!sales || sales.length === 0) {
      pendingSalesTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-[var(--color-text-secondary)]">No hay ventas guardadas que coincidan con la búsqueda.</td></tr>`;
      return;
    }
    pendingSalesTableBody.innerHTML = "";
    sales.forEach((sale) => {
      const tr = document.createElement("tr");
      tr.className = "hover:bg-[var(--color-bg-primary)]";
      const formattedDate = new Date(sale.fecha).toLocaleString("es-MX", { dateStyle: "short", timeStyle: "short" });
      tr.innerHTML = `
                <td class="py-2 px-4 text-sm font-semibold">#${sale.id}</td>
                <td class="py-2 px-4 text-sm">${formattedDate}</td>
                <td class="py-2 px-4 text-sm">${sale.cliente_nombre}</td>
                <td class="py-2 px-4 text-right text-sm font-mono">$${formatNumber(sale.total)}</td>
                <td class="py-2 px-4 text-center">
                    <div class="flex items-center justify-center space-x-3">
                        <button data-id="${sale.id}" class="load-sale-btn text-blue-400 hover:text-blue-300" title="Cargar Venta"><i class="fas fa-folder-open"></i></button>
                        <button data-id="${sale.id}" class="duplicate-sale-btn text-yellow-400 hover:text-yellow-300" title="Duplicar Venta"><i class="fas fa-copy"></i></button>
                        <a href="${BASE_URL}/generateQuote?id=${sale.id}" target="_blank" class="pdf-sale-btn text-green-400 hover:text-green-300" title="Ver Cotización PDF"><i class="fas fa-file-pdf"></i></a>
                        <button data-id="${sale.id}" class="delete-sale-btn text-red-400 hover:text-red-300" title="Eliminar Venta"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </td>`;
      pendingSalesTableBody.appendChild(tr);
    });
  }

  async function loadSale(saleId) {
    closePendingSalesModal();
    if (cart.length > 0) {
      const confirmed = await showConfirm("Se limpiará el carrito actual para cargar la venta pendiente. ¿Desea continuar?");
      if (!confirmed) return;
    }
    try {
      const response = await fetch(`${BASE_URL}/loadSale?id=${saleId}`);
      const result = await response.json();
      if (result.success) {
        loadSaleIntoPOS(result.data);
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      showToast("Error de conexión al cargar la venta.", "error");
    }
  }

  async function loadSaleIntoPOS(saleData) {
    currentSaleId = saleData.header.id;
    await selectClient({ id: saleData.header.id_cliente, nombre: saleData.header.cliente_nombre }, false);
    const clientOption = new Option(selectedClient.nombre, selectedClient.id, true, true);
    searchClientSelect.append(clientOption).trigger("change");
    if (saleData.header.id_direccion_envio) {
      addressSelect.value = saleData.header.id_direccion_envio;
    }
    applyIVA = saleData.header.iva_aplicado == 1;
    toggleIvaCheckbox.checked = applyIVA;
    cart = saleData.items.map((item) => ({
      id: item.id_producto,
      nombre: item.nombre,
      quantity: parseInt(item.cantidad),
      precio_final: parseFloat(item.precio_unitario),
      precio_menudeo: item.precio_menudeo,
      precio_mayoreo: item.precio_mayoreo,
      tipo_precio_aplicado: "Guardado",
      sku: item.sku || null,
      codigo_barras: item.codigo_barras || null,
      descripcion: item.descripcion || null,
    }));
    renderCart();
    showToast(`Venta #${currentSaleId} cargada en el POS.`, "info");
  }

  function promptForDescription(defaultText = "") {
    return new Promise((resolve) => {
      const close = (val = null) => { descModal.classList.add("hidden"); resolve(val); };
      const onConfirm = () => { const v = descInput.value.trim(); cleanup(); close(v || null); };
      const onCancel = () => { cleanup(); close(null); };
      const onKey = (e) => { if (e.key === "Enter") onConfirm(); if (e.key === "Escape") onCancel(); };
      function cleanup() {
        descConfirmBtn.removeEventListener("click", onConfirm);
        descCancelBtn.removeEventListener("click", onCancel);
        descInput.removeEventListener("keydown", onKey);
      }
      descInput.value = defaultText || "";
      descModal.classList.remove("hidden");
      setTimeout(() => descInput.focus(), 0);
      descConfirmBtn.addEventListener("click", onConfirm);
      descCancelBtn.addEventListener("click", onCancel);
      descInput.addEventListener("keydown", onKey);
    });
  }


  async function promptForDuplicateClient() {
    return new Promise((resolve) => {
      // Obtenemos referencias a los elementos del modal que está en pos.php
      const modal = document.getElementById('duplicate-client-modal');
      const selectElement = modal.querySelector('#duplicate-client-select');
      const confirmBtn = modal.querySelector('#duplicate-modal-confirm-btn');
      const cancelBtn = modal.querySelector('#duplicate-modal-cancel-btn');
      const addNewClientBtn = modal.querySelector('#duplicate-add-new-client-btn');

      const $select = $(selectElement);

      // Función para limpiar todo (eventos y Select2) y cerrar el modal
      const cleanup = () => {
        modal.classList.add('hidden');
        confirmBtn.removeEventListener('click', onConfirm);
        cancelBtn.removeEventListener('click', onCancel);
        addNewClientBtn.removeEventListener('click', onAddNew);
        // Destruir la instancia de Select2 para evitar problemas de memoria
        if ($select.data('select2')) {
          $select.select2('destroy');
        }
        $select.empty(); // Limpiar opciones
      };

      // Manejadores de eventos
      const onConfirm = () => {
        const selectedId = $select.val() ? parseInt($select.val(), 10) : null;
        cleanup();
        resolve(selectedId);
      };

      const onCancel = () => {
        cleanup();
        resolve(null);
      };

      const onAddNew = () => {
        showAddClientModal(); // Reutilizamos el modal de añadir cliente
        // Escuchamos el evento personalizado que se dispara cuando un cliente se crea
        document.addEventListener('pos:new-client-created', function onNewClient(e) {
          const newClient = e.detail;
          if (newClient && newClient.id && newClient.nombre) {
            // Creamos la nueva opción, la seleccionamos y actualizamos Select2
            const option = new Option(newClient.nombre, newClient.id, true, true);
            $select.append(option).trigger('change');
          }
        }, { once: true }); // 'once: true' asegura que el evento se escuche solo una vez
      };

      // Asignamos los eventos a los botones
      confirmBtn.addEventListener('click', onConfirm);
      cancelBtn.addEventListener('click', onCancel);
      addNewClientBtn.addEventListener('click', onAddNew);

      // Inicializamos Select2 usando la función reutilizable que ya tienes
      initClientSelect2($select, modal);

      // Pre-cargamos la opción "Público en General" por defecto
      const defaultOption = new Option("Público en General", 1, true, true);
      $select.append(defaultOption).trigger('change');

      // Mostramos el modal
      modal.classList.remove('hidden');

      // Abrimos el buscador de Select2 y ponemos el foco para escribir
      $select.select2('open');
    });
  }

  async function handleDuplicateSale(saleId) {
    // Pedimos cliente destino
    const idClienteDestino = await promptForDuplicateClient();
    if (idClienteDestino === null) {
      return; // cancelado
    }

    try {
      const response = await fetch(`${BASE_URL}/duplicateSale`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_venta: saleId, id_cliente: idClienteDestino })
      });
      const result = await response.json();
      if (result.success) {
        showToast(`Venta #${saleId} duplicada. Nuevo folio: #${result.new_sale_id}`, 'success');
        openPendingSalesModal();
      } else {
        showToast(result.message || 'No se pudo duplicar la venta.', 'error');
      }
    } catch (error) {
      console.error(error);
      showToast('Error de conexión al duplicar la venta.', 'error');
    }
  }


  async function handleDeletePendingSale(saleId) {
    const confirmed = await showConfirm("¿Estás seguro de que quieres eliminar esta venta pendiente? Esta acción es irreversible.");
    if (!confirmed) return;
    try {
      const response = await fetch(`${BASE_URL}/deletePendingSale`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id_venta: saleId }),
      });
      const result = await response.json();
      if (result.success) {
        showToast(result.message, "success");
        openPendingSalesModal();
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      showToast("Error de conexión al eliminar la venta.", "error");
    }
  }

  function showAddClientModal() {
    addClientModal.classList.remove("hidden");
    addClientForm.reset();
    creditLimitContainer.classList.add("hidden");
    // asegurar que quede encima del overlay del modal de duplicado
    addClientModal.style.zIndex = "120";
    // enfocar primer campo
    setTimeout(() => {
      const first = addClientForm.querySelector("input, select, textarea");
      if (first) first.focus();
    }, 0);
  }

  function hideAddClientModal() {
    addClientModal.classList.add("hidden");
    addClientModal.style.zIndex = "";
  }

  async function handleAddNewClient(event) {
    event.preventDefault();
    const formData = new FormData(addClientForm);
    const clientData = {};
    for (const [key, value] of formData.entries()) {
      clientData[key] = value;
    }
    clientData.tiene_credito = clientHasCreditCheckbox.checked ? 1 : 0;
    clientData.limite_credito = parseFloat(clientData.limite_credito) || 0.0;
    try {
      const response = await fetch(`${BASE_URL}/createClient`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(clientData),
      });
      const result = await response.json();
      if (result.success) {
        showToast("Cliente añadido exitosamente.", "success");
        hideAddClientModal();
        const newClient = {
          id: result.id,
          text: clientData.nombre,
          original: { id: result.id, nombre: clientData.nombre, tiene_credito: clientData.tiene_credito, limite_credito: clientData.limite_credito, deuda_actual: 0.0 },
        };
        document.dispatchEvent(new CustomEvent('pos:new-client-created', { detail: { id: result.id, nombre: clientData.nombre } }));
        const option = new Option(newClient.text, newClient.id, true, true);
        searchClientSelect.append(option).trigger("change");
        searchClientSelect.trigger({ type: "select2:select", params: { data: newClient } });
      } else {
        showToast(`Error al añadir cliente: ${result.message}`, "error");
      }
    } catch (error) {
      console.error("Error al añadir cliente:", error);
      showToast("Error de conexión al añadir el cliente.", "error");
    }
  }

  function setupCashOpeningModal() {
    const showModal = () => {
      fechaInput.value = new Date().toISOString().split('T')[0];
      if (montoInicialAn) {
        montoInicialAn.clear();
      }
      modalErrorMessage.classList.add('hidden');
      cashOpeningModal.classList.remove('hidden');
      montoInput.focus();
    };
    const hideModal = () => cashOpeningModal.classList.add('hidden');
    const showModalError = (message) => {
      modalErrorMessage.textContent = message;
      modalErrorMessage.classList.remove('hidden');
    }
    openCashModalBtn.addEventListener('click', showModal);
    closeCashModalBtn.addEventListener('click', hideModal);
    cancelCashOpeningBtn.addEventListener('click', hideModal);
    cashOpeningForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      modalErrorMessage.classList.add('hidden');

      const monto = montoInicialAn.getNumericString();
      const fecha = fechaInput.value;

      if (monto === '' || parseFloat(monto) < 0 || !fecha) {
        showModalError('Por favor, ingrese un monto y fecha válidos.');
        return;
      }
      const data = { monto_inicial: parseFloat(monto), fecha_apertura: fecha };
      try {
        const response = await fetch(`${BASE_URL}/registrarApertura`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(data)
        });
        const result = await response.json();
        if (response.ok) {
          hideModal();
          showToast(result.message, 'success');
        } else {
          showModalError(result.message || 'Ocurrió un error inesperado.');
        }
      } catch (error) {
        showModalError('No se pudo conectar con el servidor. Verifique su conexión.');
      }
    });
  }

  let printMethod = 'service';
  let qzScriptsLoaded = false;
  const pmServiceBtn = document.getElementById('pm-service');
  const pmQzBtn = document.getElementById('pm-qztray');
  const qzConnectBtn = document.getElementById('btn-qz-connect');
  const qzStatusWrap = document.getElementById('qz-status');
  const qzConfirmModal = document.getElementById('qz-confirm');
  const qzAcceptBtn = document.getElementById('qz-accept');
  const qzCancelBtn = document.getElementById('qz-cancel');

  function markMethodUI() {
    if (!pmServiceBtn || !pmQzBtn) return;
    pmServiceBtn.classList.toggle('bg-[var(--color-accent)]', printMethod === 'service');
    pmServiceBtn.classList.toggle('text-white', printMethod === 'service');
    pmQzBtn.classList.toggle('bg-[var(--color-accent)]', printMethod === 'qztray');
    pmQzBtn.classList.toggle('text-white', printMethod === 'qztray');
    qzConnectBtn.classList.toggle('hidden', printMethod !== 'qztray' || (typeof qz !== 'undefined' && qz.websocket.isActive()));
    qzStatusWrap.classList.toggle('hidden', printMethod !== 'qztray');
  }

  function setQzStatus(connected) {
    if (!qzStatusWrap) return;
    const statusSpan = qzStatusWrap.querySelector('span');
    if (statusSpan) {
      statusSpan.textContent = connected ? 'Conectado' : 'Desconectado';
      statusSpan.className = connected ? 'text-green-400 font-semibold' : 'text-red-400 font-semibold';
    }
    markMethodUI();
  }

  async function fetchPrintPrefs() {
    try {
      const response = await fetch(`${BASE_URL}/getPrintPrefs`);
      const result = await response.json();
      if (result.success && result.data) {
        printMethod = result.data.print_method || 'service';
        configuredPrinter = result.data.impresora_tickets;
      }
    } catch (e) {
      console.error("No se pudieron cargar las preferencias de impresión.", e);
      printMethod = 'service';
    }
    markMethodUI();
  }

  async function savePrintPrefs(method) {
    try {
      await fetch(`${BASE_URL}/updatePrintPrefs`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ print_method: method })
      });
    } catch (e) {
      console.error("No se pudieron guardar las preferencias de impresión.", e);
      showToast("Error al guardar preferencia.", "error");
    }
  }

  async function loadQZScripts() {
    if (qzScriptsLoaded) return Promise.resolve();
    const loadScript = (src) => new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = src;
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
    try {
      await loadScript("https://cdn.jsdelivr.net/npm/js-sha256@0.9.0/src/sha256.min.js");
      await loadScript("https://cdn.jsdelivr.net/npm/qz-tray@2.2/qz-tray.min.js");
      await loadScript("js/qz-tray-handler.js");
      qzScriptsLoaded = true;
    } catch (error) {
      console.error("Falló la carga de scripts de QZ Tray:", error);
      throw new Error("Falló la carga de scripts de QZ Tray.");
    }
  }

  async function triggerPrint(saleId) {
    try {
      const response = await fetch(`${BASE_URL}/getTicketDetails?id=${encodeURIComponent(saleId)}`);
      const result = await response.json();
      if (!result.success) {
        showToast(`Error al obtener ticket: ${result.message}`, "error");
        return;
      }
      await imprimirTicketDespuesDeGuardar(result);
    } catch (e) {
      console.error("Error de red al obtener ticket:", e);
      showToast("Error de red al obtener ticket.", "error");
    }
  };

  function applyCustomDescriptionsToTicket(ticketData, cartSnapshot) {
    // Si no hay items o no hay snapshot, no hacemos nada.
    if (!ticketData || !Array.isArray(ticketData.items) || !Array.isArray(cartSnapshot)) return;

    const startsHash = (s) => String(s || "").trim().startsWith("#");

    // Construimos una cola de descripciones por id_producto según el orden en que se agregaron al carrito
    const queues = new Map();
    for (const it of cartSnapshot) {
      if (startsHash(it.nombre) || startsHash(it.sku)) {
        if (!queues.has(it.id)) queues.set(it.id, []);
        // Guardamos la descripción (puede venir vacía/null; en ese caso no sustituimos)
        queues.get(it.id).push(it.descripcion && it.descripcion !== "" ? it.descripcion : null);
      }
    }

    // Recorremos los renglones del ticket (como vienen del backend) y sustituimos
    for (const line of ticketData.items) {
      const lineIsHash = startsHash(line.producto_nombre) || startsHash(line.sku);
      if (!lineIsHash) continue;

      // Buscamos la siguiente descripción disponible para ese id_producto
      const q =
        queues.get(Number(line.id_producto)) ??
        queues.get(String(line.id_producto));

      if (q && q.length) {
        const desc = q.shift();
        if (desc && desc.trim() !== "") {
          // Sustituimos el nombre que empieza con # por la descripción de ESA línea
          line.producto_nombre = desc;
        }
      }
    }
  }


  async function imprimirTicketDespuesDeGuardar(result) {
    const ticketData = result.data || {};
    applyCustomDescriptionsToTicket(ticketData, lastCartSnapshot);

    if (configuredPrinter) {
      ticketData.printerName = configuredPrinter;
    }
    if (printMethod === 'service') {
      showToast("Enviando a servicio de impresión local...", "info");
      try {
        const response = await fetch('http://127.0.0.1:9898/imprimir', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(ticketData)
        });
        if (response.ok) {
          showToast("Ticket enviado al servicio local.", "success");
        } else {
          const errorText = await response.text();
          showToast(`Error del servicio local: ${errorText}`, "error");
        }
      } catch (err) {
        console.warn("Servicio local no disponible:", err);
        showToast("Servicio local no encontrado. Verifique que esté en ejecución.", "error");
      }
      return;
    }
    if (printMethod === 'qztray') {
      showToast("Enviando a QZ Tray...", "info");
      try {
        await loadQZScripts();
      } catch (e) {
        showToast("No se pudieron cargar los componentes de QZ Tray.", "error");
        return;
      }
      if (typeof qz === "undefined" || !qz.websocket.isActive()) {
        showToast("QZ Tray no está conectado. Conéctelo para poder imprimir.", "warning");
        return;
      }
      if (!configuredPrinter) {
        showToast("No hay una impresora configurada para QZ Tray. Vaya a Ajustes.", "error");
        return;
      }
      try {
        await printTicket(configuredPrinter, ticketData);
        showToast("Ticket enviado a QZ Tray.", "success");
      } catch (e) {
        console.error("Error al imprimir con QZ Tray:", e);
        showToast("No se pudo imprimir con QZ Tray.", "error");
      }
    }
  };

  pmServiceBtn.addEventListener('click', async () => {
    printMethod = 'service';
    markMethodUI();
    await savePrintPrefs('service');
    showToast("Impresión por servicio local activada.", "success");
  });

  pmQzBtn.addEventListener('click', () => {
    if (printMethod === 'qztray') return;
    qzConfirmModal.classList.remove('hidden');
    qzConfirmModal.classList.add('flex');
  });

  qzCancelBtn.addEventListener('click', () => {
    qzConfirmModal.classList.add('hidden');
    qzConfirmModal.classList.remove('flex');
  });

  qzAcceptBtn.addEventListener('click', async () => {
    qzConfirmModal.classList.add('hidden');
    qzConfirmModal.classList.remove('flex');
    showToast("Activando QZ Tray...", "info");
    try {
      await loadQZScripts();
      if (typeof connectQz === 'function') {
        await connectQz(setQzStatus);
      }
      printMethod = 'qztray';
      markMethodUI();
      await savePrintPrefs('qztray');
      showToast("Modo de impresión QZ Tray activado.", "success");
    } catch (e) {
      showToast("No se pudo cargar o conectar con QZ Tray.", "error");
    }
  });

  qzConnectBtn.addEventListener('click', async () => {
    showToast("Intentando conectar con QZ Tray...", "info");
    try {
      await loadQZScripts();
      if (typeof connectQz === 'function') {
        await connectQz(setQzStatus);
      }
    } catch (e) {
      showToast("Fallo al intentar conectar con QZ.", "error");
    }
  });

  cartItemsContainer.addEventListener("click", function (event) {
    const quantityButton = event.target.closest(".quantity-change");
    const removeButton = event.target.closest(".remove-item-btn");
    if (quantityButton) {
      handleQuantityChange(event);
    } else if (removeButton) {
      const key = removeButton.dataset.uid || removeButton.dataset.id;
      removeProductFromCart(key);
    }
  });

  cartItemsContainer.addEventListener("input", handleQuantityInput);
  cartItemsContainer.addEventListener("change", handleQuantityCommit);
  cartItemsContainer.addEventListener("blur", handleQuantityCommit, true);
  cancelSaleBtn.addEventListener("click", cancelSale);
  chargeBtn.addEventListener("click", showChargeModal);
  saveSaleBtn.addEventListener("click", handleSaveSale);
  modalCancelBtn.addEventListener("click", hideChargeModal);
  modalConfirmBtn.addEventListener("click", processSale);

  if (priceTypeSelector) {
    priceTypeSelector.addEventListener("click", (e) => {
      const targetButton = e.target.closest(".price-type-btn");
      if (!targetButton || targetButton.classList.contains("active-price-type")) return;
      priceTypeSelector.querySelectorAll(".price-type-btn").forEach((btn) => btn.classList.remove("active-price-type"));
      targetButton.classList.add("active-price-type");
      const newLevel = parseInt(targetButton.dataset.level || "1", 10);
      if (String(priceTypeValueInput.value) !== String(newLevel)) {
        priceTypeValueInput.value = String(newLevel);
        priceTypeValueInput.dispatchEvent(new Event("change", { bubbles: true }));
      }
    });
  }

  if (priceTypeValueInput) {
    priceTypeValueInput.addEventListener("change", handlePriceTypeChange);
  }

  openPendingSalesBtn.addEventListener("click", openPendingSalesModal);
  closePendingSalesModalBtn.addEventListener("click", closePendingSalesModal);
  searchCartInput.addEventListener("input", renderCart);
  searchPendingSaleInput.addEventListener("input", filterAndRenderPendingSales);
  addPaymentMethodBtn.addEventListener("click", () => addPaymentMethodInput());


  cartItemsContainer.addEventListener("change", (e) => {
    const sel = e.target.closest(".price-level-select");
    if (!sel) return;
    const id = sel.dataset.id;
    const uid = sel.dataset.uid;
    const item = cart.find((i) =>
      (i.uid != null ? String(i.uid) === String(uid) : String(i.id) === String(id))
    );
    const val = sel.value;
    if (!item) return;
    // ... resto de la lógica
    if (val === "Especial") {
      item.tipo_precio_aplicado = "Especial";
    } else {
      const lvl = parseInt(val.slice(1), 10);
      item.tipo_precio_aplicado = `P${lvl}`;
      item.precio_final = getPriceForProduct(item, lvl);
    }
    renderCart();
  });

  toggleIvaCheckbox.addEventListener("change", () => {
    applyIVA = toggleIvaCheckbox.checked;
    updateTotals();
  });

  pendingSalesTableBody.addEventListener("click", function (event) {
    const loadButton = event.target.closest(".load-sale-btn");
    const deleteButton = event.target.closest(".delete-sale-btn");
    const duplicateButton = event.target.closest(".duplicate-sale-btn");
    if (loadButton) loadSale(loadButton.dataset.id);
    else if (deleteButton) handleDeletePendingSale(deleteButton.dataset.id);
    else if (duplicateButton) handleDuplicateSale(duplicateButton.dataset.id);
  });

  addNewClientBtn.addEventListener("click", showAddClientModal);
  closeAddClientModalBtn.addEventListener("click", hideAddClientModal);
  cancelAddClientBtn.addEventListener("click", hideAddClientModal);
  addClientForm.addEventListener("submit", handleAddNewClient);
  openStockCheckerBtn.addEventListener("click", openStockCheckerModal);
  closeStockCheckerModalBtn.addEventListener("click", closeStockCheckerModal);
  stockCheckerSearchInput.addEventListener("keyup", () => {
    clearTimeout(stockSearchTimer);
    stockSearchTimer = setTimeout(searchStockAcrossBranches, 300);
  });
  clientHasCreditCheckbox.addEventListener("change", function () {
    creditLimitContainer.classList.toggle("hidden", !this.checked);
  });
  searchProductInput.addEventListener("input", filterProducts);
  searchProductInput.addEventListener("keydown", handleBarcodeScan);


  // Reutilizable: configura Select2 para buscar clientes (carrito y modales)
  function initClientSelect2($el, dropdownParentEl = null) {
    const config = {
      width: "100%",
      placeholder: "Buscar cliente por nombre, RFC o teléfono...",
      minimumInputLength: 2,
      language: {
        inputTooShort: () => "Por favor, introduce 2 o más caracteres para buscar.",
        noResults: () => "No se encontraron resultados.",
        searching: () => "Buscando...",
      },
      ajax: {
        url: `${BASE_URL}/searchClients`,
        dataType: "json",
        delay: 250,
        data: (params) => ({ term: params.term }),
        processResults: (data) => ({
          results: (data.results || []).map((client) => ({
            id: client.id,
            text: client.text,
            original: client,
          })),
        }),
        cache: true,
      },
    };
    if (dropdownParentEl) config.dropdownParent = $(dropdownParentEl);
    return $el.select2(config);
  }
  initClientSelect2(searchClientSelect, null).on("select2:select", (e) => { /* handled below */ });
  // Reemplazado por initClientSelect2
  /* ORIGINAL REMOVED */
  initClientSelect2(searchClientSelect, null).on("select2:select", (e) => {
    const selectedData = e.params.data;
    const clientToSelect = selectedData.original || { id: selectedData.id, nombre: selectedData.text };
    selectClient(clientToSelect);
  });
  function initializePOS() {
    fetchPrintPrefs();
    fetchProducts();
    toggleSaveButton();
    setupCashOpeningModal();

    if (montoInput) {
      montoInicialAn = new AutoNumeric(montoInput, {
        currencySymbol: '',
        decimalCharacter: '.',
        digitGroupSeparator: ',',
        decimalPlaces: 2,
        minimumValue: '0'
      });
    }
    updateCreditUI();
  }

  initializePOS();
});
