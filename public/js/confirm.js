// Archivo: /public/js/confirm.js

function showConfirm(message, title = 'Confirmación') {
    return new Promise((resolve) => {
        // Crear el overlay del modal
        const modalOverlay = document.createElement('div');
        modalOverlay.className = 'fixed inset-0 z-[90] flex items-center justify-center bg-black bg-opacity-75';

        // Crear el contenido del modal
        const modalContent = document.createElement('div');
        modalContent.className = 'bg-[#1e293b] rounded-lg shadow-xl w-full max-w-md p-6 transform transition-all duration-300 ease-in-out scale-95 opacity-0';

        modalContent.innerHTML = `
            <h2 class="text-2xl font-bold text-white mb-4">${title}</h2>
            <p class="text-gray-300 mb-6">${message}</p>
            <div class="flex justify-end space-x-4">
                <button id="confirm-cancel-btn" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg">Cancelar</button>
                <button id="confirm-ok-btn" class="bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-4 rounded-lg">Confirmar</button>
            </div>
        `;

        modalOverlay.appendChild(modalContent);
        document.body.appendChild(modalOverlay);

        const okButton = modalContent.querySelector('#confirm-ok-btn');
        const cancelButton = modalContent.querySelector('#confirm-cancel-btn');

        // Animación de entrada (usando requestAnimationFrame para mayor fiabilidad)
        requestAnimationFrame(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        });

        // --- CORRECCIÓN DEFINITIVA ---
        // Reemplazamos el event listener 'transitionend' por un setTimeout,
        // que es más robusto y menos propenso a fallos.
        const close = (value) => {
            // Inicia la animación de salida
            modalContent.classList.add('scale-95', 'opacity-0');

            // Espera a que la animación termine (300ms) para remover el elemento y resolver la promesa.
            setTimeout(() => {
                modalOverlay.remove();
                resolve(value);
            }, 300);
        };

        // Event listeners para los botones
        okButton.addEventListener('click', () => close(true));
        cancelButton.addEventListener('click', () => close(false));
    });
}


