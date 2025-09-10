// Archivo: /public/js/toast.js

function showToast(message, type = 'success') {
    // Crear el contenedor de toasts si no existe
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'fixed top-5 right-5 z-[100] space-y-3 w-full max-w-xs';
        document.body.appendChild(toastContainer);
    }

    // Crear el elemento del toast
    const toast = document.createElement('div');
    
    const icons = {
        success: '<i class="fas fa-check-circle"></i>',
        error: '<i class="fas fa-times-circle"></i>',
        info: '<i class="fas fa-info-circle"></i>' 
    };
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-600',
        info: 'bg-blue-500'
    };

     const icon = icons[type] || icons['success'];
    const color = colors[type] || colors['success'];


    toast.className = `flex items-center p-4 rounded-lg shadow-lg text-white ${colors[type]} transform transition-all duration-300 ease-in-out translate-x-full opacity-0`;
    toast.innerHTML = `
        <div class="text-xl mr-3">${icons[type]}</div>
        <div>${message}</div>
    `;

    toastContainer.appendChild(toast);

    // Animación de entrada
    requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
    });

    // --- CORRECCIÓN ---
    // Lógica de salida más robusta
    const removeToast = () => {
        toast.classList.add('opacity-0');
        toast.addEventListener('transitionend', () => {
            toast.remove();
            if (toastContainer.children.length === 0) {
                toastContainer.remove();
            }
        }, { once: true });
    };

    setTimeout(removeToast, 3000); // Iniciar la eliminación después de 3 segundos
}
