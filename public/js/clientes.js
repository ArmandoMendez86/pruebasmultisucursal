document.addEventListener("DOMContentLoaded", function () {
  // --- CONFIG ---
  const NOMINATIM_EMAIL = "armando.mendez.dev@gmail.com"; // requerido por OSM
  const NOMINATIM_BASE = "https://nominatim.openstreetmap.org/search";
  const CP_API_BASE = "https://api.zippopotam.us/MX";

  // --- ESTADO/UI GLOBAL ---
  let dataTableInstance;
  let montoAbonoAn;
  let limiteCreditoAn;
  let searchTimeout;
  let specialPriceInstances = [];
  let nominatimAbortController = null;

  // --- DOM ROOTS ---
  const clientModal = document.getElementById("client-modal");
  const clientForm = document.getElementById("client-form");
  const specialPricesContainer = document.getElementById("special-prices-container");
  const productSearchInput = document.getElementById("product-search-input");
  const productSearchResults = document.getElementById("product-search-results");
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabContents = document.querySelectorAll(".tab-content");

  //Formato de envio
  const obsEnvioInput = document.getElementById("obs_envio");
  const previewBox = document.getElementById("shipping-preview");
  const previewBtn = document.getElementById("preview-shipping-btn");
  const printBtn = document.getElementById("print-shipping-btn");



  // --- DATATABLE ---
  dataTableInstance = jQuery('#clientesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: { url: `${BASE_URL}/listClients`, type: 'POST' },
    columns: [
      { data: 'nombre' },
      { data: 'telefono' },
      { data: 'email' },
      { data: 'tipo' }, // NUEVA
      { data: 'deuda_actual', className: 'text-right' },
      { data: 'acciones', orderable: false, searchable: false, className: 'text-center' }
    ],

    lengthChange: true,
    pageLength: 15,
    lengthMenu: [[15, 30, 60], [15, 30, 60]],
    columnDefs: [{
      targets: 4,
      render: function (data, type) {
        if (type === 'display') {
          const n = parseFloat(data) || 0;
          const fmt = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(n);
          return `<span class="font-mono ${n > 0 ? 'text-red-500' : 'text-green-500'}">${fmt}</span>`;
        }
        return data;
      }
    }],
    dom: "<'flex justify-between'lf><'clear'><'flex justify-center mb-8'B>rtip",
    buttons: [
      { extend: 'copyHtml5', text: 'Copiar', exportOptions: { columns: [0, 1, 2, 3, 4] } },
      { extend: 'excelHtml5', title: 'Clientes', exportOptions: { columns: [0, 1, 2, 3, 4] } },
      { extend: 'csvHtml5', title: 'Clientes', exportOptions: { columns: [0, 1, 2, 3, 4] } },
      { extend: 'pdfHtml5', title: 'Clientes', exportOptions: { columns: [0, 1, 2, 3, 4] } },
      { extend: 'print', text: 'Imprimir', exportOptions: { columns: [0, 1, 2, 3, 4] } }
    ],

    language: { url: 'js/es.json' },
    order: [[0, 'asc']]
  });

  // --- AutoNumeric ---
  const autoNumericOptions = {
    currencySymbol: '$', currencySymbolPlacement: 'p',
    decimalCharacter: '.', digitGroupSeparator: ',',
    decimalPlaces: 2, minimumValue: '0'
  };
  const autoNumericPriceOptions = { ...autoNumericOptions, currencySymbol: '' };


  // === GEO === Geocodifica una dirección usando Nominatim (con limpieza y fallback)
  async function geocodeAddressStructured({ direccion, ciudad, estado, codigo_postal }) {
    // 0) Limpieza avanzada de 'street'
    const cleanStreet = (direccion || "")
      .replace(/\[[^\]]*\]/g, "")      // 1. Quita [ ... ]
      .replace(/\s+\|\s+/g, " ")      // 2. Quita ' | '
      .replace(/\bcol\.?\b/ig, "")     // 3. Quita "Col." / "col" (ya lo tenías)
      .replace(/\s{2,}/g, " ")         // 4. Espacios dobles
      // --- NUEVAS REGLAS DE LIMPIEZA PARA NOMINATIM ---
      .replace(/\b(núm|num|no|#)\b/ig, "") // 5. Quita abreviaturas de número (ej: "Num. 123" -> "123")
      .replace(/,\s?.*$/ig, "")        // 6. ¡CRUCIAL! Quita todo después de la primera coma
      .trim();

    // 1) Intentos: Definición de parámetros
    const baseParams = {
      format: "jsonv2",
      addressdetails: "0",
      limit: "1",
      dedupe: "1",
      countrycodes: "mx",
      "accept-language": "es",
      email: NOMINATIM_EMAIL || ""
    };

    // --- PRIMER INTENTO: ESTRUCTURADO Y COMPLETO ---
    let params1 = new URLSearchParams(baseParams);

    // Si la calle no contiene un número, es muy probable que falle.
    // Nominatim prefiere que el número de casa esté en el campo 'street' junto a la calle.
    if (cleanStreet) params1.set("street", cleanStreet);
    if (codigo_postal) params1.set("postalcode", codigo_postal);
    if (ciudad) params1.set("city", ciudad);
    if (estado) params1.set("state", estado);

    let data = [];
    if (cleanStreet && codigo_postal) { // Solo si tenemos CALLE y CP, intentamos la búsqueda estructurada
      const r1 = await fetch(`${NOMINATIM_BASE}?${params1.toString()}`, { headers: { "x-nominatim": "1" } });
      if (r1.ok) data = await r1.json(); else { console.warn("Nominatim error (Intento 1)", r1.status); }
    }

    // --- SEGUNDO INTENTO (FALLBACK): BÚSQUEDA LIBRE CON LA MÁXIMA INFORMACIÓN ---
    if (!Array.isArray(data) || !data.length) {
      const q = [
        cleanStreet,
        codigo_postal, // Ponemos el CP primero para anclar la búsqueda
        ciudad,
        estado,
        "México"
      ].filter(Boolean).join(", ");

      if (q) {
        const p2 = new URLSearchParams(baseParams);
        p2.set("q", q);

        const r2 = await fetch(`${NOMINATIM_BASE}?${p2.toString()}`);
        if (r2.ok) data = await r2.json(); else { console.warn("Nominatim fallback error (Intento 2)", r2.status); }
      }
    }

    // --- TERCER INTENTO (AGRESIVO): SOLO CALLE Y CÓDIGO POSTAL ---
    // Este intento es útil si la ciudad o estado ingresados son incorrectos o ambiguos.
    if (!Array.isArray(data) || !data.length) {
      const q_cp_only = [cleanStreet, codigo_postal, "México"].filter(Boolean).join(", ");
      if (q_cp_only) {
        const p3 = new URLSearchParams(baseParams);
        p3.set("q", q_cp_only);

        const r3 = await fetch(`${NOMINATIM_BASE}?${p3.toString()}`);
        if (r3.ok) data = await r3.json(); else { console.warn("Nominatim fallback error (Intento 3)", r3.status); }
      }
    }


    // 4) Retorno de coordenadas
    if (!Array.isArray(data) || !data.length) return null;
    const lat = parseFloat(data[0].lat);
    const lon = parseFloat(data[0].lon);

    if (Number.isFinite(lat) && Number.isFinite(lon)) return { latitud: lat, longitud: lon };
    return null;
  }

  // Geocodifica cada dirección y rellena latitud/longitud (como string para no romper tu backend)
  async function geocodeDirecciones(direcciones) {
    if (!Array.isArray(direcciones)) return;
    for (const d of direcciones) {
      // si ya vienen coords, respétalas
      if (d.latitud && d.longitud) continue;

      const res = await geocodeAddressStructured(d);
      d.latitud = res ? String(res.latitud) : "";   // deja "" si no hay match
      d.longitud = res ? String(res.longitud) : "";

      // respiro para Nominatim
      await new Promise(rs => setTimeout(rs, 300));
    }
  }


  // --- Tabs ---
  function switchTab(target) {
    tabContents.forEach(c => c.classList.remove('active'));
    tabButtons.forEach(b => b.classList.remove('active'));
    document.getElementById(`tab-content-${target}`).classList.add('active');
    document.querySelector(`.tab-button[data-tab="${target}"]`).classList.add('active');
  }
  tabButtons.forEach(b => b.addEventListener('click', () => switchTab(b.dataset.tab)));

  // --- Modal cliente ---
  const showModal = () => clientModal.classList.remove("hidden");
  const hideModal = () => {
    specialPriceInstances.forEach(i => i.remove());
    specialPriceInstances = [];
    clientModal.classList.add("hidden");
  };

  async function prepareNewClientForm() {
    clientForm.reset();
    await loadClientTypes();
    document.getElementById("id_tipo").value = ""; // queda en blanco; backend usará 1 si lo dejas vacío

    if (limiteCreditoAn) limiteCreditoAn.clear();
    document.getElementById("client-id").value = "";
    document.getElementById("modal-title").textContent = "Añadir Nuevo Cliente";
    document.getElementById("limite-credito-container").classList.add("hidden");
    document.getElementById("addresses-container").innerHTML = "";
    addAddressRow();
    specialPricesContainer.innerHTML = `<p class="text-center text-[var(--color-text-secondary)] placeholder-text">Busque y añada productos para asignar precios especiales.</p>`;
    productSearchInput.value = ''; productSearchResults.innerHTML = ''; productSearchResults.classList.add('hidden');
    if (obsEnvioInput) obsEnvioInput.value = "";
    if (previewBox) previewBox.innerHTML = '<p class="text-sm text-gray-600">Genera una vista previa…</p>';

    switchTab('personales'); showModal();
  }

  async function handleEditClient(id) {
    await loadClientTypes();

    try {
      const r = await fetch(`${BASE_URL}/getClient?id=${id}`);
      const result = await r.json();
      if (!result.success) { showToast(result.message, "error"); return; }
      const c = result.data;
      document.getElementById("client-id").value = c.id;
      document.getElementById("nombre").value = c.nombre;
      document.getElementById("rfc").value = c.rfc;
      document.getElementById("telefono").value = c.telefono;
      document.getElementById("email").value = c.email;
      document.getElementById("tiene_credito").checked = c.tiene_credito == 1;
      document.getElementById("limite-credito-container").classList.toggle("hidden", c.tiene_credito != 1);
      document.getElementById("id_tipo").value = (c.id_tipo ?? 1).toString();
      document.getElementById("obs_envio").value = c.obs_envio || "";
      renderShippingPreview();  // opcional


      if (limiteCreditoAn) limiteCreditoAn.set(c.limite_credito);
      document.getElementById("modal-title").textContent = "Editar Cliente";

      specialPricesContainer.innerHTML = '';
      if (c.precios_especiales?.length) {
        c.precios_especiales.forEach(sp => addProductToSpecialList({
          id: sp.id_producto, nombre: sp.nombre, sku: sp.sku, precio_menudeo: sp.precio_menudeo
        }, sp.precio_especial));
      } else {
        specialPricesContainer.innerHTML = `<p class="text-center text-[var(--color-text-secondary)] placeholder-text">Busque y añada productos para asignar precios especiales.</p>`;
      }

      document.getElementById("addresses-container").innerHTML = "";
      if (c.direcciones?.length) c.direcciones.forEach(a => addAddressRow(a));
      else addAddressRow();

      switchTab('personales'); showModal();
    } catch { showToast("No se pudieron obtener los datos del cliente.", "error"); }
  }

  // --- Guardado ---
  async function handleFormSubmit(e) {
    e.preventDefault();
    const fd = new FormData(clientForm);
    let validacionFallida = false; // Declarada aquí
    let clientData = {};

    // 1. Recolección de datos generales
    for (let [k, v] of fd.entries()) {
      if (!k.startsWith('direccion') && !k.startsWith('ciudad') && !k.startsWith('estado') && !k.startsWith('codigo_postal') && !k.startsWith('principal')) clientData[k] = v;
    }
    clientData.tiene_credito = document.getElementById("tiene_credito").checked;
    // asegura número, nunca cadena vacía
    if (clientData.tiene_credito) {
      const v = limiteCreditoAn ? limiteCreditoAn.getNumericString() : "";
      clientData.limite_credito = v && !isNaN(v) ? v : "0";
    } else {
      clientData.limite_credito = "0";
    }

    // 2. Recolección y Validación de Direcciones
    clientData.direcciones = [];
    document.querySelectorAll("#addresses-container .address-row").forEach(row => {
      // Selección manual o de lista
      const ciudadSel = row.querySelector('[name="ciudad"]');
      const estadoSel = row.querySelector('[name="estado"]');
      const ciudad = ciudadSel.value === "__manual__" ? row.querySelector('input[name="ciudad_manual"]').value.trim() : ciudadSel.value.trim();
      const estado = estadoSel.value === "__manual__" ? row.querySelector('input[name="estado_manual"]').value.trim() : estadoSel.value.trim();

      // VALIDACIÓN DE CAMPOS REQUERIDOS
      const dirEl = row.querySelector('textarea[name="direccion"]');
      const datoDireccion = (dirEl.value || "").split("\n")[0].split("[")[0].trim();
      const codigoPostal = row.querySelector('input[name="codigo_postal"]').value.trim();

      if (!datoDireccion || !codigoPostal || !ciudad || !estado) {
        validacionFallida = true;
        return; // Sale de la iteración actual del forEach
      }

      // Extras → se agregan al texto para no tocar el backend
      const colonia = row.querySelector('[name="colonia"]')?.value.trim() || '';
      const municipio = row.querySelector('input[name="municipio"]')?.value.trim() || '';
      const entre1 = row.querySelector('input[name="entre1"]')?.value.trim() || '';
      const entre2 = row.querySelector('input[name="entre2"]')?.value.trim() || '';
      const refs = row.querySelector('input[name="referencias"]')?.value.trim() || '';

      // Tomar SOLO calle y número (primera línea) y quitar cualquier bloque entre corchetes si existiera
      let direccion = (dirEl.value || "").split("\n")[0].split("[")[0].trim();
      let direccion_respaldo = row.querySelector('textarea[name="direccion_respaldo"]').value.trim();

      clientData.direcciones.push({
        direccion, // limpio
        ciudad,
        estado,
        codigo_postal: codigoPostal, // Usamos la variable limpia
        principal: row.querySelector('input[name="principal"]').checked ? 1 : 0,
        direccion_respaldo
      });

    });

    // 3. Validación Final Síncrona (detiene si faltan campos)
    if (validacionFallida) {
      showToast("❌ Faltan campos requeridos, verifica (Calle, C.P., Ciudad o Estado).", "error");
      return; // Detiene la ejecución
    }

    // 4. Recolección de datos adicionales
    clientData.precios = {};
    specialPriceInstances.forEach(inst => {
      const val = inst.getNumericString();
      if (val && parseFloat(val) > 0) clientData.precios[inst.domElement.dataset.productId] = val;
    });
    // después de construir clientData a partir de FormData:
    clientData.id_tipo = clientData.id_tipo && !isNaN(clientData.id_tipo) ? clientData.id_tipo : "1";
    clientData.obs_envio = (document.getElementById("obs_envio")?.value || "").trim();

    // --- LOG opcional ---
    console.log("Cliente ANTES de geocodificar:", JSON.parse(JSON.stringify(clientData)));

    // -----------------------------------------------------
    // 5. INICIO DE LOADER Y BLOQUE ASÍNCRONO
    // -----------------------------------------------------
    showLoader(); // Muestra el loader

    try {
      // Validaciones mínimas antes de geocodificar (Mantenemos el filtro original)
      clientData.direcciones = clientData.direcciones.filter(d => {
        const okStreet = (d.direccion || "").trim().length > 0;      // calle y número
        const okCP = /^\d{5}$/.test(d.codigo_postal || "");           // CP 5 dígitos
        const okLoc = (d.ciudad && d.ciudad.trim()) || (d.estado && d.estado.trim()); // ciudad o estado
        return okStreet && okCP && okLoc;
      });

      // 6. Geocodificación (Operación asíncrona)
      await geocodeDirecciones(clientData.direcciones);

      // 7. Revisión y Alerta de coordenadas
      for (const d of clientData.direcciones) {
        if (d.latitud && d.latitud !== "0" && d.latitud !== "") {
          showToast("✅ Coordenadas obtenidas para la dirección: " + d.direccion, "success");
        } else {
          showToast("⚠️ ATENCIÓN: No se encontraron coordenadas para la dirección: " + d.direccion, "warning");
        }
      }

      // --- LOG opcional para verificar ---
      console.log("Cliente DESPUÉS de geocodificar:", JSON.parse(JSON.stringify(clientData)));

      // 8. Guardado/Edición (Fetch al servidor)
      const url = clientData.id ? `${BASE_URL}/updateClient` : `${BASE_URL}/createClient`;
      const r = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(clientData)
      });
      const result = await r.json();

      if (result.success) {
        hideModal();
        dataTableInstance.ajax.reload(null, false);
        showToast(`Cliente ${clientData.id ? "actualizado" : "creado"} exitosamente.`, "success");
      } else {
        showToast(result.message, "error");
      }

    } catch (error) {
      console.error("Error en geocodificación o guardado:", error);
      showToast("No se pudo conectar con el servidor o hubo un error inesperado.", "error");
    } finally {
      hideLoader(); // Oculta el loader siempre
    }
  }

  async function handleDeleteClient(id) {
    const ok = await showConfirm("¿Estás seguro? Esta acción es irreversible."); if (!ok) return;
    try {
      const r = await fetch(`${BASE_URL}/deleteClient`, { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id }) });
      const result = await r.json();
      if (result.success) { showToast("Cliente eliminado exitosamente.", "success"); dataTableInstance.ajax.reload(null, false); }
      else showToast(result.message, "error");
    } catch { showToast("No se pudo eliminar el cliente.", "error"); }
  }

  // --- Utilidades dirección ---
  async function loadClientTypes() {
    try {
      const r = await fetch(`${BASE_URL}/getClientTypes`);
      const result = await r.json();
      const sel = document.getElementById("id_tipo");
      if (!sel) return;
      sel.innerHTML = '<option value="">Seleccione tipo de cliente</option>';
      (result.data || []).forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.id;
        opt.textContent = t.nombre;
        sel.appendChild(opt);
      });
    } catch (e) { /* opcional: console.warn(e) */ }
  }

  function populateSelect(sel, values, placeholder = "Selecciona…") {
    sel.innerHTML = "";
    const ph = document.createElement("option");
    ph.value = ""; ph.textContent = placeholder; ph.disabled = true; ph.selected = true;
    sel.appendChild(ph);
    values.forEach(v => {
      const opt = document.createElement("option");
      opt.value = v; opt.textContent = v; sel.appendChild(opt);
    });
    const manual = document.createElement("option");
    manual.value = "__manual__"; manual.textContent = "Otra… (escribir)";
    sel.appendChild(manual);
  }
  function setSelectValue(sel, value) {
    if (!value) return;
    let found = Array.from(sel.options).some(o => o.value === value);
    if (!found) {
      const opt = document.createElement("option");
      opt.value = value; opt.textContent = value;
      sel.insertBefore(opt, sel.lastChild); // antes de "Otra…"
    }
    sel.value = value;
  }

  async function fetchZippopotam(cp) {
    const r = await fetch(`${CP_API_BASE}/${encodeURIComponent(cp)}`);
    if (!r.ok) return null;
    return r.json();
  }
  async function fetchNominatimByCP(cp) {
    try {
      if (nominatimAbortController) nominatimAbortController.abort();
      nominatimAbortController = new AbortController();
      const params = new URLSearchParams({
        format: "jsonv2",
        postalcode: cp,                 // SOLO structured, sin 'q'
        countrycodes: "mx",
        addressdetails: "1",
        limit: "1",
        dedupe: "1",
        "accept-language": "es",
        email: NOMINATIM_EMAIL || ""
      });
      const r = await fetch(`${NOMINATIM_BASE}?${params.toString()}`, { signal: nominatimAbortController.signal });
      if (!r.ok) return null;
      const data = await r.json();
      if (!Array.isArray(data) || !data.length) return null;
      const a = data[0].address || {};
      return {
        city: a.city || a.town || a.village || a.municipality || a.county || "",
        state: a.state || a.region || "",
        county: a.county || "",
      };
    } catch { return null; }
  }

  async function buildFromCP(cp, row, preselect = {}) {
    const coloniaSel = row.querySelector('select[name="colonia"]');
    const ciudadSel = row.querySelector('select[name="ciudad"]');
    const estadoSel = row.querySelector('select[name="estado"]');
    const municipioInput = row.querySelector('input[name="municipio"]');

    // 1) Colonias y estado desde Zippopotam
    const z = await fetchZippopotam(cp);
    let colonias = [];
    let estadoZ = "";
    if (z?.places?.length) {
      estadoZ = z.places[0].state || "";
      colonias = [...new Set(z.places.map(p => p['place name']).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es'));
    }
    populateSelect(coloniaSel, colonias, colonias.length ? "Selecciona colonia…" : "Sin colonias");
    populateSelect(estadoSel, estadoZ ? [estadoZ] : [], "Selecciona estado…");
    if (estadoZ) setSelectValue(estadoSel, preselect.estado || estadoZ);

    // 2) Ciudad y municipio desde Nominatim
    const n = await fetchNominatimByCP(cp);
    const ciudadN = n?.city || "";
    const municipioN = n?.county || "";
    /*  populateSelect(ciudadSel, ciudadN ? [ciudadN] : [], "Selecciona ciudad…");
     if (ciudadN) setSelectValue(ciudadSel, preselect.ciudad || ciudadN);
     if (municipioInput && municipioN && !municipioInput.value) municipioInput.value = municipioN; */

    // 1. Llenar el select con la ciudad de la API (si existe)
    populateSelect(ciudadSel, ciudadN ? [ciudadN] : [], "Selecciona ciudad…");

    // 2. Priorizar y cargar la ciudad de la base de datos (preselect.ciudad)
    // setSelectValue() probablemente añade la opción si no existe y la selecciona.
    const cityToSelect = preselect.ciudad || ciudadN;
    if (cityToSelect) {
      setSelectValue(ciudadSel, cityToSelect);
    }

    if (municipioInput && municipioN && !municipioInput.value) municipioInput.value = municipioN;

    // Preselección de colonia si viene en texto
    if (preselect.colonia) setSelectValue(coloniaSel, preselect.colonia);
  }

  function attachCpWorkflow(row, data = {}) {
    const cpInput = row.querySelector('input[name="codigo_postal"]');
    const ciudadSel = row.querySelector('select[name="ciudad"]');
    const estadoSel = row.querySelector('select[name="estado"]');

    // Mostrar inputs manuales si elige "Otra…"
    const ciudadManual = row.querySelector('input[name="ciudad_manual"]');
    const estadoManual = row.querySelector('input[name="estado_manual"]');
    function toggleManual(sel, manualInput) {
      manualInput.classList.toggle('hidden', sel.value !== "__manual__");
    }
    ciudadSel.addEventListener('change', () => toggleManual(ciudadSel, ciudadManual));
    estadoSel.addEventListener('change', () => toggleManual(estadoSel, estadoManual));

    cpInput.setAttribute('maxlength', '5');
    let t;
    cpInput.addEventListener('input', () => {
      clearTimeout(t);
      const raw = (cpInput.value || "").replace(/\D/g, '');
      if (raw.length === 5) {
        t = setTimeout(() => buildFromCP(raw, row, {
          ciudad: data.ciudad || "",
          estado: data.estado || "",
          colonia: data.colonia || ""
        }), 200);
      }
    });

    // Si viene con datos precargados (editar)
    const raw = (cpInput.value || "").replace(/\D/g, '');
    if (raw.length === 5) {
      buildFromCP(raw, row, { ciudad: data.ciudad, estado: data.estado, colonia: data.colonia });
    }
  }

  // --- Autocompletado libre OSM para textarea (opcional) ---
  async function queryNominatimFree(text) {
    try {
      if (nominatimAbortController) nominatimAbortController.abort();
      nominatimAbortController = new AbortController();
      const params = new URLSearchParams({
        format: "jsonv2",
        q: text,
        addressdetails: "1",
        limit: "6",
        countrycodes: "mx",
        dedupe: "1",
        "accept-language": "es",
        email: NOMINATIM_EMAIL || ""
      });
      const r = await fetch(`${NOMINATIM_BASE}?${params.toString()}`, { signal: nominatimAbortController.signal });
      if (!r.ok) return [];
      const data = await r.json();
      return Array.isArray(data) ? data : [];
    } catch { return []; }
  }
  function attachAddressAutocomplete(row) {
    const wrapper = row.querySelector('.direccion-wrapper');
    const textarea = row.querySelector('textarea[name="direccion"]');
    const box = row.querySelector('.address-suggestions');
    if (!textarea || !box) return;
    let t;
    const hide = () => { box.classList.add('hidden'); box.innerHTML = ''; };
    const show = () => box.classList.remove('hidden');
    textarea.addEventListener('input', () => {
      const q = textarea.value.trim();
      clearTimeout(t);
      if (q.length < 3) { hide(); return; }
      t = setTimeout(async () => {
        const items = await queryNominatimFree(q);
        if (!items.length) { hide(); return; }
        box.innerHTML = items.map(it => {
          const a = it.address || {};
          const label = it.display_name;
          const city = a.city || a.town || a.village || a.municipality || a.county || '';
          const state = a.state || a.region || '';
          const postcode = a.postcode || '';
          return `<div class="addr-item p-2 hover:bg-[var(--color-bg-primary)] cursor-pointer text-sm" data-json='${JSON.stringify({ label, city, state, postcode }).replace(/'/g, "&apos;")}'>${label}</div>`;
        }).join('');
        show();
      }, 450);
    });
    box.addEventListener('click', (e) => {
      const el = e.target.closest('.addr-item'); if (!el) return;
      const p = JSON.parse(el.dataset.json.replace(/&apos;/g, "'"));
      textarea.value = p.label;
      const ciudadSel = row.querySelector('select[name="ciudad"]');
      const estadoSel = row.querySelector('select[name="estado"]');
      const cpInput = row.querySelector('input[name="codigo_postal"]');
      if (p.city) { populateSelect(ciudadSel, [p.city]); setSelectValue(ciudadSel, p.city); }
      if (p.state) { populateSelect(estadoSel, [p.state]); setSelectValue(estadoSel, p.state); }
      if (p.postcode && !cpInput.value) cpInput.value = p.postcode;
      hide();
    });
    document.addEventListener('click', (e) => { if (!wrapper.contains(e.target)) hide(); });
  }

  /*  <div class="md:col-span-2">
           <label class="text-sm font-medium text-[var(--color-text-secondary)]">Entre calles</label>
           <div class="grid grid-cols-2 gap-2">
             <input type="text" name="entre1" placeholder="Calle 1" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
             <input type="text" name="entre2" placeholder="Calle 2" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
           </div>
         </div>
 
         <div class="md:col-span-2">
           <label class="text-sm font-medium text-[var(--color-text-secondary)]">Referencias</label>
           <input type="text" name="referencias" placeholder="Punto de referencia, color de fachada, etc." class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
         </div> */

  // --- UI de dirección (con selects) ---
  function addAddressRow(data = {}) {
    const row = document.createElement("div");
    row.className = "address-row bg-[var(--color-bg-primary)] p-4 rounded-lg space-y-3";
    const uniqueId = `principal-${Date.now()}-${Math.random()}`;
    row.innerHTML = `
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2 relative direccion-wrapper">
         <label class="text-sm font-medium text-[var(--color-text-secondary)]">Dirección Geolocalización (Calle y Número)</label>
          <textarea name="direccion" class="mt-1 w-full bg-[var(--color-bg-secondary)] border border-[var(--color-border)] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" rows="3">${(data.direccion || "").replace(/\s*\[[^\]]*\]\s*$/, "")}</textarea>
          <div class="address-suggestions hidden absolute left-0 right-0 max-h-56 overflow-auto bg-[var(--color-bg-secondary)] border border-[var(--color-border)] rounded-md shadow-md z-50"></div>
        </div>

        <div class="relative">
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Código Postal</label>
          <input type="text" name="codigo_postal" value="${data.codigo_postal || ""}" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]" placeholder="Ej. 39030">
          <p class="mt-1 text-xs text-[var(--color-text-secondary)]">Escribe 5 dígitos. Se cargarán colonias y ciudad/estado.</p>
        </div>

        <div>
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Ciudad</label>
          <select name="ciudad" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
          <input type="text" name="ciudad_manual" placeholder="Escribe ciudad…" class="mt-2 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] hidden">
        </div>

        <div>
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Estado</label>
          <select name="estado" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
          <input type="text" name="estado_manual" placeholder="Escribe estado…" class="mt-2 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] hidden">
        </div>

        <div class="md:col-span-2 relative direccion-wrapper">
         <label class="text-sm font-medium text-[var(--color-text-secondary)]">Dirección de Envío</label>
         <textarea id="direccion_respaldo" name="direccion_respaldo" class="mt-1 w-full bg-[var(--color-bg-secondary)] border border-[var(--color-border)] rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[var(--color-primary)]" rows="3">${(data.direccion_respaldo || "")}</textarea>

         <!-- NEW: checkbox para elegir usar la dirección de respaldo -->
         <div class="mt-2">
           <label class="flex items-center cursor-pointer text-sm">
             <input type="checkbox" class="use-respaldo-checkbox h-4 w-4 text-[var(--color-accent)] bg-[var(--color-bg-primary)] border-[var(--color-border)]"}>
             <span class="ml-2">Usar dirección de respaldo</span>
           </label>
         </div>
        </div>


        <div class='hidden'>
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Colonia / Asentamiento</label>
          <select name="colonia" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></select>
        </div>

        <div class='hidden'>
          <label class="text-sm font-medium text-[var(--color-text-secondary)]">Municipio / Alcaldía</label>
          <input type="text" name="municipio" value="${data.municipio || ""}" class="mt-1 w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
        </div>

        <div class="md:col-span-2 flex items-center justify-between mt-2">
          <label class="flex items-center cursor-pointer">
            <input type="radio" name="principal" id="${uniqueId}" class="h-4 w-4 text-[var(--color-accent)] bg-[var(--color-bg-primary)] border-[var(--color-border)]" ${data.principal == 1 ? "checked" : ""}>
            <span class="ml-2 text-sm">Marcar como principal</span>
          </label>
          <button type="button" class="remove-address-btn text-red-500 hover:text-red-400 text-sm"><i class="fas fa-trash-alt mr-1"></i>Eliminar</button>
        </div>
      </div>
    `;
    document.getElementById("addresses-container").appendChild(row);

    // Placeholders iniciales
    populateSelect(row.querySelector('select[name="ciudad"]'), [], "Selecciona ciudad…");
    populateSelect(row.querySelector('select[name="estado"]'), [], "Selecciona estado…");
    populateSelect(row.querySelector('select[name="colonia"]'), [], "Selecciona colonia…");

    // Prefill si viene data
    if (data.ciudad) setSelectValue(row.querySelector('select[name="ciudad"]'), data.ciudad);
    if (data.estado) setSelectValue(row.querySelector('select[name="estado"]'), data.estado);

    // Flujos
    attachCpWorkflow(row, data);
    //attachAddressAutocomplete(row);
  }

  // --- Precios especiales ---
  function createSpecialPriceRow(product, specialPrice = '') {
    const el = document.createElement("div");
    el.className = "grid grid-cols-12 gap-3 items-center product-row p-2 bg-[var(--color-bg-primary)] rounded";
    el.dataset.productId = product.id;
    el.innerHTML = `
      <div class="col-span-5">
        <p class="text-sm font-medium">${product.nombre}</p>
        <p class="text-xs text-[var(--color-text-secondary)]">SKU: ${product.sku}</p>
      </div>
      <div class="col-span-3 text-right">
        <p class="text-xs text-[var(--color-text-secondary)]">Normal:</p>
        <p class="text-sm">${AutoNumeric.format(product.precio_menudeo, autoNumericOptions)}</p>
      </div>
      <div class="col-span-3">
        <input type="text" placeholder="Especial" value="${specialPrice}" data-product-id="${product.id}" class="special-price-input w-full bg-[var(--color-bg-secondary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)] text-right">
      </div>
      <div class="col-span-1 text-right">
        <button type="button" class="remove-special-price-btn text-red-500 hover:text-red-400 text-sm" title="Quitar"><i class="fas fa-times-circle"></i></button>
      </div>`;
    return el;
  }
  function addProductToSpecialList(product, specialPrice = '') {
    if (document.querySelector(`#special-prices-container .product-row[data-product-id='${product.id}']`)) return;
    const row = createSpecialPriceRow(product, specialPrice);
    const ph = specialPricesContainer.querySelector('.placeholder-text'); if (ph) ph.remove();
    specialPricesContainer.appendChild(row);
    const input = row.querySelector('.special-price-input');
    const inst = new AutoNumeric(input, autoNumericPriceOptions);
    specialPriceInstances.push(inst);
  }
  productSearchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    const term = productSearchInput.value.trim();
    if (term.length < 2) { productSearchResults.innerHTML = ""; productSearchResults.classList.add("hidden"); return; }
    searchTimeout = setTimeout(async () => {
      try {
        const r = await fetch(`${BASE_URL}/searchProductsSimple?term=${encodeURIComponent(term)}`);
        const result = await r.json();
        productSearchResults.innerHTML = "";
        if (result.success && result.data.length) {
          result.data.forEach(p => {
            const item = document.createElement("div");
            item.className = "p-3 hover:bg-[var(--color-bg-primary)] cursor-pointer text-sm";
            item.innerHTML = `<p class="font-medium">${p.nombre}</p><p class="text-xs text-[var(--color-text-secondary)]">SKU: ${p.sku}</p>`;
            item.dataset.product = JSON.stringify(p);
            productSearchResults.appendChild(item);
          });
          productSearchResults.classList.remove("hidden");
        } else {
          productSearchResults.innerHTML = `<div class="p-3 text-sm text-[var(--color-text-secondary)]">No se encontraron productos.</div>`;
          productSearchResults.classList.remove("hidden");
        }
      } catch { productSearchResults.classList.add("hidden"); }
    }, 300);
  });
  productSearchResults.addEventListener("click", (e) => {
    const t = e.target.closest('[data-product]'); if (!t) return;
    addProductToSpecialList(JSON.parse(t.dataset.product));
    productSearchInput.value = ""; productSearchResults.innerHTML = ""; productSearchResults.classList.add("hidden");
  });
  specialPricesContainer.addEventListener('click', (e) => {
    const btn = e.target.closest('.remove-special-price-btn'); if (!btn) return;
    const row = btn.closest('.product-row');
    const input = row.querySelector('.special-price-input');
    const idx = specialPriceInstances.findIndex(inst => inst.domElement === input);
    if (idx > -1) { specialPriceInstances[idx].remove(); specialPriceInstances.splice(idx, 1); }
    row.remove();
    if (specialPricesContainer.children.length === 0) {
      specialPricesContainer.innerHTML = `<p class="text-center text-[var(--color-text-secondary)] placeholder-text">Busque y añada productos para asignar precios especiales.</p>`;
    }
  });

  // --- Abonos ---
  const paymentModal = document.getElementById("payment-modal");
  const paymentForm = document.getElementById("payment-form");
  const showPaymentModal = () => paymentModal.classList.remove("hidden");
  const hidePaymentModal = () => paymentModal.classList.add("hidden");

  function handleOpenPaymentModal(id, nombre, deuda) {
    paymentForm.reset();
    document.getElementById("payment-client-id").value = id;
    document.getElementById("payment-client-name").value = nombre;

    // Deuda actual (solo lectura)
    const deudaActualInput = document.getElementById("payment-client-debt");
    const montoAbonoInput = document.getElementById("monto_abono");
    const deudaFloat = parseFloat(deuda);
    const deudaAn = new AutoNumeric(deudaActualInput, { ...autoNumericOptions, readOnly: true });
    deudaAn.set(deudaFloat);

    // Límite del monto = deuda
    if (montoAbonoAn) montoAbonoAn.remove();
    montoAbonoAn = new AutoNumeric(montoAbonoInput, { ...autoNumericOptions, maximumValue: deudaFloat.toFixed(2) });
    montoAbonoInput.placeholder = `Máximo: ${deudaFloat.toFixed(2)}`;

    // === NUEVO: inicializa campos extra ===
    const detalleEl = document.getElementById("detalle_abono");
    const fechaRecibidoEl = document.getElementById("fecha_recibido_abono");
    const hoyChk = document.getElementById("fecha_recibido_hoy");

    if (detalleEl) detalleEl.value = ""; // limpiar detalle
    if (fechaRecibidoEl) {
      const tz = new Date();
      const yyyy = tz.getFullYear();
      const mm = String(tz.getMonth() + 1).padStart(2, '0');
      const dd = String(tz.getDate()).padStart(2, '0');
      fechaRecibidoEl.value = `${yyyy}-${mm}-${dd}`; // default = hoy
    }
    if (hoyChk) {
      hoyChk.checked = true;
      hoyChk.addEventListener('change', () => {
        if (!fechaRecibidoEl) return;
        if (hoyChk.checked) {
          const tz = new Date();
          const yyyy = tz.getFullYear();
          const mm = String(tz.getMonth() + 1).padStart(2, '0');
          const dd = String(tz.getDate()).padStart(2, '0');
          fechaRecibidoEl.value = `${yyyy}-${mm}-${dd}`;
        }
      }, { once: true });
    }

    showPaymentModal();
  }



  let submittingPayment = false;

  async function handlePaymentSubmit(e) {
    e.preventDefault();

    if (submittingPayment) return;           // Candado anti-doble submit
    submittingPayment = true;
    const submitBtn = document.querySelector('#payment-form button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    try {
      if (!montoAbonoAn) { showToast("Reabre el modal de abono.", "error"); return; }

      const paymentData = {
        id_cliente: document.getElementById("payment-client-id").value,
        monto: montoAbonoAn.getNumericString(),
        metodo_pago: document.getElementById("metodo_pago_abono").value,
        detalle: (document.getElementById("detalle_abono")?.value || "").trim(),
        fecha_recibido: document.getElementById("fecha_recibido_abono")?.value || ""
      };

      const settings = montoAbonoAn.getSettings ? montoAbonoAn.getSettings() : {};
      const maxDeuda = parseFloat(settings.maximumValue || "0");

      if (!paymentData.monto || parseFloat(paymentData.monto) <= 0) {
        showToast("El monto debe ser un número positivo.", "error"); return;
      }
      if (parseFloat(paymentData.monto) > maxDeuda) {
        showToast(`El abono no puede ser mayor que la deuda de $${maxDeuda.toFixed(2)}.`, "error"); return;
      }
      if (paymentData.detalle.length > 300) {
        showToast("El detalle no debe exceder 300 caracteres.", "error"); return;
      }
      if (paymentData.fecha_recibido && !/^\d{4}-\d{2}-\d{2}$/.test(paymentData.fecha_recibido)) {
        showToast("Formato de fecha inválido (YYYY-MM-DD).", "error"); return;
      }

      const r = await fetch(`${BASE_URL}/registrarAbono`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(paymentData)
      });
      const result = await r.json();

      if (r.ok && result.success) {
        document.getElementById("payment-modal").classList.add("hidden");
        jQuery('#clientesTable').DataTable().ajax.reload(null, false);
        showToast("Abono registrado.", "success");
      } else {
        showToast(result.message || "No se pudo registrar el abono.", "error");
      }
    } catch (err) {
      showToast("No se pudo conectar con el servidor.", "error");
    } finally {
      submittingPayment = false;
      if (submitBtn) submitBtn.disabled = false;
    }
  }


  //Modificacion para imprimir formato del cliente

  /* =========================================
    FORMATO DE ENVÍO – ACUSE (2 etiquetas + OBS amarillas)
    ========================================= */
  (() => {
    // 1) Remitente fijo (ajusta logo si cambia la ruta)
    const REMITENTE = {
      nombre: 'Marco Divito',
      direccion: 'C. Álvaro Obregón 560C, Oblatos, 44360 Guadalajara, Jal.',
      telefono: '3316835010',
      logoUrl: '/multi-sucursal/public/img/logo.jpg' // <-- ajusta la ruta del logo
    };

    // Utils
    const $ = (s, r = document) => r.querySelector(s);
    const toUpperSafe = s => (s || '').toString().trim().toUpperCase();

    function getPrincipalAddressFromUI() {
      const rows = Array.from(document.querySelectorAll('#addresses-container .address-row'));
      if (!rows.length) return '';
      const row = rows.find(r => r.querySelector('input[name="principal"]')?.checked) || rows[0];

      // Si el usuario marcó "Usar dirección de respaldo" y existe texto en ella, úsala.
      const useRespaldo = !!row.querySelector('.use-respaldo-checkbox')?.checked;
      const direccionRespaldo = (row.querySelector('textarea[name="direccion_respaldo"]')?.value || '').trim();

      let dir;
      if (useRespaldo && direccionRespaldo) {
        dir = direccionRespaldo;
      } else {
        dir = (row.querySelector('textarea[name="direccion"]')?.value || '').trim();
      }

      const ciudad = (row.querySelector('input[name="ciudad_manual"]')?.value
        || row.querySelector('select[name="ciudad"]')?.value || '').trim();
      const estado = (row.querySelector('input[name="estado_manual"]')?.value
        || row.querySelector('select[name="estado"]')?.value || '').trim();
      const cp = (row.querySelector('input[name="codigo_postal"]')?.value || '').trim();

      const partes = [];
      if (dir) partes.push(dir);
      if (ciudad || estado) partes.push([ciudad, estado].filter(Boolean).join(', '));
      if (cp) partes.push(cp);
      return partes.join('. ');
    }


    // CSS base + leyenda amarilla
    function baseStyles() {
      return `
      <style>
        @page{ margin:12mm }
        body{ font-family: Arial, Helvetica, sans-serif; }
        .label{ position:relative; padding:14px 8px 8px 8px; min-height: 160px; page-break-inside: avoid; margin-bottom: 18px; }
        .logo{ position:absolute; right:8px; top:2px; height:58px; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        .title{ font-weight:700; font-size:18px; margin:6px 0 2px }
        .line{ font-size:14px; margin:3px 0 }
        .line.strong{ font-weight:700; font-size:16px }
        .line.up{ text-transform:uppercase }
        .sep{ border:0; border-top:1px solid #bfbfbf; margin:8px 0 10px }
        .legend-wrap{ text-align:center; margin:16px 0 28px; page-break-inside: avoid; }
        .legend{
          display:inline-block; background:#FEF08A; padding:4px 10px; border-radius:4px;
          font-weight:700; font-size:12px; line-height:1.25;
          -webkit-print-color-adjust:exact; print-color-adjust:exact;
        }
        .legend-wrap + .label {
        margin-top: 32px;
        }
      </style>
    `;
    }

    // Etiqueta completa (ENVÍA + línea + RECIBE)
    function buildFullLabel({ nombre, direccion, telefono }) {
      return `
      <div class="label">
        <img class="logo" src="${REMITENTE.logoUrl}" alt="logo">
        <div class="title">Envia:</div>
        <div class="line strong">${REMITENTE.nombre}</div>
        <div class="line">${REMITENTE.direccion}. Telef: ${REMITENTE.telefono}</div>

        <hr class="sep">

        <div class="title">Recibe:</div>
        <div class="line strong">${toUpperSafe(nombre)}</div>
        <div class="line up">${toUpperSafe(direccion)}</div>
        ${telefono ? `<div class="line">Teléfono: ${telefono}</div>` : ``}
      </div>
    `;
    }

    // Leyenda amarilla (observaciones)
    function buildLegend(text) {
      const t = (text || '').trim();
      if (!t) return '';
      return `
      <div class="legend-wrap">
        <div class="legend">${t.replace(/\n/g, '<br>')}</div>
      </div>
    `;
    }

    // Render vista previa: etiqueta arriba + OBS (si hay) + etiqueta abajo
    function renderShippingPreview() {
      const box = $('#shipping-preview');
      if (!box) return;

      const nombre = $('#nombre')?.value || '';
      const telefono = $('#telefono')?.value || '';
      const direccion = getPrincipalAddressFromUI() || '';
      const obs = $('#obs_envio')?.value || '';

      const etiqueta = buildFullLabel({ nombre, direccion, telefono });
      const leyenda = buildLegend(obs);

      box.innerHTML = `${baseStyles()}${etiqueta}${leyenda}${etiqueta}`;
    }

    // Eventos (delegación)
    document.addEventListener('click', e => {
      if (e.target && e.target.matches('#preview-shipping-btn')) {
        renderShippingPreview();
      }
      if (e.target && e.target.matches('#print-shipping-btn')) {
        const nombre = $('#nombre')?.value || '';
        const telefono = $('#telefono')?.value || '';
        const direccion = getPrincipalAddressFromUI() || '';
        const obs = $('#obs_envio')?.value || '';

        const etiqueta = buildFullLabel({ nombre, direccion, telefono });
        const leyenda = buildLegend(obs);

        const html = `
        <html>
          <head><meta charset="utf-8"><title>Formato de Envío (Acuse)</title>${baseStyles()}</head>
          <body>
            ${etiqueta}
            ${leyenda}
            ${etiqueta}
            <script>window.print();</script>
          </body>
        </html>`;
        const w = window.open('', '_blank', 'width=900,height=700');
        w.document.open(); w.document.write(html); w.document.close();
      }
    });

    // Auto-preview al escribir/cambiar datos
    ['#obs_envio', '#nombre', '#telefono'].forEach(sel => {
      document.addEventListener('input', e => {
        if (e.target && e.target.matches(sel)) renderShippingPreview();
      });
    });
    document.addEventListener('input', e => {
      if (e.target && e.target.closest('#addresses-container')) renderShippingPreview();
    });

    // Exponer si lo necesitas
    window.renderShippingPreview = renderShippingPreview;
  })();

  // --- Listeners globales ---
  document.getElementById("add-client-btn").addEventListener("click", prepareNewClientForm);
  document.getElementById("close-modal-btn").addEventListener("click", hideModal);
  document.getElementById("cancel-btn").addEventListener("click", hideModal);
  clientForm.addEventListener("submit", handleFormSubmit);
  document.getElementById("add-address-btn").addEventListener("click", () => addAddressRow());
  document.getElementById("close-payment-modal-btn").addEventListener("click", hidePaymentModal);
  document.getElementById("cancel-payment-btn").addEventListener("click", hidePaymentModal);
  paymentForm.addEventListener("submit", handlePaymentSubmit);

  document.getElementById("addresses-container").addEventListener("click", (e) => {
    if (!e.target.closest(".remove-address-btn")) return;
    if (document.querySelectorAll("#addresses-container .address-row").length > 1) e.target.closest(".address-row").remove();
    else showToast("Debe haber al menos una dirección.", "error");
  });
  document.getElementById("tiene_credito").addEventListener("change", function () {
    document.getElementById("limite-credito-container").classList.toggle("hidden", !this.checked);
  });
  jQuery('#clientesTable tbody').on('click', '.edit-btn', function () {
    const data = dataTableInstance.row(jQuery(this).parents('tr')).data(); handleEditClient(data.id);
  });
  jQuery('#clientesTable tbody').on('click', '.delete-btn', function () {
    const data = dataTableInstance.row(jQuery(this).parents('tr')).data(); handleDeleteClient(data.id);
  });
  jQuery('#clientesTable tbody').on('click', '.payment-btn', function () {
    const data = dataTableInstance.row(jQuery(this).parents('tr')).data(); handleOpenPaymentModal(data.id, data.nombre, data.deuda_actual);
  });

  hideLoader();

  // --- Init ---
  limiteCreditoAn = new AutoNumeric('#limite_credito', autoNumericOptions);
});

/** Muestra el overlay de carga */
function showLoader() {
  const loader = document.getElementById('global-loader-overlay');
  if (loader) {
    loader.style.display = 'flex'; // O 'block', dependiendo de cómo lo diseñes
  }
}

/** Oculta el overlay de carga */
function hideLoader() {
  const loader = document.getElementById('global-loader-overlay');
  if (loader) {
    loader.style.display = 'none';
  }
}