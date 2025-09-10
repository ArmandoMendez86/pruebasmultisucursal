<?php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Impresoras - Sistema POS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    rel="stylesheet" />
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap");

    body {
      font-family: "Inter", sans-serif;
    }

    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: var(--color-bg-primary);
    }

    ::-webkit-scrollbar-thumb {
      background: var(--color-border);
      border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--color-text-secondary);
    }
  </style>
</head>

<body class="bg-[var(--color-bg-primary)] text-[var(--color-text-primary)]">
  <div class="flex h-screen">

    <?php include_once '../parciales/navegacion.php'; ?>

    <!-- Contenido Principal -->
    <main class="flex-1 p-8 overflow-y-auto">
      <header class="lg:hidden flex items-center justify-between bg-[var(--color-bg-secondary)] p-4 shadow-md flex-shrink-0">
        <button id="mobile-menu-button" class="text-[var(--color-text-primary)] focus:outline-none">
          <i class="fas fa-bars text-2xl"></i>
        </button>
        <h1 class="text-lg font-bold text-[var(--color-text-primary)]">Impresoras</h1>
        <div class="w-8"></div>
      </header>
      <h1 class="text-3xl font-bold text-[var(--color-text-primary)] mb-8">
        Configuración de Impresora
      </h1>
      <div class="bg-[var(--color-bg-secondary)] p-6 rounded-lg max-w-lg mx-auto">
        <form id="printer-form" class="space-y-6">
          <div>
            <h3 class="text-lg font-semibold text-[var(--color-text-primary)] mb-2">
              Impresora de Tickets
            </h3>
            <p class="text-sm text-[var(--color-text-secondary)] mb-4">
              Selecciona la impresora térmica que usarás para imprimir los
              recibos en esta estación de trabajo.
            </p>
            <div class="flex items-end gap-4">
              <div class="flex-1">
                <label
                  for="impresora_tickets"
                  class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Impresora Seleccionada</label>
                <select
                  id="impresora_tickets"
                  name="impresora_tickets"
                  class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
                  <option value="">-- Busque impresoras --</option>
                </select>
              </div>
              <button
                type="button"
                id="find-printers-btn"
                class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg flex items-center">
                <i class="fas fa-search mr-2"></i> Buscar
              </button>
            </div>
            <p id="qz-status" class="text-xs text-[var(--color-text-secondary)] mt-2">
              Estado de QZ Tray:
              <span class="font-semibold">Desconectado</span>
            </p>
          </div>
          <div class="pt-4 flex justify-end">
            <button
              type="submit"
              class="bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-2 px-6 rounded-lg">
              Guardar Impresora
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/js-sha256@0.9.0/src/sha256.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2/qz-tray.min.js"></script>
  <script src="js/qz-tray-handler.js"></script>
  <script src="js/rutas.js"></script>
  <script src="js/toast.js"></script>
  <script src="js/impresoras.js"></script>
   <script>
    document.addEventListener('DOMContentLoaded', function() {
      const mobileMenuButton = document.getElementById('mobile-menu-button');
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebar-overlay');

      if (mobileMenuButton && sidebar && overlay) {
        mobileMenuButton.addEventListener('click', (e) => {
          e.stopPropagation();
          sidebar.classList.remove('-translate-x-full');
          overlay.classList.remove('hidden');
        });

        overlay.addEventListener('click', () => {
          sidebar.classList.add('-translate-x-full');
          overlay.classList.add('hidden');
        });
      }
    });
  </script>
</body>

</html>
