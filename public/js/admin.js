// Archivo: public/js/admin.js

document.addEventListener('DOMContentLoaded', () => {
    // --- LÓGICA DE PESTAÑAS ---
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const tabId = button.dataset.tab;
            tabContents.forEach(content => {
                content.id === `tab-${tabId}` ? content.classList.add('active') : content.classList.remove('active');
            });
        });
    });

    // --- CACHE DE DATOS ---
    let allSucursales = [];
    let allUsuarios = [];

    // --- GESTIÓN DE SUCURSALES ---
    const sucursalModalContainer = document.getElementById('sucursal-modal');
    const addSucursalBtn = document.getElementById('add-sucursal-btn');
    const sucursalesTableBody = document.getElementById('sucursales-table-body');

    const renderSucursalModal = (sucursal = null) => {
        const isEditing = sucursal !== null;
        sucursalModalContainer.innerHTML = `
            <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-lg">
                <div class="p-6 border-b border-[var(--color-border)] flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">${isEditing ? 'Editar Sucursal' : 'Nueva Sucursal'}</h2>
                    <button class="close-modal-btn text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl">&times;</button>
                </div>
                <form id="sucursal-form" class="p-6 space-y-4">
                    <input type="hidden" name="id" value="${sucursal?.id || ''}">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-[var(--color-text-secondary)]">Nombre</label>
                        <input type="text" name="nombre" required class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]" value="${sucursal?.nombre || ''}">
                    </div>
                    <div>
                        <label for="direccion" class="block text-sm font-medium text-[var(--color-text-secondary)]">Dirección</label>
                        <input type="text" name="direccion" required class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]" value="${sucursal?.direccion || ''}">
                    </div>
                    <div>
                        <label for="telefono" class="block text-sm font-medium text-[var(--color-text-secondary)]">Teléfono</label>
                        <input type="tel" name="telefono" class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]" value="${sucursal?.telefono || ''}">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-[var(--color-text-secondary)]">Email</label>
                        <input type="email" name="email" class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]" value="${sucursal?.email || ''}">
                    </div>
                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" class="close-modal-btn bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg">Cancelar</button>
                        <button type="submit" class="bg-green-600 hover:bg-green-500 font-bold py-2 px-6 rounded-lg text-white">Guardar</button>
                    </div>
                </form>
            </div>`;
        sucursalModalContainer.classList.remove('hidden');
        addModalListeners(sucursalModalContainer, handleSucursalFormSubmit);
    };

    const loadSucursales = async () => {
        try {
            const response = await fetch(`${BASE_URL}/getSucursales`);
            const result = await response.json();
            if (result.success) {
                allSucursales = result.data;
                renderSucursales(allSucursales);
            } else showToast(result.message, 'error');
        } catch (error) { showToast('Error de conexión al cargar sucursales.', 'error'); }
    };

    const renderSucursales = (sucursales) => {
        sucursalesTableBody.innerHTML = '';
        if (sucursales.length === 0) {
            sucursalesTableBody.innerHTML = `<tr><td colspan="4" class="text-center py-10 text-[var(--color-text-secondary)]">No hay sucursales registradas.</td></tr>`;
            return;
        }
        sucursales.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-[var(--color-text-primary)]">${s.nombre}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">${s.direccion}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">${s.telefono || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                    <button data-id="${s.id}" class="edit-sucursal-btn text-indigo-400 hover:text-indigo-300 mr-3" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                    <button data-id="${s.id}" class="delete-sucursal-btn text-red-400 hover:text-red-300" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                </td>`;
            sucursalesTableBody.appendChild(tr);
        });
    };
    
    const handleSucursalFormSubmit = async (e) => {
        e.preventDefault();
        const id = e.target.querySelector('input[name="id"]').value;
        const data = Object.fromEntries(new FormData(e.target).entries());
        const url = id ? `${BASE_URL}/updateSucursal` : `${BASE_URL}/createSucursal`;
        try {
            const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                sucursalModalContainer.classList.add('hidden');
                await loadSucursales();
            } else showToast(result.message, 'error');
        } catch (error) { showToast('Error de conexión al guardar la sucursal.', 'error'); }
    };

    addSucursalBtn.addEventListener('click', () => renderSucursalModal());
    sucursalesTableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-sucursal-btn');
        if (editBtn) renderSucursalModal(allSucursales.find(s => s.id == editBtn.dataset.id));
        
        const deleteBtn = e.target.closest('.delete-sucursal-btn');
        if (deleteBtn) {
             const id = deleteBtn.dataset.id;
             const confirmed = await showConfirm('¿Seguro que quieres eliminar esta sucursal? Esta acción no se puede deshacer.');
             if(confirmed) {
                try {
                    const response = await fetch(`${BASE_URL}/deleteSucursal`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
                    const result = await response.json();
                    if (result.success) {
                        showToast(result.message, 'success');
                        await loadSucursales();
                    } else showToast(result.message, 'error');
                } catch (error) { showToast('Error de conexión al eliminar.', 'error'); }
             }
        }
    });

    // --- GESTIÓN DE USUARIOS ---
    const usuarioModalContainer = document.getElementById('usuario-modal');
    const addUsuarioBtn = document.getElementById('add-usuario-btn');
    const usuariosTableBody = document.getElementById('usuarios-table-body');

    const renderUsuarioModal = (usuario = null) => {
        const isEditing = usuario !== null;
        const sucursalOptions = allSucursales.map(s => `<option value="${s.id}" ${usuario?.id_sucursal == s.id ? 'selected' : ''}>${s.nombre}</option>`).join('');
        usuarioModalContainer.innerHTML = `
            <div class="bg-[var(--color-bg-secondary)] rounded-lg shadow-xl w-full max-w-lg">
                <div class="p-6 border-b border-[var(--color-border)] flex justify-between items-center">
                    <h2 class="text-2xl font-bold text-[var(--color-text-primary)]">${isEditing ? 'Editar Usuario' : 'Nuevo Usuario'}</h2>
                    <button class="close-modal-btn text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] text-2xl">&times;</button>
                </div>
                <form id="usuario-form" class="p-6 space-y-4">
                    <input type="hidden" name="id" value="${usuario?.id || ''}">
                    <div>
                        <label class="block text-sm font-medium text-[var(--color-text-secondary)]">Nombre Completo</label>
                        <input type="text" name="nombre" required class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]" value="${usuario?.nombre || ''}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--color-text-secondary)]">Username</label>
                        <input type="text" name="username" required class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]" value="${usuario?.username || ''}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[var(--color-text-secondary)]">Contraseña</label>
                        <input type="password" name="password" class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]" placeholder="${isEditing ? 'Dejar en blanco para no cambiar' : ''}" ${!isEditing ? 'required' : ''}>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-[var(--color-text-secondary)]">Rol</label>
                            <select name="rol" required class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]">
                                <option value="Vendedor" ${usuario?.rol === 'Vendedor' ? 'selected' : ''}>Vendedor</option>
                                <option value="Super" ${usuario?.rol === 'Super' ? 'selected' : ''}>Superadmin</option>
                                <option value="Administrador" ${usuario?.rol === 'Administrador' ? 'selected' : ''}>Administrador</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[var(--color-text-secondary)]">Sucursal</label>
                            <select name="id_sucursal" required class="mt-1 block w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)]">${sucursalOptions}</select>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="button" class="close-modal-btn bg-[var(--color-border)] hover:bg-[var(--color-text-secondary)] text-[var(--color-text-primary)] font-bold py-2 px-4 rounded-lg">Cancelar</button>
                        <button type="submit" class="bg-green-600 hover:bg-green-500 font-bold py-2 px-6 rounded-lg text-white">Guardar</button>
                    </div>
                </form>
            </div>`;
        usuarioModalContainer.classList.remove('hidden');
        addModalListeners(usuarioModalContainer, handleUsuarioFormSubmit);
    };
    
    const loadUsuarios = async () => {
        try {
            const response = await fetch(`${BASE_URL}/getUsuarios`);
            const result = await response.json();
            if (result.success) {
                allUsuarios = result.data;
                renderUsuarios(allUsuarios);
            } else showToast(result.message, 'error');
        } catch (error) { showToast('Error de conexión al cargar usuarios.', 'error'); }
    };

    const renderUsuarios = (usuarios) => {
        usuariosTableBody.innerHTML = '';
        if (usuarios.length === 0) {
            usuariosTableBody.innerHTML = `<tr><td colspan="5" class="text-center py-10 text-[var(--color-text-secondary)]">No hay usuarios registrados.</td></tr>`;
            return;
        }
        usuarios.forEach(u => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-[var(--color-text-primary)]">${u.nombre}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">${u.username}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">${u.rol}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--color-text-secondary)]">${u.sucursal_nombre || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                    <button data-id="${u.id}" class="edit-usuario-btn text-indigo-400 hover:text-indigo-300 mr-3" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                    <button data-id="${u.id}" class="delete-usuario-btn text-red-400 hover:text-red-300" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                </td>`;
            usuariosTableBody.appendChild(tr);
        });
    };

    const handleUsuarioFormSubmit = async (e) => {
        e.preventDefault();
        const id = e.target.querySelector('input[name="id"]').value;
        const data = Object.fromEntries(new FormData(e.target).entries());
        if (id && data.password === '') delete data.password;
        const url = id ? `${BASE_URL}/updateUsuario` : `${BASE_URL}/createUsuario`;
        try {
            const response = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                usuarioModalContainer.classList.add('hidden');
                await loadUsuarios();
            } else showToast(result.message, 'error');
        } catch (error) { showToast('Error de conexión al guardar el usuario.', 'error'); }
    };

    addUsuarioBtn.addEventListener('click', () => renderUsuarioModal());
    usuariosTableBody.addEventListener('click', async (e) => {
        const editBtn = e.target.closest('.edit-usuario-btn');
        if (editBtn) renderUsuarioModal(allUsuarios.find(u => u.id == editBtn.dataset.id));

        const deleteBtn = e.target.closest('.delete-usuario-btn');
        if (deleteBtn) {
            const id = deleteBtn.dataset.id;
            const confirmed = await showConfirm('¿Seguro que quieres eliminar este usuario?');
            if(confirmed) {
                try {
                    const response = await fetch(`${BASE_URL}/deleteUsuario`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
                    const result = await response.json();
                    if (result.success) {
                        showToast(result.message, 'success');
                        await loadUsuarios();
                    } else showToast(result.message, 'error');
                } catch (error) { showToast('Error de conexión al eliminar.', 'error'); }
            }
        }
    });

    // --- HELPERS ---
    const addModalListeners = (modalContainer, submitHandler) => {
        modalContainer.querySelectorAll('.close-modal-btn').forEach(btn => {
            btn.addEventListener('click', () => modalContainer.classList.add('hidden'));
        });
        modalContainer.querySelector('form').addEventListener('submit', submitHandler);
    };

    // --- CARGA INICIAL ---
    loadSucursales().then(loadUsuarios);
});
