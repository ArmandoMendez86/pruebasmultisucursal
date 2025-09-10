// public/js/dashboard.js

document.addEventListener("DOMContentLoaded", function () {
  
  // Formateador para valores monetarios
  const currencyFormatter = new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
  });

  /**
   * Carga los datos del dashboard desde la API y actualiza la UI.
   */
  function loadDashboardData() {
    fetch(`${BASE_URL}/getDashboardData`)
      .then(response => {
        if (!response.ok) {
          throw new Error(`Error HTTP: ${response.status}`);
        }
        return response.json();
      })
      .then(result => {
        if (result.success) {
          const data = result.data;
          updateUI(data);
        } else {
          console.error('Error al cargar los datos del dashboard:', result.message);
          document.getElementById('top-productos-container').textContent = `Error: ${result.message}`;
          document.getElementById('top-clientes-container').textContent = `Error: ${result.message}`;
        }
      })
      .catch(error => {
        console.error('Error de red o en el fetch:', error);
      });
  }

  /**
   * Actualiza el DOM con los datos recibidos.
   * @param {object} data - Los datos del dashboard.
   */
  function updateUI(data) {
    document.getElementById('ingresos-dia').textContent = currencyFormatter.format(data.ingresosHoy || 0);
    document.getElementById('cuentas-cobrar').textContent = currencyFormatter.format(data.cuentasPorCobrar || 0);
    document.getElementById('gastos-dia').textContent = currencyFormatter.format(data.gastosHoy || 0);
    document.getElementById('ventas-dia').textContent = data.conteoVentasHoy || 0;

    // Actualizar Top 5 Productos
    const topProductosContainer = document.getElementById('top-productos-container');
    if (data.topProductos && data.topProductos.length > 0) {
      const productList = data.topProductos.map(p => `
        <div class="flex justify-between items-center py-2 border-b border-[var(--color-border)] last:border-b-0">
          <span class="text-[var(--color-text-secondary)]">${p.nombre}</span>
          <span class="font-semibold text-white bg-blue-500/20 px-2 py-1 rounded-md">${p.total_vendido} vendidos</span>
        </div>
      `).join('');
      topProductosContainer.innerHTML = `<div class="space-y-2">${productList}</div>`;
    } else {
      topProductosContainer.textContent = 'No hay datos de productos para mostrar.';
    }

    // Actualizar Top 5 Clientes
    const topClientesContainer = document.getElementById('top-clientes-container');
    if (data.topClientes && data.topClientes.length > 0) {
      const clientList = data.topClientes.map(c => `
        <div class="flex justify-between items-center py-2 border-b border-[var(--color-border)] last:border-b-0">
          <span class="text-[var(--color-text-secondary)]">${c.nombre}</span>
          <span class="font-semibold text-green-400">${currencyFormatter.format(c.total_comprado)}</span>
        </div>
      `).join('');
      topClientesContainer.innerHTML = `<div class="space-y-2">${clientList}</div>`;
    } else {
      topClientesContainer.textContent = 'No hay datos de clientes para mostrar.';
    }
  }

  // Cargar los datos al iniciar la página
  loadDashboardData();
});
