// Archivo: /public/js/gastos.js

document.addEventListener('DOMContentLoaded', function () {
    // --- Referencias a elementos del DOM ---
    const addExpenseBtn = document.getElementById('add-expense-btn');
    const expenseModal = document.getElementById('expense-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const expenseForm = document.getElementById('expense-form');
    const modalTitle = document.getElementById('modal-title');

    let gastosDataTable;
    let montoInputAn; // Variable para la instancia de AutoNumeric

    // --- Funciones para manejar el Modal ---
    const showModal = () => expenseModal.classList.remove('hidden');
    const hideModal = () => expenseModal.classList.add('hidden');

    function prepareNewExpenseForm() {
        expenseForm.reset();
        if (montoInputAn) {
            montoInputAn.clear(); // Limpiar el valor de AutoNumeric
        }
        document.getElementById('expense-id').value = '';
        modalTitle.textContent = 'Registrar Nuevo Gasto';
        showModal();
    }

    // --- Lógica de DataTables con Server-Side ---
    function initializeGastosDataTable() {
        gastosDataTable = $('#gastosTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: `${BASE_URL}/getGastosServerSide`,
                type: 'POST'
            },
            columns: [
                {
                    data: 'fecha',
                    render: (data) => new Date(data).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
                },
                { data: 'categoria_gasto', className: 'font-semibold text-white' },
                { data: 'descripcion' },
                {
                    data: 'monto',
                    className: 'text-right font-mono text-red-400',
                    render: function (data, type, row) {
                        // Solo formateamos para la vista ('display')
                        if (type === 'display') {
                            const number = parseFloat(data) || 0;
                            const hasDebt = number > 0;
                            // Usamos la API Intl para un formato de moneda correcto y localizado
                            const formattedCurrency = new Intl.NumberFormat('es-MX', {
                                style: 'currency',
                                currency: 'MXN'
                            }).format(number); // -> $1,500.00

                            const colorClass = hasDebt ? 'text-red-400' : 'text-green-400';
                            return `<span class="font-mono ${colorClass}">${formattedCurrency}</span>`;
                        }
                        // Para ordenar, buscar, etc., usamos el dato original (el número)
                        return data;
                    }
                },
                {
                    data: 'id',
                    className: 'text-center',
                    orderable: false,
                    searchable: false,
                    render: (data) => `
                        <div class="flex items-center justify-center flex-nowrap">
                            <button data-id="${data}" class="edit-btn text-blue-400 hover:text-blue-300 px-2" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                            <button data-id="${data}" class="delete-btn text-red-500 hover:text-red-400 px-2" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    `
                }
            ],
            responsive: true,
            paging: true,
            searching: true,
            info: true,
            lengthChange: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            order: [[0, 'desc']],
            language: {
                search: "Buscar:",
                searchPlaceholder: "Buscar gasto...",
                zeroRecords: "No se encontraron gastos",
                emptyTable: "No hay gastos registrados",
                info: "Mostrando _START_ a _END_ de _TOTAL_ gastos",
                infoEmpty: "Mostrando 0 a 0 de 0 gastos",
                paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" },
                processing: "Procesando..."
            },
            dom: '<"flex flex-col md:flex-row justify-between items-center mb-4 gap-4" <"flex items-center" l> <"ml-auto" f> > rt <"flex justify-between items-center mt-4"ip>'
        });
    }

    async function handleEditExpense(id) {
        try {
            const response = await fetch(`${BASE_URL}/getExpense?id=${id}`);
            const result = await response.json();
            if (result.success) {
                const expense = result.data;
                document.getElementById('expense-id').value = expense.id;
                document.getElementById('categoria_gasto').value = expense.categoria_gasto;
                document.getElementById('descripcion').value = expense.descripcion;

                // Usar el método set de AutoNumeric para formatear el valor
                if (montoInputAn) {
                    montoInputAn.set(expense.monto);
                }

                modalTitle.textContent = 'Editar Gasto';
                showModal();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudieron obtener los datos del gasto.', 'error');
        }
    }

    async function handleDeleteExpense(id) {
        const confirmed = await showConfirm('¿Estás seguro de que quieres eliminar este gasto?');
        if (!confirmed) return;
        try {
            const response = await fetch(`${BASE_URL}/deleteExpense`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            const result = await response.json();
            if (result.success) {
                showToast('Gasto eliminado exitosamente.', 'success');
                gastosDataTable.ajax.reload(null, false);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudo eliminar el gasto.', 'error');
        }
    }

    async function handleFormSubmit(event) {
        event.preventDefault();
        const formData = new FormData(expenseForm);
        const expenseData = Object.fromEntries(formData.entries());
        const expenseId = expenseData.id;

        // Obtener el valor numérico crudo de AutoNumeric
        if (montoInputAn) {
            expenseData.monto = montoInputAn.getNumericString();
        }

        const url = expenseId ? `${BASE_URL}/updateExpense` : `${BASE_URL}/createExpense`;

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(expenseData)
            });
            const result = await response.json();
            if (result.success) {
                hideModal();
                gastosDataTable.ajax.reload(null, false);
                showToast(`Gasto ${expenseId ? 'actualizado' : 'registrado'} exitosamente.`, 'success');
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudo conectar con el servidor.', 'error');
        }
    }

    // --- Asignación de Eventos ---
    addExpenseBtn.addEventListener('click', prepareNewExpenseForm);
    closeModalBtn.addEventListener('click', hideModal);
    cancelBtn.addEventListener('click', hideModal);
    expenseForm.addEventListener('submit', handleFormSubmit);

    $('#gastosTable tbody').on('click', 'button', function (event) {
        const target = $(event.currentTarget);
        const id = target.data('id');
        if (target.hasClass('edit-btn')) {
            handleEditExpense(id);
        } else if (target.hasClass('delete-btn')) {
            handleDeleteExpense(id);
        }
    });

    // --- Carga Inicial ---
    initializeGastosDataTable();

    // Inicializar AutoNumeric en el campo de monto
    const montoInput = document.getElementById('monto');
    if (montoInput) {
        montoInputAn = new AutoNumeric(montoInput, {
            currencySymbol: '', // El '$' ya está visualmente
            decimalCharacter: '.',
            digitGroupSeparator: ',',
            decimalPlaces: 2,
            minimumValue: '0'
        });
    }
});
