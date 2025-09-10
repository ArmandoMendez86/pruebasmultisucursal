<?php
// Archivo: /public/parciales/navegacion.php

$currentPage = basename($_SERVER['PHP_SELF']);
// Se construye la URL completa del logo, usando una imagen por defecto si no existe.
$logo = !empty($_SESSION['logo_url']) ? '/multi-sucursal/public' . $_SESSION['logo_url'] : '/multi-sucursal/public/img/logo.png';
?>

<style>
  /* Estilos para los tooltips y la barra lateral colapsada */
  .tooltip-container {
    position: relative;
  }

  .tooltip-container .tooltip {
    visibility: hidden;
    opacity: 0;
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    margin-left: 12px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.875rem;
    white-space: nowrap;
    z-index: 10;
    transition: opacity 0.2s ease-in-out;
    pointer-events: none;
    background-color: var(--color-bg-secondary);
    color: var(--color-text-primary);
    border: 1px solid var(--color-border);
  }

  #sidebar.w-24 .tooltip-container:hover .tooltip {
    visibility: visible;
    opacity: 1;
  }

  #sidebar.w-24 nav {
    overflow: visible;
  }

  #sidebar.w-24 nav a {
    justify-content: center;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
  }

  /* 1. Ocultar el contenedor del logo en lugar de encogerlo */
  #sidebar.w-24 #logo-container {
    display: none;
  }

  /* 2. Ajustar la cabecera para que no quede un espacio vacío */
  #sidebar.w-24 .sidebar-header {
    height: auto;
    /* Altura automática */
    padding-top: 1rem;
    padding-bottom: 1rem;
  }

  #sidebar-overlay {
    transition: opacity 0.3s ease-in-out;
  }

  /* ===== Espaciado vertical de navegación ===== */
  :root {
    /* Ajusta si quieres más/menos espacio */
    --nav-item-py: clamp(.40rem, .35vw + .25rem, .75rem);
    /* padding vertical de cada link */
    --nav-item-gap: clamp(.28rem, .40vw, .80rem);
    /* separación entre ítems */
  }

  /* Nav vertical Bootstrap: .nav.flex-column */
  .nav.flex-column {
    row-gap: var(--nav-item-gap);
  }

  .nav.flex-column .nav-link {
    padding-block: var(--nav-item-py);
    line-height: 1.25;
  }

  /* Sidebar con .navbar-nav (cuando está en columna) */
  .navbar-nav {
    row-gap: var(--nav-item-gap);
  }

  .navbar-nav .nav-link {
    padding-block: var(--nav-item-py);
    line-height: 1.25;
  }

  /* UL/LI genérico dentro de <nav> (sin Bootstrap/tailwind) */
  nav ul {
    display: flex;
    flex-direction: column;
    row-gap: var(--nav-item-gap);
  }

  nav li>a,
  nav .link {
    display: block;
    padding-block: var(--nav-item-py);
    line-height: 1.25;
  }

  /* Dropdowns: mantienen la misma respiración */
  .dropdown-menu {
    padding-block: calc(var(--nav-item-gap) * .75);
  }

  .dropdown-menu .dropdown-item {
    padding-block: calc(var(--nav-item-py) - .125rem);
  }

  /* Opcional: un pelín más de espacio en pantallas chicas */
  @media (max-width: 576px) {
    :root {
      --nav-item-py: .65rem;
      --nav-item-gap: .55rem;
    }
  }
</style>

<!-- Overlay para el menú en móviles -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

