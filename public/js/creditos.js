
// Utilidades
const money = n => `$${Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
const modal = document.getElementById('modal-detalle-venta');
const mdVentaId = document.getElementById('md-venta-id');
const mdTbody = document.getElementById('md-tbody');
const mdTotal = document.getElementById('md-total');

function openModal() { modal.classList.remove('hidden'); }
function closeModal() { modal.classList.add('hidden'); }

document.getElementById('btn-cerrar-modal').addEventListener('click', closeModal);
modal.addEventListener('click', (e) => { if (e.target.hasAttribute('data-close-modal')) closeModal(); });

// ======== DataTables (Server-Side) ========
(function () {
    if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) {
        console.error('DataTables no está cargado.');
        return;
    }
    const $table = jQuery('#tabla-ventas-credito');
    //const $fPend = document.getElementById('filtro-pendientes');

    const dt = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: `${BASE_URL}/ventasCreditoData`,
            type: 'POST',
            data: function (d) {
                d.soloPendientes = 0;
            },
            dataSrc: function (json) {
                const sum = Number(json.sumSaldoFiltered || 0);
                document.getElementById('sum-saldo').textContent =
                    `$${sum.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                return json.data || [];
            }
        },
        order: [[2, 'desc']], // fecha
        pageLength: 25,
        language: { url: 'js/es.json' },
        dom: "<'flex justify-between'lf>" + "<'clear'>" + "<'flex justify-center mb-8'B>" + 'rtip',
        columns: [
            { data: 'cliente' },
            { data: 'venta_id', render: (d) => 'V' + d },
            { data: 'fecha_venta' },
            { data: 'monto_credito', className: 'text-right', render: (d) => money(d) },
            { data: 'abonado_total', className: 'text-right', render: (d) => money(d) },
            { data: 'saldo_pendiente', className: 'text-right', render: (d) => money(d) },
            {
                data: 'estatus_calc',
                render: (d) => {
                    const cls = d === 'Cerrada' ? 'text-xs bg-emerald-600/20 text-emerald-300' :
                        d === 'Parcial' ? 'bg-amber-600/20 text-amber-300' : 'bg-slate-400/20 text-slate-300';
                    return `<span class="inline-block px-2 py-0.5 rounded-full text-xs ${cls}">${d}</span>`;
                }
            },
            {
                data: 'venta_id', orderable: false, searchable: false,
                render: (ventaId) => `<button class="btn-detalle-venta px-2 py-1 rounded-md bg-purple-600 hover:bg-purple-700 text-white font-bold" data-venta="${ventaId}">Ver</button>`
            }
        ],
        buttons: [
            { extend: 'copyHtml5', text: 'Copiar', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
            { extend: 'excelHtml5', title: 'Creditos', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
            { extend: 'csvHtml5', title: 'Creditos', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
            {
                extend: 'pdfHtml5', title: 'Creditos', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] }, customize: function (doc) {
                    // 1) Tomamos el total del último JSON del server
                    const last = dt.ajax.json() || {};
                    const totalFiltro = Number(last.sumSaldoFiltered || 0);

                    // 2) Localizamos la tabla exportada
                    const table = doc.content.find(x => x.table);
                    if (!table) return;

                    // (opcional) estilos base
                    doc.pageMargins = [36, 40, 36, 40];
                    doc.defaultStyle.fontSize = 9;
                    doc.styles.tableHeader = { bold: true, fontSize: 10, color: '#fff', fillColor: '#111827' };

                    // 3) Anchos y alineación de números
                    table.table.widths = ['*', 'auto', 'auto', 'auto', 'auto', 'auto', 'auto']; // ajusta si cambias columnas
                    const numericCols = [3, 4, 5]; // Crédito, Abonado, Saldo
                    table.table.body.forEach((row, i) => {
                        if (i === 0) return; // header
                        numericCols.forEach(ci => row[ci] && (row[ci].alignment = 'right'));
                    });

                    // 4) Agregamos la fila de TOTAL al final
                    const colCount = table.table.body[0].length; // número de columnas exportadas
                    const totalRow = new Array(colCount).fill({ text: '' });
                    totalRow[colCount - 3] = { text: 'Total (filtro):', alignment: 'right', bold: true };
                    totalRow[colCount - 2] = { text: money(totalFiltro), alignment: 'right', bold: true };
                    table.table.body.push(totalRow);

                    // 5) Líneas sutiles
                    table.layout = {
                        hLineColor: '#e5e7eb',
                        vLineColor: '#e5e7eb'
                    };
                }
            },
            { extend: 'print', text: 'Imprimir', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } }
        ],
    });

    // Refrescar por filtro
    //$fPend.addEventListener('change', () => dt.ajax.reload());

    // Abrir modal detalle

    // Abrir modal detalle
    document.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.btn-detalle-venta');
        if (!btn) return;

        const ventaId = btn.dataset.venta;
        mdVentaId.textContent = ventaId;
        mdTbody.innerHTML = '<tr><td colspan="5" class="px-3 py-3 text-center">Cargando…</td></tr>';
        openModal();

        try {
            const r = await fetch(`${BASE_URL}/ventaDetalle&venta_id=${encodeURIComponent(ventaId)}`);
            const j = await r.json();
            if (!j.success) throw new Error(j.message || 'Error');

            let total = 0;
            mdTbody.innerHTML = (j.data || []).map(row => {
                total += Number(row.importe || 0);
                return `
                                <tr>
                                <td class="px-3 py-1">${row.producto || ''}</td>
                                <td class="px-3 py-1 text-right">${row.cantidad}</td>
                                <td class="px-3 py-1 text-right">${money(row.precio_unitario)}</td>
                                <td class="px-3 py-1 text-right">${money(row.importe)}</td>
                                </tr>`;
            }).join('');
            mdTotal.textContent = money(total);
        } catch (e) {
            mdTbody.innerHTML = `<tr><td colspan="5" class="px-3 py-3 text-center text-red-600">No se pudo cargar el detalle.</td></tr>`;
        }
    });


})();


