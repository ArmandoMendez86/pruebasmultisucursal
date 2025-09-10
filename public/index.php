<?php
// Archivo: /public/index.php

// --- INICIO DE LA SOLUCIÓN ---
// Esta sección intercepta las peticiones a archivos estáticos (imágenes, CSS, JS)
// y los sirve directamente, evitando que tu router de PHP los procese.

$requested_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Comprobamos si la URL es para una de nuestras carpetas de assets
if (preg_match('/^\/multi-sucursal\/public\/(img|css|js)\//', $requested_uri)) {

    // Construimos la ruta física del archivo en el servidor
    $file_path = __DIR__ . str_replace('/multi-sucursal/public', '', $requested_uri);

    // Si el archivo realmente existe en el disco...
    if (file_exists($file_path) && is_file($file_path)) {

        // Obtenemos el tipo de contenido (ej: 'image/png')
        $mime_type = mime_content_type($file_path);

        if ($mime_type) {
            // Enviamos la cabecera correcta al navegador
            header('Content-Type: ' . $mime_type);
            // Enviamos el contenido del archivo
            readfile($file_path);
            // Detenemos el script para que tu enrutador no intente procesar la imagen
            exit;
        }
    }
}
// --- FIN DE LA SOLUCIÓN ---


// --- INICIO DE TU CÓDIGO ORIGINAL (INTACTO) ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Mexico_City');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

// Cargamos controladores según la acción
if (in_array($action, ['login', 'logout', 'check-session'])) {
    require_once __DIR__ . '/../app/controllers/LoginController.php';
    $controller = new LoginController();
} elseif (
    in_array($action, [
        'getProducts',
        'getProductsServerSide',
        'createProduct',
        'getProduct',
        'updateProduct',
        'deleteProduct',
        'getProductForPOS',
        'getProductByBarcode',
        'adjustStock',
        'getInventoryMovements',
        'getStockAcrossBranches',
        'searchProducts',
        'uploadProductImage',
        'deleteProductImage'
    ])
) {
    require_once __DIR__ . '/../app/controllers/ProductoController.php';
    $controller = new ProductoController();
} elseif (in_array($action, ['getCategorias', 'getMarcas', 'createCategoria', 'updateCategoria', 'deleteCategoria', 'createMarca', 'updateMarca', 'deleteMarca'])) {
    require_once __DIR__ . '/../app/controllers/CatalogoController.php';
    $controller = new CatalogoController();
} elseif (
    in_array($action, [
        'getClients',
        'createClient',
        'getClient',
        'updateClient',
        'deleteClient',
        'searchClients',
        'searchProductsSimple',
        'getProductosParaPreciosEspeciales',
        'saveSpecialClientPrice',
        'registrarAbono',
        'listClients',
        'ventasCreditoView',
        'ventasCreditoData',
        'ventaDetalle',
        'pagosAplicadosData',
        'getClientTypes',
    ])
) {
    require_once __DIR__ . '/../app/controllers/ClienteController.php';
    $controller = new ClienteController();
} elseif (
    in_array($action, [
        'processSale',
        'saveSale',
        'getTicketDetails',
        'listPendingSales',
        'loadSale',
        'deletePendingSale',
        'generateQuote',
        'cancelSale',
        'duplicateSale' // <-- RUTA AÑADIDA
    ])
) {
    require_once __DIR__ . '/../app/controllers/VentaController.php';
    $controller = new VentaController();
} elseif (in_array($action, ['getExpenses', 'getGastosServerSide', 'createExpense', 'getExpense', 'updateExpense', 'deleteExpense'])) {
    require_once __DIR__ . '/../app/controllers/GastoController.php';
    $controller = new GastoController();
} elseif (
    in_array($action, [
        'getSalesReport',
        'getSalesReportPaginated',
        'getCashCut',
        'getDetailedExpenses',
        'getDetailedClientPayments',
        'getGlobalSalesReport',
        'getGlobalVentasServerSide',
        'getSucursalActual'
    ])
) {
    require_once __DIR__ . '/../app/controllers/ReporteController.php';
    $controller = new ReporteController();
} elseif (in_array($action, ['checkApertura', 'registrarApertura', 'getMontoApertura'])) {
    require_once __DIR__ . '/../app/controllers/AperturaCajaController.php';
    $controller = new AperturaCajaController();
} elseif (
    in_array($action, [
        'getSucursales',
        'createSucursal',
        'updateSucursal',
        'deleteSucursal',
        'getUsuarios',
        'createUsuario',
        'getUsuario',
        'updateUsuario',
        'deleteUsuario',

    ])
) {
    require_once __DIR__ . '/../app/controllers/AdminController.php';
    $controller = new AdminController();
} elseif (in_array($action, ['getPrinterConfig', 'updatePrinterConfig', 'getBranchConfig', 'updateBranchConfig', 'uploadBranchLogo'])) {
    require_once __DIR__ . '/../app/controllers/ConfiguracionController.php';
    $controller = new ConfiguracionController();
} elseif (in_array($action, ['getPrintPrefs', 'updatePrintPrefs'])) {
    require_once __DIR__ . '/../app/controllers/ImpresionController.php';
    $controller = new ImpresionController();
} elseif (in_array($action, ['getDashboardData'])) {
    require_once __DIR__ . '/../app/controllers/DashboardController.php';
    $controller = new DashboardController();
} elseif (
    in_array($action, [
        'getUsuarios',
        'getUsuario',
        'createUsuario',
        'updateUsuario',
        'deleteUsuario',
        'getBranchUsers'
    ])
) {
    require_once __DIR__ . '/../app/controllers/UsuarioController.php';
    $controller = new UsuarioController();
} elseif (in_array($action, ['getReportBuilderMeta', 'getDynamicReportServerSide', 'listPresets', 'savePreset', 'getPreset', 'deletePreset'])) {
    require_once __DIR__ . '/../app/controllers/ReporteDinamicoController.php';
    $controller = new ReporteDinamicoController();
}

