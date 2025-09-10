// Archivo: /public/js/login.js

document.addEventListener('DOMContentLoaded', function() {

    const loginForm = document.querySelector('form');
    const messageDiv = document.createElement('div');
    messageDiv.className = 'text-center text-sm mt-4';
    loginForm.querySelector('button').insertAdjacentElement('afterend', messageDiv);

    loginForm.addEventListener('submit', function(event) {
        event.preventDefault();
        messageDiv.textContent = '';
        messageDiv.className = 'text-center text-sm mt-4';

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        const formData = {
            username: username,
            password: password
        };

        const apiUrl = `${BASE_URL}/login`;

        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                // Se o status da resposta não for 2xx, lemos a mensagem de erro do JSON
                return response.json().then(errorData => {
                    throw new Error(errorData.message || 'Erro na resposta do servidor');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                messageDiv.textContent = data.message;
                messageDiv.classList.add('text-green-400');

                setTimeout(() => {
                    // --- NOVA LÓGICA DE REDIRECIONAMENTO CONDICIONAL ---
                    if (data.user && data.user.rol === 'Administrador' && data.requires_cash_opening) {
                        // Se for um administrador e a caixa não foi aberta, redirecionar para a página de abertura
                        window.location.href = 'apertura_caja.php';
                    } else {
                        // Caso contrário, redirecionar para o dashboard ou página principal
                        window.location.href = 'dashboard.php'; 
                    }
                }, 1000);

            } else {
                // Este 'else' agora é um backup, o tratamento de erros é feito acima.
                messageDiv.textContent = data.message || 'Erro desconhecido.';
                messageDiv.classList.add('text-red-500');
            }
        })
        .catch(error => {
            console.error('Erro na solicitação:', error);
            // Mostramos a mensagem de erro que capturamos.
            messageDiv.textContent = error.message;
            messageDiv.classList.add('text-red-500');
        });
    });
});
