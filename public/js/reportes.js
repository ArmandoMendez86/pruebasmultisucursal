// Archivo: /public/js/reportes.js

document.addEventListener("DOMContentLoaded", function () {
  // --- DataTables server-side for Sales Report ---
  let ventasDataTable = null;

  function initSalesDataTable() {
    const tabla = $('#tablaVentas');
    if (ventasDataTable) {
      ventasDataTable.ajax.reload();
      return;
    }
    ventasDataTable = tabla.DataTable({
      serverSide: true,
      processing: true,
      searching: true, // DataTables search box
      lengthMenu: [10, 25, 50, 100],
      pageLength: 10,
      ajax: {
        url: `${BASE_URL}/getSalesReportPaginated`,
        type: 'GET',
        data: function (d) {
          const userSel = document.getElementById('user-filter-select');
          d.user_id = userSel ? (userSel.value === 'all' ? '' : userSel.value) : '';
        }
      },
      columns: [
        {
          data: 'fecha', render: function (data, type) {
            const m = moment(data);
            if (type === 'display') return m.format('DD/MM/YYYY, h:mm a'); // lo que ves
            if (type === 'filter') return m.format('YYYY-MM-DD');         // lo que busca DataTables
            if (type === 'sort') return m.valueOf();                    // orden correcto
            if (type === 'export') return m.format('YYYY-MM-DD HH:mm');   // CSV/Excel/PDF
            return data;                                        // fallback
          }
        },
        { data: 'id', title: 'Ticket ID' },
        { data: 'cliente', title: 'Cliente' },
        { data: 'usuario', title: 'Vendedor' },
        {
          data: 'total',
          title: 'Total',
          className: 'dt-right',
          render: function (val, type) {
            if (type === 'display' || type === 'filter') {
              const n = parseFloat(val || 0);
              const f = n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
              return `<span class="text-green-400 font-semibold">$${f}</span>`;
            }
            return val;
          }
        },
        {
          data: 'estado',
          title: 'Estado',
          render: function (val) {
            const chip =
              val === 'Completada' ? 'bg-emerald-600/20 text-emerald-300' :
                val === 'Cancelada' ? 'bg-red-600/20 text-red-300' :
                  'bg-amber-600/20 text-amber-300';
            return `<span class="px-2 py-0.5 rounded-full text-xs ${chip}">${val}</span>`;
          }
        },
        { data: 'acciones', title: 'Acciones', orderable: false, searchable: false, className: 'text-center' }
      ],

      order: [[0, 'desc']],
      dom: 'Bfrtip',
      buttons: [
        { extend: 'csvHtml5', text: 'CSV', title: 'reporte_ventas' },
        { extend: 'excelHtml5', text: 'Excel', title: 'reporte_ventas' },
        { extend: 'pdfHtml5', text: 'PDF', title: 'reporte_ventas', orientation: 'landscape', pageSize: 'A4' },
        { extend: 'print', text: 'Imprimir' }
      ],
      language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
      }
    });

    // Al terminar cada draw, renombra el botón de imprimir (Windows)
    $('#tablaVentas').on('draw.dt', function () {
      const api = $('#tablaVentas').DataTable();
      api.rows({ page: 'current' }).every(function () {
        const d = this.data() || {};
        const label = String(d.estado || '').toLowerCase() === 'completada' ? 'Factura' : 'Proforma';
        const $row = $(this.node());
        $row.find('.print-ticket-win-btn').each(function () {
          // Conserva el ícono si existe
          const icon = this.querySelector('i');
          this.innerHTML = (icon ? icon.outerHTML + ' ' : '') + label;
        });
      });
    });


    $(document).on('click', '#tablaVentas .print-ticket-btn', function (e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      if (id) { handlePrintTicket(parseInt(id, 10)); }
    });
    $(document).on('click', '#tablaVentas .print-ticket-win-btn', async function (e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      if (id) { await handlePrintTicketViaDialog(parseInt(id, 10)); }
    });
    $(document).on('click', '#tablaVentas .view-pdf-btn', function (e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      if (id) { handleViewPdf(parseInt(id, 10)); }
    });
    $(document).on('click', '#tablaVentas .cancel-sale-btn', async function (e) {
      e.preventDefault();
      const id = this.getAttribute('data-id');
      if (id) { await cancelSale(parseInt(id, 10)); }
    });
  }

  const cashCutDateInput = document.getElementById("cash-cut-date");
  const initialCashInput = document.getElementById("initial-cash");
  const generateCashCutBtn = document.getElementById("generate-cash-cut-btn");
  const printCashCutBtn = document.getElementById("print-cash-cut-btn");
  const cashCutResultsContainer = document.getElementById("cash-cut-results");
  const userFilterSelect = document.getElementById('user-filter-select');

  const currentUserId = document.getElementById('current-user-id')?.value || '';
  const currentUserName = document.getElementById('current-user-name')?.value || '';


  let currentCashCutData = null;
  let currentInitialCash = 0;
  let printerConfig = {
    name: null,
    isLoaded: false,
    isLoading: false
  };
  let sucursalActual = {
    nombre: 'Sucursal Principal',
    direccion: '',
    telefono: ''
  };

  const configPrinterBtn = document.getElementById('config-printer-btn');
  const printerConfigModal = document.getElementById('printer-config-modal');
  const savePrinterConfigBtn = document.getElementById('save-printer-config-btn');
  const cancelPrinterConfigBtn = document.getElementById('cancel-printer-config-btn');
  const radioService = document.getElementById('print-method-service');
  const radioQzTray = document.getElementById('print-method-qztray');

  function updatePrinterMethodSelection() {
    if (printMethod === 'service') {
      radioService.checked = true;
    } else {
      radioQzTray.checked = true;
    }
  }

  if (configPrinterBtn) {
    configPrinterBtn.addEventListener('click', () => {
      updatePrinterMethodSelection();
      printerConfigModal.classList.remove('hidden');
    });
  }

  if (cancelPrinterConfigBtn) {
    cancelPrinterConfigBtn.addEventListener('click', () => {
      printerConfigModal.classList.add('hidden');
    });
  }

  if (savePrinterConfigBtn) {
    savePrinterConfigBtn.addEventListener('click', () => {
      const selectedMethod = radioService.checked ? 'service' : 'qztray';
      localStorage.setItem('reportesPrintMethod', selectedMethod);
      printMethod = selectedMethod;
      showToast('Configuración de impresora guardada.', 'success');
      printerConfigModal.classList.add('hidden');
      if (printMethod === 'qztray') {
        if (typeof connectQz === "function") {
          connectQz();
        } else {
          console.error("La función connectQz no está disponible.");
        }
      }
    });
  }

  async function getPrintPrefs() {
    if (printerConfig.isLoading || printerConfig.isLoaded) return;
    printerConfig.isLoading = true;
    try {
      const localPrintMethod = localStorage.getItem('reportesPrintMethod');
      const response = await fetch(`${BASE_URL}/getPrintPrefs`);
      const result = await response.json();
      if (result.success && result.data) {
        printerConfig.name = result.data.impresora_tickets || null;
        if (!localPrintMethod) {
          printMethod = result.data.print_method || 'service';
        }
      }
      updatePrinterMethodSelection();
    } catch (error) {
      console.error("No se pudo cargar las preferencias de impresión.", error);
      showToast("Error al cargar preferencias de impresión.", "error");
    } finally {
      printerConfig.isLoading = false;
      printerConfig.isLoaded = true;
    }
  }

  async function loadUsersForFilter() {
    if (!userFilterSelect) return;
    try {
      const response = await fetch(`${BASE_URL}/getBranchUsers`);
      const result = await response.json();
      if (result.success) {
        result.data.forEach(user => {
          const option = document.createElement('option');
          option.value = user.id;
          option.textContent = user.nombre;
          userFilterSelect.appendChild(option);
        });
      }
    } catch (error) {
      console.error('Error al cargar usuarios para el filtro:', error);
    }
  }

  async function fetchSucursalDetails() {
    try {
      const response = await fetch(`${BASE_URL}/getSucursalActual`);
      const result = await response.json();
      if (result.success && result.data) {
        sucursalActual = result.data;
      } else {
        console.warn("No se pudieron cargar los detalles de la sucursal.");
      }
    } catch (error) {
      console.error("Error de conexión al obtener detalles de la sucursal:", error);
    }
  }

  let printMethod = localStorage.getItem('reportesPrintMethod') || 'service';

  if (printMethod === 'qztray') {
    if (typeof connectQz === "function") {
      connectQz();
    }
  }

  const ticketWidth = 42;
  const removeAccents = (str) => str ? str.normalize("NFD").replace(/[\u0300-\u036f]/g, "") : "";
  const formatCurrencyForTicket = (value) => parseFloat(value).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  const padLR = (left, right) => {
    left = removeAccents(left || "");
    right = removeAccents(right || "");
    const padding = Math.max(0, ticketWidth - left.length - right.length);
    return left + " ".repeat(padding) + right;
  };

  const padMultiple = (c1, c2, c3, c4) => {
    const w1 = 5, w2 = 16, w3 = 8, w4 = 8;
    c1 = (c1 || "").padEnd(w1);
    c2 = (c2 || "").padEnd(w2);
    c3 = (c3 || "").padStart(w3);
    c4 = (c4 || "").padStart(w4);
    const line = `${c1} ${c2}${c3}${c4}`;
    return line.substring(0, ticketWidth);
  };

  async function printTicketQZ(printerName, ticketData) {
    if (!qzTrayConnected) {
      showToast("No se puede imprimir: QZ Tray está desconectado.", "error");
      return;
    }
    const config = qz.configs.create(printerName);
    const venta = ticketData.venta || {};
    const items = ticketData.items || [];

    const companyName = "MEGA PARTY GDL";
    const companyRfc = "PIBP7906297W4";
    const sucursalDireccion = venta.sucursal_direccion || "Dirección no disponible";

    let dataToPrint = [
      "\x1B\x40", // Reset
      "\x1B\x74\x11", // Codepage CP850
      "\x1B\x61\x01", // Center
      "\x1B\x21\x08", // Bold
      removeAccents(companyName) + "\x0A",
      "\x1B\x21\x00", // Normal
      removeAccents(companyRfc) + "\x0A",
      removeAccents(sucursalDireccion) + "\x0A",
      "\x0A",
      removeAccents("FACTURA") + "\x0A",
      removeAccents(`F${venta.id}`) + "\x0A",
      "\x0A",
      "\x1B\x61\x00", // Left align
      "\x1B\x21\x08", // Bold
      "ADQUIRIENTE\x0A",
      "\x1B\x21\x00", // Normal
      removeAccents(companyName) + "\x0A",
      removeAccents(companyRfc) + "\x0A",
      removeAccents(sucursalDireccion) + "\x0A",
      "\x0A",
      padLR("FECHA:", new Date(venta.fecha).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' })) + "\x0A",
      padLR("MONEDA:", "PESOS MEXICANOS") + "\x0A",
      padLR("Usuario:", venta.vendedor) + "\x0A",
      padLR("SUCURSAL:", venta.sucursal_nombre) + "\x0A",
      "-".repeat(ticketWidth) + "\x0A",
      padMultiple("CANT", "DESCRIPCION", "P/U", "TOTAL") + "\x0A",
      "-".repeat(ticketWidth) + "\x0A",
    ];

    items.forEach((item) => {
      let name = item.producto_nombre || "Producto";
      const availableWidth = 16;
      if (name.length > availableWidth) {
        name = name.substring(0, availableWidth);
      }
      dataToPrint.push(padMultiple(
        formatCurrencyForTicket(item.cantidad),
        name,
        formatCurrencyForTicket(item.precio_unitario),
        formatCurrencyForTicket(item.subtotal)
      ) + "\x0A");
    });

    dataToPrint.push("-".repeat(ticketWidth) + "\x0A");
    dataToPrint.push("\x1B\x61\x02"); // Right align
    dataToPrint.push(padLR("TOTAL $", formatCurrencyForTicket(venta.total)) + "\x0A");

    let totalRecibido = 0;
    if (venta.metodo_pago) {
      try {
        const payments = JSON.parse(venta.metodo_pago);
        if (Array.isArray(payments)) {
          payments.forEach(pago => {
            const monto = parseFloat(pago.amount) || 0;
            totalRecibido += monto;
            const etiqueta = (pago.method === "Efectivo") ? "Recibido" : pago.method;
            dataToPrint.push(padLR(`${etiqueta} $`, formatCurrencyForTicket(monto)) + "\x0A");
          });
        }
      } catch (e) {
        totalRecibido = parseFloat(venta.total) || 0;
        dataToPrint.push(padLR("Recibido $", formatCurrencyForTicket(totalRecibido)) + "\x0A");
      }
    }

    const cambio = totalRecibido - (parseFloat(venta.total) || 0);
    if (cambio > 0.005) {
      dataToPrint.push(padLR("Cambio $", formatCurrencyForTicket(cambio)) + "\x0A");
    }

    dataToPrint.push("\x0A");
    dataToPrint.push("\x1B\x61\x01"); // Center
    dataToPrint.push("Gracias por su Visita\x0A");
    dataToPrint.push("\x0A\x0A\x0A");
    dataToPrint.push("\x1D\x56\x41\x03"); // Cut

    try {
      await qz.print(config, dataToPrint);
      showToast("Ticket enviado a la impresora.", "success");
    } catch (err) {
      console.error("Error al imprimir:", err);
      showToast("Error al enviar el ticket a la impresora.", "error");
    }
  }

  async function printCashCutQZ(printerName, reportData) {
    if (!qzTrayConnected) {
      showToast("No se puede imprimir: QZ Tray está desconectado.", "error");
      return;
    }
    const config = qz.configs.create(printerName);

    const { sucursal, corte, cajaInicial, gastosDetalle, abonosDetalle, fechaCorte, usuario } = reportData;

    const totalIngresos = (cajaInicial || 0) + (corte.ventas_efectivo || 0) + (corte.abonos_clientes || 0) + (corte.ventas_tarjeta || 0);
    const totalEntregar = (cajaInicial || 0) + (corte.ventas_efectivo || 0) + (corte.abonos_clientes || 0) - (corte.total_gastos || 0);

    let dataToPrint = [
      "\x1B\x40", // Reset
      "\x1B\x74\x11", // Codepage
      "\x1B\x61\x01", // Center
      "\x1B\x21\x18", // Bold + Double Height
      "Mega Party\x0A",
      "\x1B\x21\x08", // Bold
      removeAccents(`Sucursal: ${sucursal.nombre}`) + "\x0A",
      "\x1B\x21\x00", // Normal
      removeAccents(sucursal.direccion || '') + "\x0A",
      removeAccents(`Teléf.:${sucursal.telefono || ''}`) + "\x0A",
      "\x0A",
      "\x1B\x61\x00", // Left
      padLR("Fecha del Corte:", new Date(fechaCorte + 'T00:00:00').toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })) + "\x0A",
      padLR("Generado el:", new Date().toLocaleString('es-MX')) + "\x0A",
      padLR("Usuario:", usuario) + "\x0A",
      "\x0A",
      "\x1B\x21\x08", // Bold
      padLR("Total Venta:", `$${formatCurrencyForTicket(corte.total_ventas)}`) + "\x0A",
      "\x1B\x21\x00", // Normal
      "\x0A",
      "Ingresos:\x0A",
      padLR("(+) Caja Inicial:", `$${formatCurrencyForTicket(cajaInicial)}`) + "\x0A",
      padLR("(+) Efectivo:", `$${formatCurrencyForTicket(corte.ventas_efectivo)}`) + "\x0A",
      padLR("(+) Abonos Clientes:", `$${formatCurrencyForTicket(corte.abonos_clientes)}`) + "\x0A",
      padLR("(+) Tarjetas:", `$${formatCurrencyForTicket(corte.ventas_tarjeta)}`) + "\x0A",
      "-".repeat(ticketWidth) + "\x0A",
      padLR("Total:", `$${formatCurrencyForTicket(totalIngresos)}`) + "\x0A",
      "\x0A",
      "Egresos\x0A"
    ];

    if (gastosDetalle && gastosDetalle.length > 0) {
      gastosDetalle.forEach(gasto => {
        let desc = gasto.descripcion || "Gasto";
        if (desc.length > ticketWidth - 14) desc = desc.substring(0, ticketWidth - 14);
        dataToPrint.push(padLR(removeAccents(desc), `$${formatCurrencyForTicket(gasto.monto)}`) + "\x0A");
      });
    } else {
      dataToPrint.push(padLR("Sin gastos registrados", "$0.00") + "\x0A");
    }

    dataToPrint.push("-".repeat(ticketWidth) + "\x0A");
    dataToPrint.push(padLR("Total:", `$${formatCurrencyForTicket(corte.total_gastos)}`) + "\x0A");
    dataToPrint.push("\x0A\x0A");
    dataToPrint.push("\x1B\x61\x01"); // Center
    dataToPrint.push("\x1B\x21\x18"); // Bold + Double Height
    dataToPrint.push(padLR("Total a entregar:", `$${formatCurrencyForTicket(totalEntregar)}`) + "\x0A");
    dataToPrint.push("\x0A\x0A\x0A");
    dataToPrint.push("\x1D\x56\x41\x03"); // Cut

    try {
      await qz.print(config, dataToPrint);
      showToast("Corte de caja enviado a la impresora.", "success");
    } catch (err) {
      console.error("Error al imprimir el corte de caja:", err);
      showToast("Error al enviar el corte de caja a la impresora.", "error");
    }
  }

  async function handlePrintTicket(saleId) {
    if (!printerConfig.isLoaded) {
      showToast("Cargando configuración de impresora, por favor espere...", "info");
      await getPrintPrefs();
    }

    showToast("Obteniendo datos del ticket...", "info");
    let ticketData;
    try {
      const response = await fetch(`${BASE_URL}/getTicketDetails?id=${saleId}`);
      const result = await response.json();
      if (!result.success) {
        showToast(result.message || "No se pudo obtener el ticket.", "error");
        return;
      }
      ticketData = result.data;
    } catch (err) {
      console.error("Error al obtener datos del ticket:", err);
      showToast("Error de conexión al obtener el ticket.", "error");
      return;
    }

    if (printMethod === 'service') {
      showToast("Enviando a servicio de impresión local...", "info");
      if (printerConfig.name) {
        ticketData.printerName = printerConfig.name;
      }
      try {
        const serviceResponse = await fetch('http://127.0.0.1:9898/imprimir', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ ...ticketData, type: 'ticket' })
        });
        if (serviceResponse.ok) {
          showToast("Ticket enviado al servicio local.", "success");
        } else {
          const errorText = await serviceResponse.text();
          showToast(`Error del servicio local: ${errorText}`, "error");
        }
      } catch (err) {
        console.warn("Servicio local no disponible:", err);
        showToast("Servicio local no encontrado. Verifique que esté en ejecución.", "error");
      }
    } else { // 'qztray'
      showToast("Enviando a QZ Tray...", "info");
      if (!printerConfig.name) {
        showToast("No hay una impresora configurada para QZ Tray.", "error");
        return;
      }
      await printTicketQZ(printerConfig.name, ticketData);
    }
  }

  async function handlePrintTicketViaDialog(saleId) {
    showToast("Preparando vista previa de impresión...", "info");
    try {
      const response = await fetch(`${BASE_URL}/getTicketDetails?id=${saleId}`);
      const result = await response.json();
      if (result.success) {
        const html = buildTicketHtml(result.data);
        openPrintDialogWithTicket(html, saleId);
      } else {
        showToast(result.message || "No se pudo obtener el ticket.", "error");
      }
    } catch (err) {
      console.error("Error al obtener datos del ticket para diálogo:", err);
      showToast("Error de conexión al preparar la vista previa.", "error");
    }
  }

  function handleViewPdf(saleId) {
    const pdfUrl = `${BASE_URL}/generateQuote?id=${saleId}`;
    window.open(pdfUrl, '_blank');
  }

  async function fetchCashCut() {
    const date = cashCutDateInput.value;
    if (!date) {
      showToast("Por favor, seleccione una fecha para el corte de caja.", "error");
      return;
    }
    cashCutResultsContainer.innerHTML = `<p class="text-[var(--color-text-secondary)] col-span-full">Calculando corte de caja...</p>`;
    let urlParams = `date=${date}`;
    if (userFilterSelect && userFilterSelect.value) {
      urlParams += `&user_id=${userFilterSelect.value}`;
    }
    try {
      const initialCashResponse = await fetch(`${BASE_URL}/getMontoApertura?${urlParams}`);
      const initialCashResult = await initialCashResponse.json();
      if (initialCashResult.success) {
        currentInitialCash = parseFloat(initialCashResult.monto_inicial || 0);
        initialCashInput.value = currentInitialCash.toFixed(2);
        if (currentInitialCash > 0) {
          initialCashInput.readOnly = true;
          initialCashInput.classList.add('opacity-75', 'cursor-not-allowed');
        } else {
          initialCashInput.readOnly = false;
          initialCashInput.classList.remove('opacity-75', 'cursor-not-allowed');
        }
      } else {
        showToast(initialCashResult.message || 'Error al obtener monto de apertura de caja.', 'error');
        currentInitialCash = 0;
        initialCashInput.value = '0.00';
        initialCashInput.readOnly = false;
        initialCashInput.classList.remove('opacity-75', 'cursor-not-allowed');
      }
      const response = await fetch(`${BASE_URL}/getCashCut?${urlParams}`);
      const result = await response.json();
      if (result.success) {
        currentCashCutData = result.data;
        renderCashCut(currentCashCutData, date, currentInitialCash);
      } else {
        cashCutResultsContainer.innerHTML = `<p class="text-red-500 col-span-full">${result.message}</p>`;
        currentCashCutData = null;
      }
    } catch (error) {
      console.error("Error fetching cash cut or initial cash:", error);
      cashCutResultsContainer.innerHTML = `<p class="text-red-500 col-span-full">No se pudo conectar con el servidor para el corte de caja.</p>`;
      currentCashCutData = null;
      currentInitialCash = 0;
      initialCashInput.value = '0.00';
      initialCashInput.readOnly = false;
      initialCashInput.classList.remove('opacity-75', 'cursor-not-allowed');
    }
  }

  async function fetchDetailedData(endpoint, date) {
    let urlParams = `date=${date}`;
    if (userFilterSelect) {
      urlParams += `&user_id=${userFilterSelect.value}`;
    }
    try {
      const response = await fetch(`${endpoint}?${urlParams}`);
      const result = await response.json();
      return result.success ? result.data : [];
    } catch (error) {
      console.error(`Error fetching detailed data from ${endpoint}:`, error);
      return [];
    }
  }

  async function cancelSale(saleId) {
    const confirmed = await showConfirm(`¿Está seguro de que desea CANCELAR la venta #${saleId.toString().padStart(6, "0")}?`);
    if (!confirmed) {
      showToast("Cancelación de venta abortada.", "info");
      return;
    }
    try {
      const response = await fetch(`${BASE_URL}/cancelSale`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_venta: saleId })
      });
      const result = await response.json();
      if (result.success) {
        showToast(result.message, "success");
        if (ventasDataTable) { ventasDataTable.ajax.reload(null, false); }
      } else {
        showToast(result.message, "error");
      }
    } catch (error) {
      console.error("Error cancelling sale:", error);
      showToast("Error de conexión al cancelar la venta.", "error");
    }
  }

  function escapeHtml(str = "") {
    return String(str).replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
  }

  function formatCurrencyMXN(value) {
    return "$" + parseFloat(value || 0).toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function buildTicketHtml(ticketData) {
    const venta = ticketData.venta || {};
    const items = ticketData.items || [];
    const docLabel = String((ticketData.venta || {}).estado || '').toLowerCase() === 'completada' ? 'Factura' : 'Proforma';
    const itemsRows = items.map(it => `
      <tr>
        <td class="qty">${escapeHtml(String(it.cantidad))}</td>
        <td class="desc">${escapeHtml(it.producto_nombre || "")}${it.sku ? `<div class="sku">SKU: ${escapeHtml(it.sku)}</div>` : ""}</td>
        <td class="monto">${formatCurrencyMXN(it.subtotal)}</td>
      </tr>`).join("");
    return `
    <div class="ticket">
      <h1>${escapeHtml(venta.sucursal_nombre || "")}</h1>
      <div class="sub">${escapeHtml(venta.sucursal_direccion || "")}<br>Tel: ${escapeHtml(venta.sucursal_telefono || "")}</div>
      <hr>
      <div class="row"><span>${docLabel}:</span><span>#${String(venta.id || 0).padStart(6, "0")}</span></div>
      <div class="row"><span>Fecha:</span><span>${new Date(venta.fecha).toLocaleString("es-MX")}</span></div>
      <div class="row"><span>Cliente:</span><span>${escapeHtml(venta.cliente || "")}</span></div>
      <div class="row"><span>Vendedor:</span><span>${escapeHtml(venta.vendedor || "")}</span></div>
      <hr>
      <table class="items">
        <thead><tr><th class="qty">Cant</th><th class="desc">Descripción</th><th class="monto">Total</th></tr></thead>
        <tbody>${itemsRows || `<tr><td colspan="3" class="center">Sin artículos</td></tr>`}</tbody>
      </table>
      <hr>
      <div class="row total"><span>TOTAL</span><span>${formatCurrencyMXN(venta.total)}</span></div>
      <p class="center gracias">¡Gracias por su compra!</p>
    </div>`;
  }

  function openPrintDialogWithTicket(html, ventaId) {
    const w = window.open("", "PRINT", "width=420,height=640");
    if (!w) {
      showToast("Popup bloqueado. Permite ventanas emergentes para imprimir.", "error");
      return;
    }
    w.document.write(`
    <!doctype html><html><head><meta charset="utf-8"><title>Ticket #${String(ventaId).padStart(6, "0")}</title><style>@page{size:80mm auto;margin:0;}@media print{html,body{margin:0;padding:0;}}html,body{background:#fff;}.ticket{width:80mm;box-sizing:border-box;padding:4mm 3mm;font-family:Tahoma,Arial,Helvetica,sans-serif;font-size:12px;color:#000;font-weight:600;text-rendering:optimizeLegibility;}h1{margin:0 0 2px 0;font-family:"Arial Black",Tahoma,Arial,Helvetica,sans-serif;font-size:15px;font-weight:900;text-align:center;letter-spacing:.2px;text-transform:uppercase;text-shadow:0 0 .3px #000,0 0 .3px #000;-webkit-text-stroke:.15px #000;}.sub{text-align:center;margin-bottom:6px;}.row{display:flex;justify-content:space-between;gap:8px;}.total{font-weight:700;font-size:13px;}hr{border:0;border-top:1px dashed #000;margin:6px 0;}.center{text-align:center;}.gracias{margin-top:8px;}table.items{width:100%;border-collapse:collapse;}table.items th{text-align:left;font-weight:700;border-bottom:1px solid #000;padding-bottom:2px;}table.items td{vertical-align:top;}.qty{width:10mm;white-space:nowrap;}.desc{width:auto;padding:0 4px;}.desc .sku{font-size:11px;margin-top:2px;opacity:.8;}.monto{text-align:right;white-space:nowrap;width:18mm;}</style></head><body>${html}<script>window.onload=function(){try{window.focus();window.print();}catch(e){}};<\/script></body></html>`);
    w.document.close();
  }

  function renderCashCut(data, date, initialCash) {
    const formatCurrency = (value) => `$${parseFloat(value || 0).toFixed(2)}`;
    const totalIngresosEfectivo = parseFloat(data.ventas_efectivo || 0) + parseFloat(data.abonos_clientes || 0);
    const balanceFinal = initialCash + totalIngresosEfectivo - parseFloat(data.total_gastos || 0);

    cashCutResultsContainer.innerHTML = `
            <div class="bg-[var(--color-bg-primary)] p-4 rounded-lg shadow-inner flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-3 flex items-center"><i class="fas fa-arrow-alt-circle-down text-green-400 mr-2"></i> Ingresos</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center"><span>Caja Inicial:</span><span class="font-mono text-blue-300">${formatCurrency(initialCash)}</span></div>
                        <div class="flex justify-between items-center"><span>Ventas en Efectivo:</span><span class="font-mono text-green-300">${formatCurrency(data.ventas_efectivo)}</span></div>
                        <div class="flex flex-col">
                            <div class="flex justify-between items-center cursor-pointer hover:text-gray-200" id="toggle-abonos"><span>Abonos de Clientes:</span><span class="font-mono text-green-300">${formatCurrency(data.abonos_clientes)} <i class="fas fa-chevron-down ml-2 transition-transform duration-300"></i></span></div>
                            <div id="abonos-detail" class="mt-2 pl-4 text-xs text-[var(--color-text-secondary)] hidden"><p>Cargando abonos...</p></div>
                        </div>
                        <hr class="border-[var(--color-border)] my-2">
                        <div class="flex justify-between items-center font-bold text-base"><span>Total Ingresos en Caja:</span><span class="font-mono text-green-400">${formatCurrency(totalIngresosEfectivo)}</span></div>
                    </div>
                </div>
            </div>
            <div class="bg-[var(--color-bg-primary)] p-4 rounded-lg shadow-inner flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-3 flex items-center"><i class="fas fa-arrow-alt-circle-up text-red-400 mr-2"></i> Egresos</h3>
                    <div class="space-y-3 text-sm mb-4">
                        <div class="flex flex-col">
                            <div class="flex justify-between items-center cursor-pointer hover:text-gray-200" id="toggle-gastos"><span>Total de Gastos:</span><span class="font-mono text-red-300">${formatCurrency(data.total_gastos)} <i class="fas fa-chevron-down ml-2 transition-transform duration-300"></i></span></div>
                            <div id="gastos-detail" class="mt-2 pl-4 text-xs text-[var(--color-text-secondary)] hidden"><p>Cargando gastos...</p></div>
                        </div>
                    </div>
                    <hr class="border-[var(--color-border)] my-2">
                    <div class="flex justify-between items-center pt-4 text-lg font-bold"><span class="text-[var(--color-text-primary)]">Balance Final en Caja:</span><span class="font-mono ${balanceFinal >= 0 ? "text-green-400" : "text-red-400"}">${formatCurrency(balanceFinal)}</span></div>
                </div>
            </div>
            <div class="col-span-full bg-[var(--color-bg-primary)] p-4 rounded-lg shadow-inner mt-4">
                <h3 class="text-md font-semibold text-[var(--color-text-secondary)] mb-3 flex items-center"><i class="fas fa-info-circle text-blue-400 mr-2"></i> Otros Totales (Informativos)</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-2 text-xs">
                    <div class="flex justify-between items-center"><span>Total Ventas (Todos los métodos):</span><span class="font-mono text-[var(--color-text-secondary)]">${formatCurrency(data.total_ventas)}</span></div>
                    <div class="flex justify-between items-center"><span>Ventas con Tarjeta:</span><span class="font-mono text-[var(--color-text-secondary)]">${formatCurrency(data.ventas_tarjeta)}</span></div>
                    <div class="flex justify-between items-center"><span>Ventas por Transferencia:</span><span class="font-mono text-[var(--color-text-secondary)]">${formatCurrency(data.ventas_transferencia)}</span></div>
                    <div class="flex justify-between items-center"><span>Ventas a Crédito:</span><span class="font-mono text-[var(--color-text-secondary)]">${formatCurrency(data.ventas_credito)}</span></div>
                </div>
            </div>`;

    const toggleGastos = document.getElementById("toggle-gastos");
    const gastosDetail = document.getElementById("gastos-detail");
    const toggleAbonos = document.getElementById("toggle-abonos");
    const abonosDetail = document.getElementById("abonos-detail");

    if (toggleGastos && gastosDetail) {
      toggleGastos.addEventListener("click", async () => {
        gastosDetail.classList.toggle("hidden");
        toggleGastos.querySelector("i").classList.toggle("rotate-180");
        if (!gastosDetail.classList.contains("hidden") && gastosDetail.dataset.loaded !== "true") {
          const expenses = await fetchDetailedData(`${BASE_URL}/getDetailedExpenses`, date);
          gastosDetail.innerHTML = "";
          if (expenses.length > 0) {
            expenses.forEach((exp) => {
              const p = document.createElement("p");
              p.className = "flex justify-between items-center py-1 border-b border-[var(--color-border)] last:border-b-0";
              p.innerHTML = `<span>${new Date(exp.fecha).toLocaleDateString('es-MX')} - ${exp.descripcion.substring(0, 30)}${exp.descripcion.length > 30 ? "..." : ""}</span><span class="font-mono text-red-300">${formatCurrency(exp.monto)}</span>`;
              gastosDetail.appendChild(p);
            });
          } else {
            gastosDetail.innerHTML = '<p class="text-center py-2">No se encontraron gastos.</p>';
          }
          gastosDetail.dataset.loaded = "true";
        }
      });
    }

    if (toggleAbonos && abonosDetail) {
      toggleAbonos.addEventListener("click", async () => {
        abonosDetail.classList.toggle("hidden");
        toggleAbonos.querySelector("i").classList.toggle("rotate-180");
        if (!abonosDetail.classList.contains("hidden") && abonosDetail.dataset.loaded !== "true") {
          const payments = await fetchDetailedData(`${BASE_URL}/getDetailedClientPayments`, date);
          abonosDetail.innerHTML = "";
          if (payments.length > 0) {
            payments.forEach((pay) => {
              const p = document.createElement("p");
              p.className = "flex justify-between items-center py-1 border-b border-[var(--color-border)] last:border-b-0";
              p.innerHTML = `<span>${new Date(pay.fecha).toLocaleString('es-MX')} - ${pay.cliente_nombre} (${pay.metodo_pago})</span><span class="font-mono text-green-300">${formatCurrency(pay.monto)}</span>`;
              abonosDetail.appendChild(p);
            });
          } else {
            abonosDetail.innerHTML = '<p class="text-center py-2">No se encontraron abonos.</p>';
          }
          abonosDetail.dataset.loaded = "true";
        }
      });
    }
  }

  async function printCashCutReport() {
    if (!printerConfig.isLoaded) {
      showToast("Cargando configuración de impresora, por favor espere...", "info");
      await getPrintPrefs();
    }

    if (!currentCashCutData) {
      showToast("No hay datos de corte de caja para imprimir.", "error");
      return;
    }

    showToast("Recopilando detalles del reporte...", "info");
    const dateForPrint = cashCutDateInput.value;
    const detailedExpenses = await fetchDetailedData(`${BASE_URL}/getDetailedExpenses`, dateForPrint);
    const detailedClientPayments = await fetchDetailedData(`${BASE_URL}/getDetailedClientPayments`, dateForPrint);

    const reportData = {
      sucursal: sucursalActual,
      corte: currentCashCutData,
      cajaInicial: currentInitialCash,
      gastosDetalle: detailedExpenses,
      abonosDetalle: detailedClientPayments,
      fechaCorte: dateForPrint,
      usuario: (userFilterSelect && userFilterSelect.value && userFilterSelect.value !== 'all')
        ? userFilterSelect.options[userFilterSelect.selectedIndex].text
        : (currentUserName || 'Usuario')

    };

    if (printMethod === 'service') {
      showToast("Enviando corte de caja al servicio local...", "info");
      reportData.type = 'corte';
      reportData.printerName = printerConfig.name;

      try {
        const serviceResponse = await fetch('http://127.0.0.1:9898/imprimir', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(reportData)
        });
        if (serviceResponse.ok) {
          showToast("Corte de caja enviado al servicio local.", "success");
        } else {
          const errorText = await serviceResponse.text();
          showToast(`Error del servicio local: ${errorText}`, "error");
        }
      } catch (err) {
        console.warn("Servicio local no disponible:", err);
        showToast("Servicio local no encontrado. Verifique que esté en ejecución.", "error");
      }

    } else { // 'qztray'
      showToast("Enviando corte de caja a QZ Tray...", "info");
      if (!printerConfig.name) {
        showToast("Impresora no configurada para QZ Tray.", "error");
        return;
      }
      await printCashCutQZ(printerConfig.name, reportData);
    }
  }

  async function initializePage() {
    const now = new Date();
    const todayFormatted = `${now.getFullYear()}-${(now.getMonth() + 1).toString().padStart(2, "0")}-${now.getDate().toString().padStart(2, "0")}`;
    if (cashCutDateInput) cashCutDateInput.value = todayFormatted;

    await fetchSucursalDetails();
    initSalesDataTable();
    await loadUsersForFilter();
    await fetchCashCut();
    await getPrintPrefs();
  }

  // Event Listeners
  if (generateCashCutBtn) generateCashCutBtn.addEventListener("click", fetchCashCut);
  if (printCashCutBtn) printCashCutBtn.addEventListener("click", printCashCutReport);
  if (userFilterSelect) {
    userFilterSelect.addEventListener('change', fetchCashCut);
  }

  initializePage();
});