switch ($action) {
    // --- RUTAS DE LOGIN ---
    case 'login':
        $controller->login();
        break;
    case 'logout':
        $controller->logout();
        break;
    case 'check-session':
        $controller->checkSession();
        break;

    // --- RUTA DE DASHBOARD ---
    case 'getDashboardData':
        $controller->getData();
        break;

    // --- RUTAS DE PRODUCTOS E INVENTARIO ---
    case 'getProducts':
        $controller->getAll();
        break;
    case 'getProductsServerSide':
        $controller->getProductsServerSide();
        break;
    case 'createProduct':
        $controller->create();
        break;
    case 'getProduct':
        $controller->getById();
        break;
    case 'updateProduct':
        $controller->update();
        break;
    case 'deleteProduct':
        $controller->delete();
        break;
    case 'getProductForPOS':
        $controller->getProductForPOS();
        break;
    case 'getProductByBarcode':
        $controller->getByBarcode();
        break;
    case 'adjustStock':
        $controller->adjustStock();
        break;
    case 'getInventoryMovements':
        $controller->getInventoryMovements();
        break;
    case 'getStockAcrossBranches':
        $controller->getStockAcrossBranches();
        break;
    case 'searchProducts':
        $controller->searchProducts();
        break;
    case 'uploadProductImage':
        $controller->uploadProductImage();
        break;
    case 'deleteProductImage':
        $controller->deleteProductImage();
        break;

    // --- RUTAS DE CATÁLOGOS ---
    case 'getCategorias':
        $controller->getCategorias();
        break;
    case 'createCategoria':
        $controller->createCategoria();
        break;
    case 'updateCategoria':
        $controller->updateCategoria();
        break;
    case 'deleteCategoria':
        $controller->deleteCategoria();
        break;
    case 'getMarcas':
        $controller->getMarcas();
        break;
    case 'createMarca':
        $controller->createMarca();
        break;
    case 'updateMarca':
        $controller->updateMarca();
        break;
    case 'deleteMarca':
        $controller->deleteMarca();
        break;

    // --- RUTAS DE CLIENTES ---
    case 'getClients':
        $controller->getAll();
        break;
    case 'createClient':
        $controller->create();
        break;
    case 'getClient':
        $controller->getById();
        break;
    case 'updateClient':
        $controller->update();
        break;
    case 'deleteClient':
        $controller->delete();
        break;
    case 'searchClients':
        $controller->search();
        break;
    case 'saveSpecialClientPrice':
        $controller->saveSpecialClientPrice();
        break;
    case 'getProductosParaPreciosEspeciales':
        $controller->getProductosParaPreciosEspeciales();
        break;
    case 'searchProductsSimple':
        $controller->searchProductsSimple();
        break;
    case 'registrarAbono':
        $controller->registrarAbono();
        break;
    case 'listClients':
        $controller->listClients();
        break;
    case 'pagosAplicadosData':
        $controller->pagosAplicadosData();
        break;
    case 'ventasCreditoData':
        $controller->ventasCreditoData();
        break;
    case 'ventaDetalle':
        $controller->ventaDetalle();
        break;
    case 'getClientTypes':
        $controller->getClientTypes();
        break;


    // --- RUTAS DE VENTA ---
    case 'processSale':
        $controller->processSale();
        break;
    case 'saveSale':
        $controller->saveSale();
        break;
    case 'duplicateSale': // <-- CASE AÑADIDO
        $controller->duplicateSale();
        break;
    case 'getTicketDetails':
        $controller->getTicketDetails();
        break;
    case 'listPendingSales':
        $controller->listPendingSales();
        break;
    case 'loadSale':
        $controller->loadSale();
        break;
    case 'deletePendingSale':
        $controller->deletePendingSale();
        break;
    case 'generateQuote':
        $controller->generateQuote();
        break;
    case 'cancelSale':
        $controller->cancelSale();
        break;

    // --- RUTAS DE GASTOS ---
    case 'getExpenses':
        $controller->getAll();
        break;
    case 'getGastosServerSide':
        $controller->getGastosServerSide();
        break;
    case 'createExpense':
        $controller->create();
        break;
    case 'getExpense':
        $controller->getById();
        break;
    case 'updateExpense':
        $controller->update();
        break;
    case 'deleteExpense':
        $controller->delete();
        break;

    // --- RUTAS DE REPORTES ---
    case 'getSalesReport':
        $controller->getVentas();
        break;
    case 'getSalesReportPaginated':
        $controller->getSalesReportPaginated();
        break;
    case 'getCashCut':
        $controller->getCorteCaja();
        break;
    case 'getDetailedExpenses':
        $controller->getDetailedExpenses();
        break;
    case 'getDetailedClientPayments':
        $controller->getDetailedClientPayments();
        break;
    case 'getGlobalSalesReport':
        $controller->getVentasGlobales();
        break;
    case 'getGlobalVentasServerSide':
        $controller->getGlobalVentasServerSide();
        break;
    case 'getSucursalActual':
        $controller->getSucursalActual();
        break;

    // --- RUTAS DE APERTURA DE CAJA ---
    case 'checkApertura':
        $controller->checkApertura();
        break;
    case 'registrarApertura':
        $controller->registrarApertura();
        break;
    case 'getMontoApertura':
        $controller->getMontoApertura();
        break;

    // --- RUTAS DE ADMINISTRACIÓN ---
    case 'getSucursales':
        $controller->getAllSucursales();
        break;
    case 'createSucursal':
        $controller->createSucursal();
        break;
    case 'updateSucursal':
        $controller->updateSucursal();
        break;
    case 'deleteSucursal':
        $controller->deleteSucursal();
        break;
    case 'getUsuarios':
        $controller->getAllUsuarios();
        break;
    case 'createUsuario':
        $controller->createUsuario();
        break;
    case 'updateUsuario':
        $controller->updateUsuario();
        break;
    case 'deleteUsuario':
        $controller->deleteUsuario();
        break;

    // --- RUTAS DE CONFIGURACIÓN ---
    case 'getBranchConfig':
        $controller->getBranchConfig();
        break;
    case 'updateBranchConfig':
        $controller->updateBranchConfig();
        break;
    case 'getPrinterConfig':
        $controller->getPrinterConfig();
        break;
    case 'updatePrinterConfig':
        $controller->updatePrinterConfig();
        break;
    case 'getBranchUsers':
        $controller->getUsersByCurrentBranch();
        break;
    case 'getPrintPrefs':
        $controller->getPrintPrefs();
        break;
    case 'updatePrintPrefs':
        $controller->updatePrintPrefs();
        break;
    case 'uploadBranchLogo':
        $controller->uploadBranchLogo();
        break;
    case 'getReportBuilderMeta':
        $controller->getReportBuilderMeta();
        break;
    case 'getDynamicReportServerSide':
        $controller->getDynamicReportServerSide();
        break;

    case 'listPresets':
        $controller->listPresets();
        break;
    case 'savePreset':
        $controller->savePreset();
        break;
    case 'getPreset':
        $controller->getPreset();
        break;
    case 'deletePreset':
        $controller->deletePreset();
        break;




    default:
        if (isset($controller)) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Acción no definida en el enrutador.']);
        } else {
            // Si no se encuentra un controlador para la acción, se puede mostrar una página 404
            // o simplemente terminar. Por ahora, terminamos con un error genérico.
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint no encontrado.']);
        }
        break;
}