<!-- Barra de Navegación Lateral -->
<aside id="sidebar"
  class="bg-[var(--color-bg-secondary)] text-[var(--color-text-primary)] flex flex-col h-screen w-64 fixed inset-y-0 left-0 z-40 lg:relative lg:translate-x-0 transform -translate-x-full transition-transform duration-300 ease-in-out border-r border-[var(--color-border)]">

  <!-- Cabecera de la barra lateral con logo y nombre -->
  <div
    class="sidebar-header p-6 text-center h-48 flex-shrink-0 flex flex-col justify-center transition-all duration-300 ease-in-out">
    <div id="logo-container"
      class="mx-auto mb-4 w-32 h-32 flex items-center justify-center bg-[var(--color-bg-primary)] rounded-full transition-all duration-300 ease-in-out">
      <!-- MODIFICACIÓN: Se añade un id="sidebar-logo" a la imagen del logo -->
      <img id="sidebar-logo" src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="Logo"
        class="h-32 w-auto rounded-full p-1">
    </div>
    <h2 id="sucursal-nombre" class="text-lg font-semibold text-[var(--color-text-primary)] nav-text">
      <?php echo isset($_SESSION['branch_name']) ? htmlspecialchars($_SESSION['branch_name']) : 'Mi Sucursal'; ?>
    </h2>
  </div>

  <!-- Menú de navegación principal -->
  <nav class="flex-1 px-4 py-2 space-y-1 overflow-y-auto min-h-0 overflow-x-hidden">
    <a href="pos.php"
      class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'pos.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
      <i class="fas fa-cash-register fa-fw w-6 h-6"></i><span class="nav-text ml-3">Ventas</span><span
        class="tooltip">Ventas</span>
    </a>
    <a href="gastos.php"
      class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'gastos.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
      <i class="fas fa-file-invoice-dollar fa-fw w-6 h-6"></i><span class="nav-text ml-3">Gastos</span><span
        class="tooltip">Gastos</span>
    </a>
    <a href="creditos.php"
      class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'creditos.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
      <i class="fas fa-usd fa-fw w-6 h-6"></i><span class="nav-text ml-3">Creditos</span><span
        class="tooltip">Creditos</span>
    </a>
    <a href="reportes.php"
      class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'reportes.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
      <i class="fas fa-chart-line fa-fw w-6 h-6"></i><span class="nav-text ml-3">Reportes</span><span
        class="tooltip">Reportes</span>
    </a>
    <?php if (isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['Administrador', 'Super'])): ?>
      <a href="dashboard.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'dashboard.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
        <i class="fas fa-tachometer-alt fa-fw w-6 h-6"></i><span class="nav-text ml-3">Dashboard</span><span
          class="tooltip">Dashboard</span>
      </a>
      <a href="inventario.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'inventario.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
        <i class="fas fa-boxes-stacked fa-fw w-6 h-6"></i><span class="nav-text ml-3">Inventario</span><span
          class="tooltip">Inventario</span>
      </a>
      <a href="clientes.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'clientes.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
        <i class="fas fa-users fa-fw w-6 h-6"></i><span class="nav-text ml-3">Clientes</span><span
          class="tooltip">Clientes</span>
      </a>
      <a href="configuracion.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'configuracion.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
        <i class="fas fa-cog fa-fw w-6 h-6"></i><span class="nav-text ml-3">Configuración</span><span
          class="tooltip">Configuración</span>
      </a>
     <!--  <a href="reportes_dinamicos.php" class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'report_builder.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
        <i class="fas fa-table mr-2"></i> <span class="nav-text ml-3">Reportes dinamicos</span><span
          class="tooltip">Reportes dinamicos</span>
      </a> -->
    <?php endif; ?>
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'Super'): ?>
      <a href="reporte_global.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'reporte_global.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
        <i class="fas fa-globe-americas fa-fw w-6 h-6"></i><span class="nav-text ml-3">Reporte Global</span><span
          class="tooltip">Reporte Global</span>
      </a>
      <a href="admin.php"
        class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'admin.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
        <i class="fas fa-shield-alt fa-fw w-6 h-6"></i><span class="nav-text ml-3">Gestión</span><span
          class="tooltip">Gestión</span>
      </a>
    <?php endif; ?>
    <a href="impresoras.php"
      class="tooltip-container flex items-center px-4 py-2.5 text-sm font-medium rounded-lg <?php echo ($currentPage == 'impresoras.php') ? 'bg-[var(--color-accent)] text-white' : 'hover:bg-[var(--color-bg-primary)]'; ?>">
      <i class="fas fa-print fa-fw w-6 h-6"></i><span class="nav-text ml-3">Impresoras</span><span
        class="tooltip">Impresoras</span>
    </a>
  </nav>

  <!-- Pie de la barra lateral -->
  <div class="flex-shrink-0">
    <!-- Controles de tema y colapso -->
    <div class="px-4 py-2 border-t border-[var(--color-border)] flex items-center justify-between">
      <button id="theme-toggle"
        class="tooltip-container flex items-center p-2 text-sm font-medium rounded-lg hover:bg-[var(--color-bg-primary)]">
        <i id="theme-toggle-dark-icon" class="fas fa-moon w-5 h-5 hidden"></i>
        <i id="theme-toggle-light-icon" class="fas fa-sun w-5 h-5 hidden"></i>
        <span class="tooltip">Cambiar Tema</span>
      </button>

      <button id="sidebar-toggle"
        class="tooltip-container hidden lg:flex items-center px-4 py-2.5 text-sm font-medium rounded-lg hover:bg-[var(--color-bg-primary)]">
        <i class="fas fa-chevron-left fa-fw w-6 h-6" id="toggle-icon"></i>
        <span class="nav-text ml-3">Ocultar</span>
        <span class="tooltip">Ocular Menú</span>
      </button>
    </div>

    <!-- Información del usuario y botón de cierre de sesión -->
    <div class="p-4 border-t border-[var(--color-border)]">
      <p id="user-nombre" class="text-sm font-semibold text-[var(--color-text-primary)] truncate nav-text">
        <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Usuario'; ?>
      </p>
      <p id="user-rol" class="text-xs text-[var(--color-text-secondary)] nav-text">
        <?php echo isset($_SESSION['rol']) ? htmlspecialchars($_SESSION['rol']) : 'Rol'; ?>
      </p>
      <button id="logout-button"
        class="tooltip-container w-full mt-4 text-left text-sm text-red-500 hover:text-red-600 flex items-center">
        <i class="fas fa-2ml fa-sign-out-alt fa-fw w-6 h-6"></i>
        <span class="nav-text ml-3">Cerrar Sesión</span>
        <span class="tooltip">Cerrar Sesión</span>
      </button>
    </div>
  </div>
