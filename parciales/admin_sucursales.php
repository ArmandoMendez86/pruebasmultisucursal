<!-- Archivo: /public/parciales/admin_sucursales.php -->
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-[var(--color-text-primary)]">Gestión de Sucursales</h2>
    <button id="add-sucursal-btn" class="bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-2 px-4 rounded-lg flex items-center">
        <i class="fas fa-plus mr-2"></i> Nueva Sucursal
    </button>
</div>
<div class="bg-[var(--color-bg-secondary)] rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-[var(--color-border)]">
            <thead class="bg-[var(--color-bg-primary)]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase">Dirección</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-[var(--color-text-secondary)] uppercase">Teléfono</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-[var(--color-text-secondary)] uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody id="sucursales-table-body" class="divide-y divide-[var(--color-border)]"></tbody>
        </table>
    </div>
</div>

<!-- Modal para Sucursales -->
<div id="sucursal-modal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden">
    <!-- Contenido del modal (formulario para crear/editar sucursal) se genera con JS -->
</div>
