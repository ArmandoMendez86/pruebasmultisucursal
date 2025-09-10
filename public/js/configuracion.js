// Archivo: /public/js/configuracion.js

document.addEventListener('DOMContentLoaded', function () {
    // --- Referencias a elementos del DOM ---
    const configForm = document.getElementById('config-form');
    const formFields = ['nombre', 'direccion', 'telefono', 'email', 'logo_url'];
    const saveButton = document.getElementById('save-config-btn');
    const adminMessage = document.getElementById('admin-only-message');

    const fileInput = document.getElementById('logo');
    const logoUrlInput = document.getElementById('logo_url');
    const preview = document.getElementById('logo-preview');

    /**
     * Sube el archivo del logo al servidor.
     * @param {File} file - El archivo de imagen a subir.
     * @returns {Promise<string>} La URL relativa del logo subido.
     */
    async function uploadLogo(file) {
        const fd = new FormData();
        fd.append('logo', file);
        // Llama al endpoint para subir el logo
        const resp = await fetch(`${BASE_URL}/uploadBranchLogo`, { method: 'POST', body: fd });
        const result = await resp.json();
        if (!result.success) {
            throw new Error(result.message || 'Error al subir el logo');
        }
        return result.url; // Retorna la nueva URL
    }

    // --- Event Listener para el input de archivo ---
    if (fileInput) {
        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files && e.target.files[0];
            if (!file) return; // Si no se selecciona archivo, no hacer nada

            try {
                if (saveButton) saveButton.disabled = true; // Deshabilitar botón mientras sube
                
                // 1. Subir el logo y obtener la nueva URL
                const url = await uploadLogo(file);
                
                // 2. Actualizar el campo de texto oculto con la nueva URL
                logoUrlInput.value = url;
                
                // 3. Construir la ruta completa para mostrar la imagen
                const fullUrl = '/multi-sucursal/public' + url;
                
                // 4. Actualizar la vista previa en el formulario
                if (preview) preview.src = fullUrl;

                // 5. MODIFICACIÓN: Actualizar el logo en la barra de navegación
                const sidebarLogo = document.getElementById('sidebar-logo');
                if (sidebarLogo) {
                    sidebarLogo.src = fullUrl;
                }
                
                if (typeof showToast === 'function') showToast('Logo actualizado con éxito.', 'success');

            } catch (err) {
                if (typeof showToast === 'function') showToast(err.message || 'Error al subir el logo', 'error');
            } finally {
                if (saveButton) saveButton.disabled = false; // Rehabilitar el botón
            }
        });
    }

    /**
     * Obtiene la configuración actual de la sucursal desde el servidor.
     */
    async function fetchBranchConfig() {
        try {
            const response = await fetch(`${BASE_URL}/getBranchConfig`);
            const result = await response.json();
            if (result.success) {
                const cfg = result.data || {};
                // Rellenar los campos del formulario con los datos obtenidos
                formFields.forEach((field) => {
                    const input = document.getElementById(field);
                    if (input) input.value = cfg[field] || '';
                });
                // Actualizar la vista previa del logo si existe una URL
                if (cfg.logo_url && preview) {
                    preview.src = '/multi-sucursal/public' + cfg.logo_url;
                }
            } else {
                if (typeof showToast === 'function') showToast(result.message, 'error');
            }
        } catch {
            if (typeof showToast === 'function') showToast('No se pudo cargar la configuración.', 'error');
        }
    }

    /**
     * Maneja el envío del formulario para guardar los cambios.
     * @param {Event} e - El evento de envío del formulario.
     */
    async function handleFormSubmit(e) {
        e.preventDefault();
        const payload = {};
        // Recolectar los datos del formulario
        formFields.forEach((f) => { 
            const el = document.getElementById(f); 
            if (el) payload[f] = el.value; 
        });

        try {
            if (saveButton) saveButton.disabled = true;
            const resp = await fetch(`${BASE_URL}/updateBranchConfig`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const result = await resp.json();
            if (result.success) {
                if (typeof showToast === 'function') showToast('Cambios guardados.', 'success');
                // Actualizar el nombre de la sucursal en la barra lateral
                const elem = document.getElementById('sucursal-nombre');
                if (elem && payload.nombre) elem.textContent = payload.nombre;
                
                // Si la URL del logo cambió, actualizar las imágenes
                if (payload.logo_url) {
                    const fullUrl = '/multi-sucursal/public' + payload.logo_url;
                    if (preview) preview.src = fullUrl;
                    const sidebarLogo = document.getElementById('sidebar-logo');
                    if (sidebarLogo) sidebarLogo.src = fullUrl;
                }
            } else {
                if (typeof showToast === 'function') showToast(result.message, 'error');
            }
        } catch {
            if (typeof showToast === 'function') showToast('No se pudo guardar la configuración.', 'error');
        } finally {
            if (saveButton) saveButton.disabled = false;
        }
    }

    // Asignar el manejador de eventos al formulario
    if (configForm) configForm.addEventListener('submit', handleFormSubmit);
    
    // Cargar la configuración inicial al entrar a la página
    fetchBranchConfig();
});