</aside>

<script>
  (function () {
    // --- LÓGICA DEL TEMA CLARO/OSCURO ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
    const root = document.documentElement;

    const lightTheme = {
      '--color-bg-primary': '#f1f5f9',
      '--color-bg-secondary': '#ffffff',
      '--color-border': '#e2e8f0',
      '--color-text-primary': '#1e293b',
      '--color-text-secondary': '#64748b',
      '--color-accent': '#4f46e5',
      '--color-accent-hover': '#4338ca',
    };

    const darkTheme = {
      '--color-bg-primary': '#0f172a',
      '--color-bg-secondary': '#1e293b',
      '--color-border': '#334155',
      '--color-text-primary': '#e2e8f0',
      '--color-text-secondary': '#94a3b8',
      '--color-accent': '#6366f1',
      '--color-accent-hover': '#4f46e5',
    };

    function applyTheme(theme) {
      const isDark = theme === 'dark';
      const themePalette = isDark ? darkTheme : lightTheme;

      for (const [key, value] of Object.entries(themePalette)) {
        root.style.setProperty(key, value);
      }

      localStorage.setItem('theme', theme);

      themeToggleLightIcon.classList.toggle('hidden', isDark);
      themeToggleDarkIcon.classList.toggle('hidden', !isDark);
    }

    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = localStorage.getItem('theme') === 'dark' ? 'light' : 'dark';
      applyTheme(currentTheme);
    });

    // Cargar tema al inicio
    const savedTheme = localStorage.getItem('theme') || 'dark'; // Oscuro por defecto
    applyTheme(savedTheme);

    // --- LÓGICA DEL MENÚ (sin cambios) ---
    const logoutButton = document.getElementById("logout-button");
    const toggleButton = document.getElementById("sidebar-toggle");
    const sidebar = document.getElementById('sidebar');

    if (sidebar && window.innerWidth >= 1024 && localStorage.getItem('sidebarCollapsed') === 'true') {
      sidebar.classList.add('w-24');
      sidebar.classList.remove('w-64');
      document.querySelectorAll('.nav-text').forEach(text => text.classList.add('hidden'));
      const toggleIcon = document.getElementById('toggle-icon');
      if (toggleIcon) {
        toggleIcon.classList.remove('fa-chevron-left');
        toggleIcon.classList.add('fa-chevron-right');
      }
    }

    function logout() {
      fetch("index.php?action=logout", {
        method: "POST"
      }).finally(() => {
        window.location.href = "login.php";
      });
    }

    if (logoutButton) {
      logoutButton.addEventListener("click", logout);
    }

    if (sidebar && toggleButton) {
      const toggleIcon = document.getElementById("toggle-icon");
      const navTexts = document.querySelectorAll(".nav-text");
      sidebar.classList.add('transition-all', 'duration-300', 'ease-in-out');
      const applyCollapsedState = () => {
        sidebar.classList.add("w-24");
        sidebar.classList.remove("w-64");
        navTexts.forEach((text) => text.classList.add("hidden"));
        if (toggleIcon) {
          toggleIcon.classList.remove("fa-chevron-left");
          toggleIcon.classList.add("fa-chevron-right");
        }
      };
      const applyExpandedState = () => {
        sidebar.classList.add("w-64");
        sidebar.classList.remove("w-24");
        navTexts.forEach((text) => text.classList.remove("hidden"));
        if (toggleIcon) {
          toggleIcon.classList.add("fa-chevron-left");
          toggleIcon.classList.remove("fa-chevron-right");
        }
      };
      const toggleSidebar = () => {
        const isCollapsed = sidebar.classList.contains("w-24");
        if (isCollapsed) {
          applyExpandedState();
          localStorage.setItem("sidebarCollapsed", "false");
        } else {
          applyCollapsedState();
          localStorage.setItem("sidebarCollapsed", "true");
        }
      };
      toggleButton.addEventListener("click", toggleSidebar);
    }
  })();
</script>