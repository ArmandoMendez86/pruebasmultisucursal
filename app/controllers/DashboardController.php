<?php
// app/controllers/DashboardController.php

require_once __DIR__ . '/../models/Dashboard.php';

class DashboardController {

    private $dashboardModel;

    public function __construct() {
        $this->dashboardModel = new Dashboard();
    }

    /**
     * Obtiene todos los datos para el dashboard y los devuelve como JSON.
     */
    public function getData() {
        header('Content-Type: application/json');

        // CORRECCIÓN FINAL: Se verifica 'user_id' y 'branch_id' que son las variables
        // que SÍ se establecen en tu LoginController.php
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['branch_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. La sesión de usuario está incompleta.']);
            return;
        }

        try {
            // Se obtiene el id_sucursal de la variable de sesión correcta.
            $id_sucursal = $_SESSION['branch_id'];
            
            // El resto de la lógica para obtener los datos permanece igual.
            $ventasHoy = $this->dashboardModel->getVentasHoyConPagos($id_sucursal);
            $ingresosHoy = 0;
            foreach ($ventasHoy as $venta) {
                if (!empty($venta['metodo_pago']) && $venta['metodo_pago'] !== 'null') {
                    $pagos = json_decode($venta['metodo_pago'], true);
                    if (is_array($pagos)) {
                        foreach ($pagos as $pago) {
                            if (isset($pago['method']) && $pago['method'] !== 'Crédito') {
                                $ingresosHoy += $pago['amount'];
                            }
                        }
                    }
                }
            }

            $cuentasPorCobrar = $this->dashboardModel->getCuentasPorCobrar();
            $gastosHoy = $this->dashboardModel->getGastosHoy($id_sucursal);
            $conteoVentasHoy = $this->dashboardModel->getConteoVentasHoy($id_sucursal);
            $topProductos = $this->dashboardModel->getTopProductos($id_sucursal);
            $topClientes = $this->dashboardModel->getTopClientes($id_sucursal);

            $data = [
                'ingresosHoy' => $ingresosHoy,
                'cuentasPorCobrar' => $cuentasPorCobrar,
                'gastosHoy' => $gastosHoy,
                'conteoVentasHoy' => $conteoVentasHoy,
                'topProductos' => $topProductos,
                'topClientes' => $topClientes
            ];

            echo json_encode(['success' => true, 'data' => $data]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
}
?>
