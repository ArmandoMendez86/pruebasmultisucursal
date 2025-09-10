// Archivo: /public/js/qz-tray-handler.js

// ... (código existente: qzTrayConnected, connectQz, updateQzStatus, findPrinters, etc.) ...
let qzTrayConnected = false;
qz.api.setPromiseType(function promise(resolver) {
  return new Promise(resolver);
});
qz.api.setSha256Type(function (data) {
  return sha256(data);
});
async function connectQz() {
  if (qz.websocket.isActive()) {
    console.log("QZ Tray ya está conectado.");
    updateQzStatus(true);
    return;
  }
  try {
    await qz.websocket.connect();
    console.log("¡Conectado a QZ Tray!");
    qzTrayConnected = true;
    updateQzStatus(true);
    showToast("Conectado a QZ Tray.", "success");
  } catch (err) {
    console.error("Error al conectar con QZ Tray:", err);
    qzTrayConnected = false;
    updateQzStatus(false);
    showToast(
      "No se pudo conectar con QZ Tray. Asegúrate de que esté en ejecución.",
      "error"
    );
  }
}
function updateQzStatus(isConnected) {
  const statusElem = document
    .getElementById("qz-status")
    ?.querySelector("span");
  if (statusElem) {
    statusElem.textContent = isConnected ? "Conectado" : "Desconectado";
    statusElem.className = isConnected
      ? "font-semibold text-green-400"
      : "font-semibold text-red-400";
  }
}
async function findPrinters(selectElement, selectedPrinter = "") {
  if (!qzTrayConnected) {
    showToast("Primero conecta con QZ Tray.", "error");
    await connectQz();
    if (!qzTrayConnected) return;
  }
  try {
    const printers = await qz.printers.find();
    selectElement.innerHTML =
      '<option value="">-- Selecciona una impresora --</option>';
    printers.forEach((printer) => {
      const option = document.createElement("option");
      option.value = printer;
      option.textContent = printer;
      if (printer === selectedPrinter) {
        option.selected = true;
      }
      selectElement.appendChild(option);
    });
    showToast("Lista de impresoras actualizada.", "success");
  } catch (err) {
    console.error("Error al buscar impresoras:", err);
    showToast("No se pudieron encontrar impresoras.", "error");
  }
}
window.addEventListener("beforeunload", () => {
  if (qz.websocket.isActive()) {
    qz.websocket.disconnect();
  }
});

/**
 * --- FUNCIÓN ACTUALIZADA Y MEJORADA ---
 * Formatea y envía los datos de un ticket a la impresora seleccionada.
 * @param {string} printerName - El nombre de la impresora.
 * @param {object} ticketData - Los datos de la venta obtenidos de la API.
 */
async function printTicket(printerName, ticketData) {
  if (!qzTrayConnected) {
    showToast("No se puede imprimir: QZ Tray está desconectado.", "error");
    return;
  }

  const config = qz.configs.create(printerName);
  const ticketWidth = 32; // Ancho estándar para tickets de 58mm

  // --- Funciones de ayuda para el formato ---
  const removeAccents = (str) =>
    str ? str.normalize("NFD").replace(/[\u0300-\u036f]/g, "") : "";

  /**
   * --- NUEVA FUNCIÓN DE FORMATO DE MONEDA ---
   * Formatea un número como moneda (ej. $1,500.00)
   */
  const formatCurrencyForTicket = (value) => {
    return (
      "$" +
      parseFloat(value).toLocaleString("es-MX", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  };

  const formatLine = (left, right = "", width = ticketWidth) => {
    const leftClean = removeAccents(left);
    const rightClean = removeAccents(right);
    const padding = Math.max(0, width - leftClean.length - rightClean.length);
    return leftClean + " ".repeat(padding) + rightClean + "\x0A";
  };

  // --- Construcción del Ticket ---
  let dataToPrint = [
    "\x1B" + "\x40", // Inicializar impresora
    "\x1B" + "\x74" + "\x11", // Seleccionar página de códigos PC850 (Español)

    "\x1B" + "\x61" + "\x31", // Centrar
    "\x1B" + "\x21" + "\x10", // Negrita y doble altura
    removeAccents(ticketData.venta.sucursal_nombre) + "\x0A",
    "\x1B" + "\x21" + "\x00", // Texto normal
    removeAccents(ticketData.venta.sucursal_direccion) + "\x0A",
    "Tel: " + ticketData.venta.sucursal_telefono + "\x0A",
    "\x0A",

    "\x1B" + "\x61" + "\x30", // Alinear a la izquierda
    formatLine(
      "Ticket:",
      "#" + ticketData.venta.id.toString().padStart(6, "0")
    ),
    formatLine(
      "Fecha:",
      new Date(ticketData.venta.fecha).toLocaleString("es-MX")
    ),
    formatLine("Cliente:", ticketData.venta.cliente),
    formatLine("Vendedor:", ticketData.venta.vendedor),
    "-".repeat(ticketWidth) + "\x0A",
    formatLine("Cant Descripcion", "Total"),
    "-".repeat(ticketWidth) + "\x0A",
  ];

  // Añadir items al ticket
  ticketData.items.forEach((item) => {
    // 1. Prepara las partes de la línea: cantidad y precio.
    const qtyPart = `${item.cantidad} `;
    const pricePart = formatCurrencyForTicket(item.subtotal);

    // 2. Calcula el espacio máximo disponible para el nombre del producto.
    // Restamos la longitud de la cantidad, el precio y un espacio de separación.
    const maxNameLength = ticketWidth - qtyPart.length - pricePart.length - 1;

    // 3. Trunca el nombre del producto si es necesario.
    let productName = item.producto_nombre;
    if (productName.length > maxNameLength) {
      // Acorta y añade "..." para indicar que está truncado.
      productName = productName.substring(0, maxNameLength - 3) + "...";
    }

    // 4. Construye y añade la línea principal del producto.
    const mainLine = qtyPart + productName;
    dataToPrint.push(formatLine(mainLine, pricePart));

    // 5. (Opcional pero recomendado) Añade el SKU en una nueva línea.
    // Asegúrate de que tu API envía 'item.sku' o un campo similar.
    if (item.sku) {
      // Añadimos un pequeño sangrado para mayor claridad.
      dataToPrint.push(formatLine(`  SKU: ${item.sku}`));
    }
  });

  dataToPrint.push("-".repeat(ticketWidth) + "\x0A");
  dataToPrint.push("\x1B" + "\x61" + "\x32"); // Alinear a la derecha
  dataToPrint.push("\x1B" + "\x21" + "\x08"); // Negrita
  dataToPrint.push(
    formatLine("TOTAL: ", formatCurrencyForTicket(ticketData.venta.total))
  );
  dataToPrint.push("\x1B" + "\x21" + "\x00"); // Texto normal
  dataToPrint.push("\x0A");

  dataToPrint.push("\x1B" + "\x61" + "\x31"); // Centrar
  dataToPrint.push(removeAccents("¡Gracias por su compra!") + "\x0A");
  dataToPrint.push("\x0A" + "\x0A" + "\x0A");
  dataToPrint.push("\x1D" + "\x56" + "\x41" + "\x03"); // Cortar papel

  try {
    await qz.print(config, dataToPrint);
    showToast("Ticket enviado a la impresora.", "success");
  } catch (err) {
    console.error("Error al imprimir:", err);
    showToast("Error al enviar el ticket a la impresora.", "error");
  }
}
