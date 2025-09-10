<?php
// Archivo: /public/views/configuracion.php
require_once __DIR__ . '/../parciales/verificar_sesion.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuración - Sistema POS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    body {
      font-family: 'Inter', sans-serif;
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
      <header
        class="lg:hidden flex items-center justify-between bg-[var(--color-bg-secondary)] p-4 shadow-md flex-shrink-0">
        <button id="mobile-menu-button" class="text-[var(--color-text-primary)] focus:outline-none">
          <i class="fas fa-bars text-2xl"></i>
        </button>
        <h1 class="text-lg font-bold text-[var(--color-text-primary)]">Configuración</h1>
        <div class="w-8"></div>
      </header>

      <h1 class="text-3xl font-bold text-[var(--color-text-primary)] mb-8">Configuración de la Sucursal</h1>

      <div class="bg-[var(--color-bg-secondary)] p-6 rounded-lg max-w-2xl mx-auto">
        <div id="admin-only-message"
          class="hidden bg-yellow-900 border border-yellow-700 text-yellow-300 px-4 py-3 rounded-lg mb-6">
          <p><i class="fas fa-exclamation-triangle mr-2"></i>Esta sección solo puede ser editada por un Administrador.
          </p>
        </div>

        <form id="config-form" class="space-y-6">
          <div>
            <label for="nombre" class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Nombre de la
              Sucursal</label>
            <input type="text" id="nombre" name="nombre"
              class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"
              required>
          </div>

          <div>
            <label for="direccion"
              class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Dirección</label>
            <textarea id="direccion" name="direccion" rows="3"
              class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]"></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="telefono"
                class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Teléfono</label>
              <input type="tel" id="telefono" name="telefono"
                class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
            </div>
            <div>
              <label for="email" class="block text-sm font-medium text-[var(--color-text-secondary)] mb-1">Email</label>
              <input type="email" id="email" name="email"
                class="w-full bg-[var(--color-bg-primary)] text-[var(--color-text-primary)] rounded-md p-2 border border-[var(--color-border)] focus:ring-[var(--color-accent)] focus:border-[var(--color-accent)]">
            </div>
          </div>

          <div class="space-y-2">
            <label for="logo" class="block text-sm font-medium">Logo de la sucursal</label>
            <div class="flex items-center gap-4">
              <img id="logo-preview"
                src="<?php echo (isset($_SESSION['logo_url']) && $_SESSION['logo_url']) ? htmlspecialchars('/multi-sucursal/public' . $_SESSION['logo_url']) : '/multi-sucursal/public/img/logo-default.png'; ?>"
                alt="Logo" class="w-16 h-16 rounded-full object-cover border" />
              <input type="file" id="logo" name="logo" accept="image/*" class="block text-sm" />
            </div>
            <!-- opcional: muestra la URL persistida -->
            <input type="text" id="logo_url" name="logo_url"
              class="w-full bg-[var(--color-bg-tertiary)] border rounded p-2"
              placeholder="/multi-sucursal/public/img/archivo.webp"
              readonly
              >
            <p class="text-xs text-[var(--color-text-secondary)]">Se optimizará a WebP cuando sea posible. Máx 5MB.</p>
          </div>

          <div class="pt-4 flex justify-end">
            <button type="submit" id="save-config-btn"
              class="bg-[var(--color-accent)] hover:bg-[var(--color-accent-hover)] text-white font-bold py-2 px-6 rounded-lg">Guardar
              Cambios</button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <script src="js/rutas.js"></script>
  <script src="js/toast.js"></script>
  <script src="js/configuracion.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
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