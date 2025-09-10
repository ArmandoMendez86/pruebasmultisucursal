<?php
// Archivo: /app/controllers/VentaController.php

require_once __DIR__ . '/../models/Venta.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../../config/Database.php';

class VentaController
{
    private $ventaModel;
    private $clienteModel;
    private $productoModel;
    private $conn;

    public function __construct()
    {
        $this->ventaModel = new Venta();
        $this->clienteModel = new Cliente();
        $this->productoModel = new Producto();
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    public function processSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['cart']) || !isset($data['total']) || empty($data['payments'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos de la venta incompletos o métodos de pago no proporcionados.']);
            return;
        }

        $data['id_usuario'] = $_SESSION['user_id'];
        $data['id_sucursal'] = $_SESSION['branch_id'];
        $data['id_cliente'] = $data['id_cliente'] ?? 1;
        $data['estado'] = 'Completada';
        $data['iva_aplicado'] = $data['iva_aplicado'] ?? 0;

        $creditPaymentAmount = 0;
        foreach ($data['payments'] as $payment) {
            if ($payment['method'] === 'Crédito') {
                $creditPaymentAmount += (float) $payment['amount'];
            }
        }

        if ($creditPaymentAmount > 0) {
            $cliente = $this->clienteModel->getById($data['id_cliente']);
            if (!$cliente || $cliente['tiene_credito'] == 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'El cliente seleccionado no tiene crédito habilitado.']);
                return;
            }
            $availableCredit = (float) $cliente['limite_credito'] - (float) $cliente['deuda_actual'];
            if ($creditPaymentAmount > $availableCredit) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Monto de crédito excede el límite disponible del cliente. Crédito disponible: $' . number_format($availableCredit, 2)]);
                return;
            }
        }

        try {
            $this->conn->beginTransaction();

            $allowNegativeStock = !empty($data['allow_negative_stock']);

            if (!$allowNegativeStock) {
                foreach ($data['cart'] as $item) {
                    $product = $this->productoModel->getById($item['id'], $data['id_sucursal']);
                    $current_stock = $product['stock'] ?? 0;

                    if ($item['quantity'] > $current_stock) {
                        throw new Exception("Stock insuficiente para: " . $product['nombre'] . ". Solicitado: " . $item['quantity'] . ", Disponible: " . $current_stock);
                    }
                }
            }

            if (isset($data['id_venta']) && !empty($data['id_venta'])) {
                $this->ventaModel->update($data);
                $saleId = $data['id_venta'];
                $message = 'Venta pendiente completada exitosamente.';
            } else {
                $saleId = $this->ventaModel->create($data);
                $message = 'Venta registrada exitosamente.';
            }

           /*  if ($creditPaymentAmount > 0) {
                $this->clienteModel->updateClientCredit($data['id_cliente'], $creditPaymentAmount);
            } */

            foreach ($data['cart'] as $item) {
                $product = $this->productoModel->getById($item['id'], $data['id_sucursal']);
                $old_stock = $product['stock'] ?? 0;
                $new_stock = $old_stock - $item['quantity'];

                $stmt_update_stock = $this->conn->prepare(
                    "UPDATE inventario_sucursal SET stock = :new_stock WHERE id_producto = :id_producto AND id_sucursal = :id_sucursal"
                );
                $stmt_update_stock->bindParam(':new_stock', $new_stock, PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':id_producto', $item['id'], PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':id_sucursal', $data['id_sucursal'], PDO::PARAM_INT);
                $stmt_update_stock->execute();

                $this->productoModel->addInventoryMovement(
                    $item['id'],
                    $data['id_sucursal'],
                    $data['id_usuario'],
                    'venta',
                    $item['quantity'],
                    $old_stock,
                    $new_stock,
                    'Venta # ' . $saleId,
                    $saleId
                );
            }

            $this->conn->commit();
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => $message, 'id_venta' => $saleId]);
        } catch (Exception $e) {
            $this->conn->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function saveSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // MODIFICACIÓN: Se elimina la validación que impedía guardar para "Público en General" (id_cliente == 1)
        if (empty($data['cart']) || !isset($data['total']) || empty($data['id_cliente'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Para guardar una venta, debe seleccionar un cliente y tener productos en el carrito.']);
            return;
        }

        $data['id_usuario'] = $_SESSION['user_id'];
        $data['id_sucursal'] = $_SESSION['branch_id'];
        $data['estado'] = 'Pendiente';
        $data['iva_aplicado'] = $data['iva_aplicado'] ?? 0;

        try {
            if (isset($data['id_venta']) && !empty($data['id_venta'])) {
                $this->ventaModel->update($data);
                $saleId = $data['id_venta'];
                $message = 'Venta pendiente actualizada exitosamente.';
            } else {
                $saleId = $this->ventaModel->create($data);
                $message = 'Venta guardada como pendiente exitosamente.';
            }

            http_response_code(201);
            echo json_encode(['success' => true, 'message' => $message, 'id_venta' => $saleId]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }

    // NUEVA FUNCIÓN: Para duplicar una venta existente.
    public function duplicateSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id_venta'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta a duplicar no proporcionado.']);
            return;
        }

        try {
            $id_venta_original = $data['id_venta'];
            $id_usuario = $_SESSION['user_id'];
            $id_sucursal = $_SESSION['branch_id'];

            $newSaleId = $this->ventaModel->duplicateById($id_venta_original, $id_usuario, $id_sucursal, isset($data['id_cliente']) ? intval($data['id_cliente']) : null);

            if ($newSaleId) {
                echo json_encode(['success' => true, 'message' => 'Venta duplicada exitosamente.', 'new_sale_id' => $newSaleId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo duplicar la venta.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al duplicar la venta: ' . $e->getMessage()]);
        }
    }

    public function listPendingSales()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        try {
            $id_sucursal = $_SESSION['branch_id'];
            $ventas = $this->ventaModel->getPendingSales($id_sucursal);
            echo json_encode(['success' => true, 'data' => $ventas]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al listar ventas pendientes.']);
        }
    }

    public function loadSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado.']);
            return;
        }

        $id_venta = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        try {
            $venta = $this->ventaModel->getSaleForPOS($id_venta);
            if ($venta) {
                echo json_encode(['success' => true, 'data' => $venta]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Venta pendiente no encontrada.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al cargar la venta.']);
        }
    }

    public function getTicketDetails()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado.']);
            return;
        }

        $id_venta = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

        try {
            $details = $this->ventaModel->getDetailsForTicket($id_venta);
            if ($details['venta']) {
                echo json_encode(['success' => true, 'data' => $details]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Venta no encontrada.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener los detalles de la venta.']);
        }
    }

    public function deletePendingSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id_venta'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado.']);
            return;
        }

        try {
            $id_venta = $data['id_venta'];
            $id_sucursal = $_SESSION['branch_id'];

            if ($this->ventaModel->deletePendingSale($id_venta, $id_sucursal)) {
                echo json_encode(['success' => true, 'message' => 'Venta pendiente eliminada exitosamente.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No se encontró la venta pendiente o no pertenece a esta sucursal.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al eliminar la venta.']);
        }
    }

    public function generateQuote()
    {
        if (!isset($_SESSION['user_id'])) {
            die('Acceso no autorizado.');
        }
        if (empty($_GET['id'])) {
            die('ID de venta no proporcionado.');
        }

        $id_venta = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

        try {
            $data = $this->ventaModel->getDetailsForTicket($id_venta);
            if ($data && $data['venta']) {
                include __DIR__ . '/../views/cotizacion_template.php';
            } else {
                die('Cotización no encontrada.');
            }
        } catch (Exception $e) {
            die('Error al generar la cotización: ' . $e->getMessage());
        }
    }

    public function cancelSale()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id_venta'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de venta no proporcionado para cancelar.']);
            return;
        }

        try {
            $id_venta = $data['id_venta'];
            $id_usuario_cancela = $_SESSION['user_id'];
            $id_sucursal = $_SESSION['branch_id'];

            if ($this->ventaModel->cancelSale($id_venta, $id_usuario_cancela, $id_sucursal)) {
                echo json_encode(['success' => true, 'message' => 'Venta cancelada exitosamente. Stock devuelto y crédito ajustado.']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'No se pudo cancelar la venta. Verifique el ID y el estado.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al cancelar la venta: ' . $e->getMessage()]);
        }
    }
}
