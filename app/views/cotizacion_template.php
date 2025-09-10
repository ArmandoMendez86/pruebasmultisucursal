<?php
// Recibimos los datos de la venta desde el controlador
$venta = $data['venta'];
$items = $data['items'];
$subtotal = 0;
$esFactura = isset($venta['estado']) && strtolower($venta['estado']) === 'completada';
$docLabel = $esFactura ? 'FACTURA' : 'PROFORMA';
$badgeCls = $esFactura ? 'bg-slate-100' : 'bg-slate-100';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización #<?php echo htmlspecialchars($venta['id']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            -webkit-print-color-adjust: exact;
            /* Asegura que los colores se impriman en Chrome/Safari */
            print-color-adjust: exact;
        }

        /* Define un tamaño de texto extra pequeño */
        .text-xxs {
            font-size: 0.65rem;
            /* Aproximadamente 10.4px */
            line-height: 0.8rem;
        }

        /* Oculta el botón de imprimir en el PDF final */
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body class="bg-gray-100">

    <div class="no-print fixed bottom-5 right-5">
        <button onclick="window.print()"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-full shadow-lg transition-transform transform hover:scale-110">
            <i class="fas fa-print text-xl"></i>
        </button>
    </div>

    <div class="max-w-3xl mx-auto p-10 bg-white text-gray-800 shadow-lg rounded-lg">

        <header class="flex justify-between items-start pb-6 border-b border-gray-200 mb-6">
            <div>
                <div class="mb-1">
                    <span
                        class="inline-block py-0.5 rounded text-black text-2xl font-bold tracking-widest uppercase <?php echo $badgeCls; ?>">
                        <?php echo $docLabel; ?>
                    </span>
                </div>
                <h2 class="text-lg text-gray-900 flex items-center">
                    <i class="fas fa-store mr-2 text-blue-600"></i> <!-- Icon for branch -->
                    <?php echo htmlspecialchars($venta['sucursal_nombre']); ?>
                </h2>
                <p class="text-sm text-gray-600 flex items-center mt-1">
                    <i class="fas fa-user mr-2 text-gray-500"></i> <!-- Icon for address -->
                    <?php echo 'Usuario: ' . htmlspecialchars($venta['vendedor']); ?>
                </p>
                <p class="text-sm text-gray-600 flex items-center mt-1">
                    <i class="fas fa-map-marker-alt mr-2 text-gray-500"></i> <!-- Icon for address -->
                    <?php echo htmlspecialchars($venta['sucursal_direccion']); ?>
                </p>
                <p class="text-sm text-gray-600 flex items-center">
                    <i class="fas fa-phone mr-2 text-gray-500"></i> <!-- Icon for phone -->
                    Tel: <?php echo htmlspecialchars($venta['sucursal_telefono']); ?>
                </p>
            </div>
            <div class="text-right">
                <!-- <h1 class="text-3xl font-bold text-gray-900 tracking-wider flex items-center justify-end">
                    <i class="fas fa-party-popper mr-3 text-purple-500"></i>
                    MegaPartyGdl
                    <i class="fas fa-party-popper ml-3 text-purple-500"></i>
                </h1> -->
                <p class="text-sm text-gray-600 mt-1 flex items-center justify-end">
                    Folio: <span class="font-semibold ml-2">#<?php echo htmlspecialchars($venta['id']); ?></span>
                    <i class="fas fa-hashtag ml-2 text-gray-500"></i> <!-- Icon for folio -->
                </p>
                <p class="text-sm text-gray-600 flex items-center justify-end">
                    Fecha: <span
                        class="font-semibold ml-2"><?php echo date("d/m/Y", strtotime($venta['fecha'])); ?></span>
                    <i class="fas fa-calendar-alt ml-2 text-gray-500"></i> <!-- Icon for date -->
                </p>
            </div>
        </header>

        <section class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-sm text-gray-600 mb-1 flex items-center">
                <i class="fas fa-user-tag mr-2 text-blue-600"></i> Cliente:
            </h3>
            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($venta['cliente']); ?></p>
            <?php if (!empty($venta['cliente_direccion'])): ?>
                <p class="text-sm text-gray-600 flex items-center mt-1">
                    <i class="fas fa-map-marked-alt mr-2 text-gray-500"></i> <!-- Icon for client address -->
                    <?php echo htmlspecialchars($venta['cliente_direccion']); ?>
                </p>
            <?php endif; ?>
        </section>

        <section class="mt-8">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="py-2 px-3 font-semibold text-xs w-1/6">
                            <div class="flex items-center">
                                <i class="fas fa-sort-numeric-up-alt mr-1"></i> Cant.
                            </div>
                        </th>
                        <th class="py-2 px-3 font-semibold text-xs w-3/6">
                            <div class="flex items-center">
                                <i class="fas fa-box-open mr-1"></i> Descripción
                            </div>
                        </th>
                        <th class="py-2 px-3 font-semibold text-xs w-1/6 text-right">
                            <div class="flex items-center justify-end">
                                <i class="fas fa-dollar-sign mr-1"></i> P. Unitario
                            </div>
                        </th>
                        <th class="py-2 px-3 font-semibold text-xs w-1/6 text-right">
                            <div class="flex items-center justify-end">
                                <i class="fas fa-file-invoice-dollar mr-1"></i> Importe
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>

                        <tr class="border-b border-gray-100">
                            <td class="py-2 px-3 align-top text-sm"><?php echo htmlspecialchars($item['cantidad']); ?></td>
                            <td class="py-2 px-3">
                                <p class="font-semibold text-sm"><?php echo htmlspecialchars($item['producto_nombre']); ?>
                                </p>
                                <p class="text-xxs text-gray-500">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                            </td>
                            <td class="py-2 px-3 text-right align-top text-sm">
                                $<?php echo number_format($item['precio_unitario'], 2); ?></td>
                            <td class="py-2 px-3 text-right align-top text-sm">
                                $<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <section class="mt-8 flex justify-end">
            <div class="w-full max-w-sm space-y-2">
                <?php
                // --- INICIO DE LA LÓGICA CORREGIDA ---
                $total_final = $venta['total'];
                $subtotal_display = $total_final;
                $iva_display = 0;

                if ($venta['iva_aplicado'] == 1) {
                    // Si se aplicó IVA, calculamos el subtotal y el IVA "hacia atrás" desde el total
                    $subtotal_display = $total_final / 1.16;
                    $iva_display = $total_final - $subtotal_display;
                }
                // --- FIN DE LA LÓGICA CORREGIDA ---
                ?>

                <div class="flex justify-between text-gray-700 items-center">
                    <span class="flex items-center"><i class="fas fa-money-bill-alt mr-2"></i> Subtotal:</span>
                    <span>$<?php echo number_format($subtotal_display, 2); ?></span>
                </div>

                <?php if ($venta['iva_aplicado'] == 1): ?>
                    <div class="flex justify-between text-gray-700 items-center">
                        <span class="flex items-center"><i class="fas fa-percent mr-2"></i> IVA (16%):</span>
                        <span>$<?php echo number_format($iva_display, 2); ?></span>
                    </div>
                <?php endif; ?>

                <div
                    class="flex justify-between text-gray-900 font-bold text-xl pt-2 border-t border-gray-200 items-center">
                    <span class="flex items-center"><i class="fas fa-calculator mr-2"></i> Total:</span>
                    <span>$<?php echo number_format($total_final, 2); ?></span>
                </div>
            </div>
        </section>

        <footer class="mt-16 pt-6 border-t border-gray-200 text-center text-gray-500 text-xs">
            <!--   <p class="flex items-center justify-center">
                <i class="fas fa-info-circle mr-2"></i> Esta cotización tiene una vigencia de 15 días.
            </p> -->
            <p class="flex items-center justify-center mt-1">
                <i class="fas fa-heart mr-2 text-red-500"></i> ¡Gracias por su preferencia!
            </p>
            <!--  <p class="flex items-center justify-center mt-2">
                <a href="https://www.facebook.com/Megapartygdloficial" target="_blank" class="text-blue-600 hover:underline flex items-center">
                    <i class="fab fa-facebook-square mr-2 text-blue-600 text-lg"></i>
                    Megapartygdloficial
                </a>
            </p> -->
        </footer>
    </div>
</body>

</html>