// === DataTable: Aplicaciones de pagos (server-side) ===
(function () {
    if (!window.jQuery || !jQuery.fn || !jQuery.fn.DataTable) return;
    const $tabla = jQuery('#tabla-aplicaciones');

    const dtApps = $tabla.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: `${BASE_URL}/pagosAplicadosData`,   // <- enrutador homogéneo
            type: 'POST',
            data: function (d) {
                // Si filtras por cliente/fechas, agrégalos aquí:
                // d.id_cliente = document.getElementById('filtro-cliente')?.value || '';
                // d.desde = document.getElementById('filtro-desde')?.value || '';
                // d.hasta = document.getElementById('filtro-hasta')?.value || '';
            }
        },
        order: [[3, 'desc'], [0, 'desc']], // fecha_recibido, nro_pago
        pageLength: 25,
        language: { url: 'js/es.json' },
        dom: "<'flex justify-between'lf>" + "<'clear'>" + "<'flex justify-center mb-8'B>" + 'rtip',
        columns: [
            { data: 'nro_pago' },
            { data: 'cliente' },
            { data: 'usuario_registro' },
            { data: 'fecha_recibido' },
            { data: 'monto_pago', className: 'text-right', render: d => money(d) },
            { data: 'metodo_pago' },
            { data: 'detalle' },
            { data: 'venta_id', render: d => d ? `V${d}` : '' },
            { data: 'fecha_venta' },
            { data: 'aplicado_en_esta_venta', className: 'text-right', render: d => money(d) },
            { data: 'monto_credito_venta', className: 'text-right', render: d => money(d) },
            { data: 'saldo_pendiente_actual', className: 'text-right', render: d => money(d) },
            { data: 'fecha_sistema' }
        ],
        buttons: [
            { extend: 'copyHtml5', text: 'Copiar' },
            { extend: 'excelHtml5', text: 'Excel' },
            { extend: 'csvHtml5', text: 'CSV' },
            {
                extend: 'pdfHtml5', text: 'PDF', orientation: 'landscape', pageSize: 'LETTER',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12] }
            },
            { extend: 'print', text: 'Imprimir' }
        ]
    });
})();

