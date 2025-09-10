// Archivo: /public/js/impresoras.js

document.addEventListener('DOMContentLoaded', function() {
    const printerForm = document.getElementById('printer-form');
    const findPrintersBtn = document.getElementById('find-printers-btn');
    const printerSelect = document.getElementById('impresora_tickets');
    let savedPrinter = '';

    /**
     * Obtiene la impresora guardada para este usuario y la muestra.
     */
    async function fetchUserPrinter() {
        try {
            const response = await fetch(`${BASE_URL}/getPrinterConfig`);
            const result = await response.json();
            if (result.success) {
                savedPrinter = result.data.impresora_tickets || '';
                if (savedPrinter) {
                    // Si ya hay una impresora guardada, la mostramos.
                    // El usuario puede buscar otras si lo desea.
                    printerSelect.innerHTML = `<option value="${savedPrinter}" selected>${savedPrinter}</option>`;
                }
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudo cargar la configuración de la impresora.', 'error');
        }
    }

    /**
     * Guarda la impresora seleccionada para el usuario actual.
     */
    async function handleFormSubmit(event) {
        event.preventDefault();
        const formData = new FormData(printerForm);
        const configData = Object.fromEntries(formData.entries());
        try {
            const response = await fetch(`${BASE_URL}/updatePrinterConfig`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(configData)
            });
            const result = await response.json();
            if (result.success) {
                showToast('Impresora guardada exitosamente.', 'success');
                savedPrinter = configData.impresora_tickets; // Actualiza el valor guardado localmente
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('No se pudo guardar la configuración.', 'error');
        }
    }
    
    // Asignación de eventos
    printerForm.addEventListener('submit', handleFormSubmit);
    findPrintersBtn.addEventListener('click', () => findPrinters(printerSelect, savedPrinter));

    // Carga inicial
    fetchUserPrinter();
});
