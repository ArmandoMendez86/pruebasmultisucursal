// Archivo: /public/js/reporte_global.js
$(document).ready(function () {
    const apiURL = `${BASE_URL}/getGlobalVentasServerSide`;
    let totalFooterAn; // Instancia de AutoNumeric para el footer

    // Inicialización de Daterangepicker
    const dateRangeInput = $('#daterange-filter');
    dateRangeInput.daterangepicker({
        opens: 'left',
        autoUpdateInput: false,
        locale: {
            "format": "YYYY-MM-DD", "separator": " - ", "applyLabel": "Aplicar", "cancelLabel": "Limpiar",
            "fromLabel": "Desde", "toLabel": "Hasta", "customRangeLabel": "Personalizado", "weekLabel": "S",
            "daysOfWeek": ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
            "monthNames": ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
            "firstDay": 1
        },
        ranges: {
            'Hoy': [moment(), moment()], 'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Últimos 7 días': [moment().subtract(6, 'days'), moment()], 'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este Mes': [moment().startOf('month'), moment().endOf('month')],
            'Mes Pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    });

    // Inicialización de DataTables en modo Server-Side
    const table = $('#global-sales-table').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": apiURL,
            "type": "POST",
            "data": function (d) {
                // Enviar el rango de fechas al servidor
                const picker = dateRangeInput.data('daterangepicker');
                if (picker && picker.startDate && picker.endDate && dateRangeInput.val()) {
                    d.startDate = picker.startDate.format('YYYY-MM-DD');
                    d.endDate = picker.endDate.format('YYYY-MM-DD');
                }
            }
        },
        "columns": [
            { "data": "fecha", "render": (data) => moment(data).format('DD/MM/YYYY, h:mm a') },
            { "data": "id", "className": "ticket-id-cell", "render": (data) => '#' + String(data).padStart(6, '0') },
            { "data": "sucursal_nombre" },
            { "data": "cliente_nombre" },
            { "data": "usuario_nombre" },
            { "data": "total", "className": "text-right", "render": (data) => `$${parseFloat(data).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` },
            { "data": "estado", "render": (data) => `<span class="${data === 'Completada' ? 'text-green-400' : 'text-red-500'} font-semibold">${data}</span>` },
            { "data": "id", "className": "text-center", "orderable": false, "searchable": false, "render": (data) => `<button class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-1 px-3 rounded-lg text-xs view-pdf-btn" data-id="${data}" title="Ver PDF"><i class="fas fa-file-pdf"></i></button>` }
        ],
        "order": [[0, 'desc']],
        "language": { "url": "js/es.json" },
        "responsive": true,
        "footerCallback": function (row, data, start, end, display) {
            const api = this.api();
            let totalCompletadas = 0;
            
            // Sumar solo las ventas completadas de la data recibida para la página actual
            api.rows({ page: 'current' }).data().each(function (rowData) {
                if (rowData.estado === 'Completada') {
                    totalCompletadas += parseFloat(rowData.total) || 0;
                }
            });

            // Usar AutoNumeric para formatear el total en el footer
            if (totalFooterAn) {
                totalFooterAn.set(totalCompletadas);
            }
        },
        dom: "<'dt-top-controls'<'left-controls'l><'center-controls'B><'right-controls'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'dt-bottom-controls'<'left-controls'i><'right-controls'p>>",
        buttons: [
            { extend: 'excelHtml5', text: '<i class="far fa-file-excel"></i> Excel', titleAttr: 'Exportar a Excel', title: 'Reporte_Global_de_Ventas', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
            { extend: 'pdfHtml5', text: '<i class="far fa-file-pdf"></i> PDF', titleAttr: 'Exportar a PDF', title: 'Reporte Global de Ventas', orientation: 'landscape', pageSize: 'A4', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
            { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', titleAttr: 'Imprimir tabla', title: 'Reporte Global de Ventas', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } }
        ],
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
    });

    // Eventos del Daterangepicker
    dateRangeInput.on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        table.draw(); // Redibujar la tabla para aplicar el filtro
    });
    dateRangeInput.on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
        table.draw();
    });

    // Delegación de eventos para los botones de PDF
    $('#global-sales-table tbody').on('click', '.view-pdf-btn', function () {
        const saleId = $(this).data('id');
        window.open(`${BASE_URL}/generateQuote?id=${saleId}`, '_blank');
    });

    // Inicializar AutoNumeric en el footer
    const totalFooterCell = document.getElementById('current-view-total');
    if (totalFooterCell) {
        totalFooterAn = new AutoNumeric(totalFooterCell, {
            currencySymbol: '$',
            currencySymbolPlacement: 'p',
            decimalCharacter: '.',
            digitGroupSeparator: ',',
            decimalPlaces: 2
        });
    }
});
