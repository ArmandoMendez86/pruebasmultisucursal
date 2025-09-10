// Archivo: /public/js/apertura_caja.js

document.addEventListener('DOMContentLoaded', function() {
    const cashOpeningForm = document.getElementById('cash-opening-form');
    const montoInicialInput = document.getElementById('monto-inicial');
    const openCashBtn = document.getElementById('open-cash-btn');
    const messageArea = document.getElementById('message-area');

    // Función para mostrar mensajes (reutiliza showToast si está disponible)
    function displayMessage(message, type = 'info') {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            messageArea.innerHTML = `<p class="${type === 'error' ? 'text-red-400' : 'text-green-400'}">${message}</p>`;
            messageArea.style.display = 'block';
        }
    }

    // Verificar si el usuario ya ha abierto la caja para hoy
    // Esto es una doble verificación, ya que el LoginController ya lo hizo.
    // Es útil si el usuario accede directamente a esta URL.
    async function checkCashOpeningStatus() {
        try {
            const response = await fetch(`${BASE_URL}/checkApertura`);
            const result = await response.json();

            if (result.success && result.opened) {
                // Caja ya abierta, redirigir al dashboard
                displayMessage('La caja ya ha sido abierta para hoy. Redirigiendo...', 'info');
                setTimeout(() => {
                    window.location.href = 'dashboard.php'; // O la página principal de tu sistema
                }, 1500);
            } else if (result.success && !result.opened) {
                // Caja no abierta, permitir ingreso
                montoInicialInput.focus();
            } else {
                // Error o acceso no autorizado
                displayMessage(result.message || 'Error al verificar el estado de la caja.', 'error');
                // Opcional: redirigir a login si no está autorizado
                if (response.status === 403) {
                     setTimeout(() => { window.location.href = 'login.php'; }, 2000);
                }
            }
        } catch (error) {
            console.error('Error al verificar el estado de la caja:', error);
            displayMessage('Error de conexión al verificar el estado de la caja.', 'error');
        }
    }

    // Manejar el envío del formulario
    cashOpeningForm.addEventListener('submit', async function(event) {
        event.preventDefault(); // Prevenir el envío por defecto del formulario

        const montoInicial = parseFloat(montoInicialInput.value);

        if (isNaN(montoInicial) || montoInicial < 0) {
            displayMessage('Por favor, ingresa un monto inicial válido (número positivo).', 'error');
            return;
        }

        openCashBtn.disabled = true;
        openCashBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Abriendo Caja...';

        try {
            const response = await fetch(`${BASE_URL}/registrarApertura`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ monto_inicial: montoInicial })
            });

            const result = await response.json();

            if (result.success) {
                displayMessage(result.message || 'Caja abierta exitosamente.', 'success');
                // Redirigir al usuario al dashboard o página principal
                setTimeout(() => {
                    window.location.href = 'dashboard.php'; // Asegúrate de que esta sea la URL correcta
                }, 1500);
            } else {
                displayMessage(result.message || 'Error al abrir la caja.', 'error');
                openCashBtn.disabled = false;
                openCashBtn.innerHTML = '<i class="fas fa-cash-register mr-2"></i> Abrir Caja';
            }
        } catch (error) {
            console.error('Error al enviar la apertura de caja:', error);
            displayMessage('Error de conexión al intentar abrir la caja.', 'error');
            openCashBtn.disabled = false;
            openCashBtn.innerHTML = '<i class="fas fa-cash-register mr-2"></i> Abrir Caja';
        }
    });

    // Cargar el estado de la caja al cargar la página
    checkCashOpeningStatus();
});
