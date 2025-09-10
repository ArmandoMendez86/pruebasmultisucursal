// Archivo: /public/js/reportes_dinamicos.js
// Requiere BASE_URL definido en js/rutas.js
$(async function () {
    const $columns = $('#columns-container');
    const $filters = $('#filters-container');
    const $groupBy = $('#groupby-container');
    const $aggs = $('#aggs-container');
    const $dateCol = $('#date-col');
    const $dateRange = $('#daterange');
    const $useDate = $('#use-date-filter');
    const $clearDate = $('#clear-date');
    const $searchCols = $('#buscar-campos');

    let META = null;
    let table = null;

    const el = (html) => $(html.trim());
    const opt = (value, text) => `<option value="${value}">${text}</option>`;

    // ================== CARGA DE METADATOS ==================
    async function loadMeta() {
        const res = await fetch(`${BASE_URL}/getReportBuilderMeta`);
        const json = await res.json();
        if (!json.success) throw new Error('No se pudieron cargar metadatos');
        META = json.data; // {columns:{Grupo:[{key,label,alias,type}]}, aggs:[], dateCols?, defaultDateCol?}

        // Pintar columnas
        const groups = META.columns || {};
        Object.keys(groups).forEach(groupName => {
            const g = groups[groupName] || [];
            const $box = el(`
        <div class="col-group">
          <div class="text-xs uppercase text-slate-400 mb-1 group-title">${groupName}</div>
          <div class="group-items space-y-0.5"></div>
        </div>`);
            const $items = $box.find('.group-items');

            g.forEach(col => {
                const id = `col__${col.key.replace('.', '_')}`;
                $items.append(`
          <label class="col-item flex items-center gap-2 py-0.5"
                 data-text="${(col.label + ' ' + col.alias + ' ' + col.key).toLowerCase()}">
            <input type="checkbox" class="col-check accent-indigo-500"
                   data-key="${col.key}" data-alias="${col.alias}" id="${id}">
            <span>${col.label}</span>
          </label>
        `);
            });
            $columns.append($box);
        });

        // Columnas de fecha (si el backend no las manda, las inferimos)
        let dateCols = META.dateCols || {};
        if (!Object.keys(dateCols).length) {
            Object.values(META.columns || {}).flat().forEach(col => {
                const t = (col.type || '').toLowerCase();
                if (t.includes('date') || t.includes('time')) dateCols[col.key] = col.label;
            });
        }
        $dateCol.empty();
        Object.entries(dateCols).forEach(([k, label]) => $dateCol.append(opt(k, label)));
        const def = META.defaultDateCol || Object.keys(dateCols)[0] || '';
        if (def) $dateCol.val(def);

        // Selección por defecto útil (déjalo si te gusta, o elimínalo)
        selectDefaults(['v.fecha', 'v.id', 'c.nombre', 'u.nombre', 's.nombre', 'v.total']);

        // NO agregamos filtros ni cálculos por defecto
        // addFilterRow();  // <- eliminado
        // addAggRow();     // <- eliminado

        // Buscador de campos
        wireColumnSearch();

        // Datepicker + controles de fecha
        initDateRange();
        bindDateControls();
        $useDate.prop('checked', false);
        syncDateControls(); // estado inicial del toggle
    }

    // ================== BUSCADOR DE CAMPOS ==================
    function wireColumnSearch() {
        if (!$searchCols.length) return;
        const filter = () => {
            const q = ($searchCols.val() || '').trim().toLowerCase();
            $columns.find('.col-group').each(function () {
                let any = false;
                $(this).find('label.col-item').each(function () {
                    const hit = !q || $(this).data('text').includes(q);
                    $(this).toggleClass('hidden', !hit);
                    if (hit) any = true;
                });
                $(this).toggleClass('hidden', !any);
            });
        };
        $searchCols.on('input', filter);
        filter();
    }

    function selectDefaults(keys) {
        keys.forEach(k => $(`.col-check[data-key="${k}"]`).prop('checked', true));
    }

    // ================== FILTROS ==================
    function addFilterRow() {
        const $row = el(`
      <div class="flex items-center gap-2">
        <select class="f-col w-1/2 bg-slate-950 border border-slate-700 rounded px-2 py-1"></select>
        <select class="f-op  w-1/4 bg-slate-950 border border-slate-700 rounded px-2 py-1">
          <option value="eq">=</option>
          <option value="neq">≠</option>
          <option value="gt">&gt;</option>
          <option value="gte">≥</option>
          <option value="lt">&lt;</option>
          <option value="lte">≤</option>
          <option value="like">contiene</option>
          <option value="between">entre</option>
        </select>
        <input  class="f-val  w-1/4 bg-slate-950 border border-slate-700 rounded px-2 py-1" placeholder="valor">
        <input  class="f-val2 w-1/4 bg-slate-950 border border-slate-700 rounded px-2 py-1 hidden" placeholder="valor 2">
        <button class="rm-filter px-2 py-1 rounded bg-slate-800 hover:bg-slate-700" title="Quitar">✕</button>
      </div>
    `);

        Object.values(META.columns || {}).flat().forEach(col => {
            $row.find('.f-col').append(opt(col.key, `[${col.alias}] ${col.label}`));
        });

        $row.on('change', '.f-op', function () {
            const isBetween = $(this).val() === 'between';
            $row.find('.f-val2').toggleClass('hidden', !isBetween);
        });
        $row.on('click', '.rm-filter', () => $row.remove());
        $filters.append($row);
    }
    $('#add-filter').on('click', addFilterRow);

    // ================== GROUP BY & AGGREGATES ==================
    function addGroupByRow() {
        const $row = el(`
      <div class="flex items-center gap-2">
        <select class="g-col w-full bg-slate-950 border border-slate-700 rounded px-2 py-1"></select>
        <button class="rm-group px-2 py-1 rounded bg-slate-800 hover:bg-slate-700">✕</button>
      </div>`);
        Object.values(META.columns || {}).flat().forEach(col => {
            $row.find('.g-col').append(opt(col.key, `[${col.alias}] ${col.label}`));
        });
        $row.on('click', '.rm-group', () => $row.remove());
        $groupBy.append($row);
    }

    function addAggRow() {
        const $row = el(`
      <div class="flex items-center gap-2">
        <select class="a-fn  w-1/3 bg-slate-950 border border-slate-700 rounded px-2 py-1"></select>
        <select class="a-col w-1/3 bg-slate-950 border border-slate-700 rounded px-2 py-1"></select>
        <input  class="a-alias w-1/3 bg-slate-950 border border-slate-700 rounded px-2 py-1" placeholder="alias (opcional)">
        <button class="rm-agg px-2 py-1 rounded bg-slate-800 hover:bg-slate-700">✕</button>
      </div>`);
        (META.aggs || ['SUM', 'AVG', 'MIN', 'MAX', 'COUNT']).forEach(fn => $row.find('.a-fn').append(opt(fn, fn)));
        Object.values(META.columns || {}).flat().forEach(col => {
            $row.find('.a-col').append(opt(col.key, `[${col.alias}] ${col.label}`));
        });
        $row.on('click', '.rm-agg', () => $row.remove());
        $aggs.append($row);
    }
    $('#add-agg').on('click', addAggRow);

    // ================== RANGO DE FECHAS ==================
    function initDateRange() {
        if (!$dateRange.length) return;
        $dateRange.daterangepicker({
            autoUpdateInput: true,
            autoApply: true,
            showDropdowns: true,
            opens: 'left',
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                applyLabel: 'Aplicar',
                cancelLabel: 'Limpiar',
                daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                firstDay: 1
            }
        }, function (start, end) {
            $dateRange.val(start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD'));
        });

        // Permitir limpiar con el evento cancel
        $dateRange.on('cancel.daterangepicker', function () {
            $(this).val('');
            const p = $(this).data('daterangepicker');
            if (p) { p.setStartDate(moment()); p.setEndDate(moment()); }
        });
    }

    function bindDateControls() {
        // Botón "Limpiar"
        $clearDate.on('click', () => {
            $dateRange.val('');
            const p = $dateRange.data('daterangepicker');
            if (p) { p.setStartDate(moment()); p.setEndDate(moment()); }
        });

        // Toggle "Filtrar por fecha"
        $useDate.on('change', syncDateControls);
    }

    function syncDateControls() {
        const use = $useDate.is(':checked');
        $dateCol.prop('disabled', !use).toggleClass('opacity-50', !use);
        $dateRange.prop('disabled', !use).toggleClass('opacity-50', !use);
        $clearDate.prop('disabled', !use);
        if (!use) {
            $dateRange.val('');
            const p = $dateRange.data('daterangepicker');
            if (p) { p.setStartDate(moment()); p.setEndDate(moment()); }
        }
    }

    // ================== RECOLECCIÓN & EJECUCIÓN ==================
    $('#run-report').on('click', runReport);

    // Quita filtros BETWEEN duplicados para la columna de fecha seleccionada
    function dedupeAndInjectDate(filters) {
        const key = ($('#date-col').val() || '');
        const cleaned = (filters || []).filter(f => !(f.col === key && String(f.op).toLowerCase() === 'between'));
        if ($('#use-date-filter').is(':checked')) {
            const $dateRange = $('#daterange');
            const picker = $dateRange.data('daterangepicker');
            if (picker && $dateRange.val() && key) {
                cleaned.unshift({
                    col: key,
                    op: 'between',
                    value: picker.startDate.format('YYYY-MM-DD'),
                    valueTo: picker.endDate.format('YYYY-MM-DD')
                });
            }
        }
        return cleaned;
    }


    function gatherConfig() {
        const selectedColumns = [];
        $('.col-check:checked').each(function () {
            selectedColumns.push($(this).data('key'));
        });

        const filters = [];
        $filters.find('> div').each(function () {
            const col = $(this).find('.f-col').val();
            const op = $(this).find('.f-op').val();
            const v1 = $(this).find('.f-val').val();
            const v2 = $(this).find('.f-val2').val();
            if (!col || !op) return;
            const f = { col, op, value: v1 };
            if (op === 'between') f.valueTo = v2;
            filters.push(f);
        });

        // *** INYECTAR RANGO DE FECHAS SOLO SI EL TOGGLE ESTÁ ACTIVO Y HAY RANGO ***
        const picker = $dateRange.data('daterangepicker');
        const key = ($dateCol.val() || '');
        if ($useDate.is(':checked') && picker && $dateRange.val() && key) {
            const start = picker.startDate.format('YYYY-MM-DD');
            const end = picker.endDate.format('YYYY-MM-DD');
            filters.unshift({ col: key, op: 'between', value: start, valueTo: end });
        }

        const groupBy = [];
        $groupBy.find('> div').each(function () {
            const col = $(this).find('.g-col').val();
            if (col) groupBy.push(col);
        });

        const aggs = [];
        $aggs.find('> div').each(function () {
            const fn = $(this).find('.a-fn').val();
            const col = $(this).find('.a-col').val();
            const alias = ($(this).find('.a-alias').val() || '').trim();
            if (fn && col) aggs.push({ fn, col, alias });
        });

        return {
            selectedColumns,
            filters,          // ← sin filas iniciales: solo lo que el usuario agregue (y fecha si procede)
            groupBy,
            aggregations: aggs
        };
    }

    function gatherConfigForRun() {
        const selectedColumns = [];
        $('.col-check:checked').each(function () { selectedColumns.push($(this).data('key')); });

        // Filtros base (los que agregó el usuario en el panel)
        const filters = [];
        $('#filters-container').find('> div').each(function () {
            const col = $(this).find('.f-col').val();
            const op = $(this).find('.f-op').val();
            const v1 = $(this).find('.f-val').val();
            const v2 = $(this).find('.f-val2').val();
            if (!col || !op) return;
            const f = { col, op, value: v1 };
            if (op === 'between') f.valueTo = v2;
            filters.push(f);
        });

        // <-- DEDUPLICACIÓN + inyección opcional del rango de fechas
        const finalFilters = dedupeAndInjectDate(filters);

        const groupBy = [];
        $('#groupby-container').find('> div').each(function () {
            const col = $(this).find('.g-col').val();
            if (col) groupBy.push(col);
        });

        const aggregations = [];
        $('#aggs-container').find('> div').each(function () {
            const fn = $(this).find('.a-fn').val();
            const col = $(this).find('.a-col').val();
            const alias = ($(this).find('.a-alias').val() || '').trim();
            if (fn && col) aggregations.push({ fn, col, alias });
        });

        return { selectedColumns, filters: finalFilters, groupBy, aggregations };
    }


    function buildColumnsForDataTables(config) {
        const cols = [];
        const byKey = {};
        Object.values(META.columns || {}).flat().forEach(c => { byKey[c.key] = c; });

        config.selectedColumns.forEach(key => {
            const meta = byKey[key];
            if (meta) cols.push({
                data: meta.alias,
                title: meta.label,
                className: meta.type === 'number' ? 'dt-right' : ''
            });
        });

        config.aggregations.forEach(a => {
            let alias = (a.alias || '').trim();
            if (!alias) {
                const meta = byKey[a.col]; const base = meta ? meta.alias : 'col';
                alias = (a.fn.toLowerCase() + '_' + base).toLowerCase();
            }
            cols.push({ data: alias, title: `${a.fn}(${(byKey[a.col]?.label) || a.col})`, className: 'dt-right' });
        });

        return cols;
    }

    async function runReport() {
        const config = gatherConfig();
        if (!config.selectedColumns.length) {
            return window.toast && toast('Selecciona al menos una columna.', 'warning');
        }

        if (table) { table.destroy(); $('#dynamic-table').empty(); }

        const columns = buildColumnsForDataTables(config);

        table = $('#dynamic-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,   // sin responsive
            scrollX: true,       // scroll horizontal
            ajax: {
                url: `${BASE_URL}/getDynamicReportServerSide`,
                type: 'POST',
                data: function (d) {
                    d.config = JSON.stringify(config);
                }
            },
            columns,
            dom: "<'dt-top-controls'<'left-controls'l><'center-controls'B><'right-controls'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'dt-bottom-controls'<'left-controls'i><'right-controls'p>>",
            buttons: [
                { extend: 'excelHtml5', text: 'Exportar Excel' },
                { extend: 'pdfHtml5', text: 'Exportar PDF', orientation: 'landscape', pageSize: 'A4' },
                { extend: 'print', text: 'Imprimir' }
            ],
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'desc']]
        });
    }

    // Botón para agregar GroupBy
    $groupBy.append(`<button id="add-groupby" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700">+ Agrupar por</button>`);
    $groupBy.on('click', '#add-groupby', function () { addGroupByRow(); });

    /* =====================
   Guardar / Abrir (robusto)
   ===================== */
    const PRESETS = {
        list: `${BASE_URL}/listPresets`,
        save: `${BASE_URL}/savePreset`,
        get: `${BASE_URL}/getPreset`,
        del: `${BASE_URL}/deletePreset`,
    };

    // Modal (si no existe, lo creo en caliente)
    (function ensurePresetsModal() {
        if (!document.getElementById('presets-modal')) {
            const html = `
<div id="presets-modal" class="fixed inset-0 hidden items-center justify-center z-50">
  <div class="absolute inset-0 bg-black/60"></div>
  <div class="relative bg-slate-900 border border-slate-800 rounded-xl w-full max-w-lg p-4">
    <div class="flex items-center gap-2 mb-3">
      <h3 class="font-semibold me-auto">Reportes guardados</h3>
      <button id="close-presets" class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700">Cerrar</button>
    </div>
    <div class="mb-3">
      <input id="preset-search" type="search" class="w-full bg-slate-950 border border-slate-700 rounded px-2 py-1"
             placeholder="Buscar por nombre…">
    </div>
    <div id="presets-list" class="space-y-2 max-h-80 overflow-y-auto"></div>
  </div>
</div>`;
            document.body.insertAdjacentHTML('beforeend', html);
        }
    })();

    const $modal = $('#presets-modal');
    const $list = $('#presets-list');
    const $search = $('#preset-search');

    // Abrir modal (soporta #btn-open y #open-load)
    $('#btn-open, #open-load').on('click', async () => {
        try {
            await refreshPresets();
            $modal.removeClass('hidden').addClass('flex');
        } catch (e) {
            alert('No se pudieron listar los reportes guardados.');
        }
    });
    $('#close-presets').on('click', () => $modal.addClass('hidden').removeClass('flex'));
    $(document).on('keydown', (e) => { if (e.key === 'Escape') $('#close-presets').click(); });

    $search.on('input', function () {
        const q = (this.value || '').toLowerCase();
        $list.find('.preset-item').each(function () {
            $(this).toggle($(this).data('name').includes(q));
        });
    });

    // Guardar (soporta #btn-save y #open-save)
    $('#btn-save, #open-save').on('click', async () => {
        const nombre = prompt('Nombre para guardar este reporte:');
        if (!nombre) return;

        // Usa tus funciones/controles ya existentes
        const cfg = gatherConfig ? gatherConfig() : {};
        const ui = {
            dateEnabled: $('#use-date-filter').is(':checked'),
            dateCol: $('#date-col').val(),
            dateRange: $('#daterange').val()
        };

        try {
            const res = await fetch(PRESETS.save, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nombre, descripcion: '', config: { ...cfg, __ui: ui } })
            }).then(r => r.json());

            if (!res || !res.success) throw new Error(res && res.message || 'Error al guardar');
            alert('Reporte guardado.');
        } catch (err) {
            alert(err.message || 'No se pudo guardar.');
        }
    });

    // Pintar lista
    async function refreshPresets() {
        $list.empty().append('<div class="text-sm text-slate-400">Cargando…</div>');
        const json = await fetch(PRESETS.list).then(r => r.json());
        $list.empty();

        if (!json.success || !Array.isArray(json.data) || !json.data.length) {
            $list.append('<div class="text-sm text-slate-400">No hay reportes guardados.</div>');
            return;
        }

        json.data.forEach(r => {
            const $row = $(`
      <div class="preset-item flex items-center gap-2 p-2 border border-slate-800 rounded-lg"
           data-name="${(r.nombre || '').toLowerCase()}">
        <div class="me-auto">
          <div class="font-medium">${r.nombre}</div>
          <div class="text-xs text-slate-400">${r.created_at || ''}</div>
        </div>
        <button class="btn-open px-2 py-1 rounded bg-indigo-600 hover:bg-indigo-500 text-white" data-id="${r.id}">Abrir</button>
        <button class="btn-del  px-2 py-1 rounded bg-slate-800 hover:bg-slate-700" data-id="${r.id}">Borrar</button>
      </div>
    `);
            $list.append($row);
        });
    }

    // Abrir
    $(document).on('click', '.btn-open', async function () {
        const id = $(this).data('id');
        try {
            const res = await fetch(`${PRESETS.get}?id=${id}`).then(r => r.json());
            if (!res.success) throw new Error(res.message || 'No se pudo cargar');
            const cfg = res.data.config || {};
            const ui = cfg.__ui || {};
            applyPresetToUI(cfg, ui);
            $('#close-presets').click();
            // Si quieres ejecutar automáticamente: $('#run-report').click();
        } catch (err) {
            alert(err.message || 'No se pudo cargar el reporte.');
        }
    });

    // Borrar
    $(document).on('click', '.btn-del', async function () {
        if (!confirm('¿Eliminar este reporte?')) return;
        const id = $(this).data('id');
        try {
            const res = await fetch(PRESETS.del, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            }).then(r => r.json());
            if (!res.success) throw new Error(res.message || 'No se pudo eliminar');
            await refreshPresets();
        } catch (err) {
            alert(err.message || 'No se pudo eliminar el reporte.');
        }
    });

    // Rehidratar UI SIN depender de returns de tus helpers
    function applyPresetToUI(cfg, ui) {
        // 1) columnas
        $('.col-check').prop('checked', false);
        (cfg.selectedColumns || cfg.columns || []).forEach(k => {
            $(`.col-check[data-key="${k}"]`).prop('checked', true).trigger('change');
        });

        // 2) filtros
        const $filters = $('#filters-container');
        $filters.empty();
        (cfg.filters || []).forEach(f => {
            // tu helper no retorna => creo y luego tomo el último
            if (typeof addFilterRow === 'function') addFilterRow();
            const $row = $filters.children('div').last();
            $row.find('.f-col').val(f.col).trigger('change');
            $row.find('.f-op').val(f.op).trigger('change');
            $row.find('.f-val').val(f.value ?? '');
            $row.find('.f-val2').val(f.valueTo ?? '');
        });

        // 3) group by
        const $group = $('#groupby-container');
        $group.find('> div').remove(); // deja botones si los tienes fuera
        (cfg.groupBy || []).forEach(g => {
            if (typeof addGroupByRow === 'function') addGroupByRow();
            const $g = $group.children('div').last();
            $g.find('.g-col').val(g).trigger('change');
        });

        // 4) agregaciones
        const $aggs = $('#aggs-container');
        $aggs.find('> div').remove();
        (cfg.aggregations || []).forEach(a => {
            if (typeof addAggRow === 'function') addAggRow();
            const $a = $aggs.children('div').last();
            $a.find('.a-fn').val(a.fn).trigger('change');
            $a.find('.a-col').val(a.col).trigger('change');
            $a.find('.a-alias').val(a.alias || '');
        });

        // 5) controles de fecha
        // ===== Fecha =====
        (function () {
            const $use = $('#use-date-filter');
            const $col = $('#date-col');
            const $range = $('#daterange');

            const uiEnabled = !!(ui && ui.dateEnabled);
            if (ui && ui.dateCol) $col.val(ui.dateCol);
            if (ui && ui.dateRange) $range.val(ui.dateRange);

            // ¿El preset YA trae un BETWEEN sobre la misma columna de fecha?
            const key = $col.val() || (ui && ui.dateCol) || '';
            const hasDateInFilters = (cfg.filters || []).some(f =>
                f.col === key && String(f.op).toLowerCase() === 'between'
            );

            // Si ya viene en filters => mantenemos el toggle APAGADO (evita duplicar)
            // Si NO viene en filters => respetamos lo que guardó el usuario en uiEnabled
            $use.prop('checked', hasDateInFilters ? false : uiEnabled);

            // sincroniza estados (deshabilita/rehabilita inputs visualmente)
            if (typeof syncDateControls === 'function') {
                syncDateControls();
            } else {
                // fallback mínimo
                $col.prop('disabled', !$use.is(':checked'));
                $range.prop('disabled', !$use.is(':checked'));
            }
        })();

    }

    $('#close-presets').click();

    // Ejecutar automáticamente
    if (typeof runReport === 'function') {
        runReport();
    } else {
        $('#run-report').trigger('click');
    }



    // Go!
    await loadMeta();
});
