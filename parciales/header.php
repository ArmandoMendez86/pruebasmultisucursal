<?php
// Archivo: /parciales/header.php
?>
<header class="flex-shrink-0 bg-[#1e293b] border-b border-gray-700 shadow-md md:hidden">
    <div class="flex items-center justify-between p-4">
        <!-- Botón de Hamburguesa (Solo visible en móviles) -->
        <button id="mobile-menu-button" class="text-gray-300 hover:text-white focus:outline-none">
            <i class="fas fa-bars text-xl"></i>
        </button>
        
        <!-- Título de la Sucursal (para dar contexto en móvil) -->
        <div class="text-lg font-semibold text-white">
            <?php echo isset($_SESSION['branch_name']) ? htmlspecialchars($_SESSION['branch_name']) : 'Menú'; ?>
        </div>

        <!-- Espacio reservado para futuros íconos o notificaciones -->
        <div class="w-8"></div>
    </div>
</header>