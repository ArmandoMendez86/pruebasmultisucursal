<?php
// Archivo: /app/controllers/ClienteController.php

require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Producto.php';

class ClienteController
{

    private $clienteModel;
    private $productoModel;

    public function __construct()
    {
        $this->clienteModel = new Cliente();
        $this->productoModel = new Producto();
    }

    // NUEVO MÉTODO PARA DATATABLES
    public function listClients()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Acceso no autorizado.']);
            return;
        }
        try {
            // Pasamos los parámetros de DataTables (enviados por POST) al modelo
            $output = $this->clienteModel->getClientsForDataTable($_POST);
            echo json_encode($output);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error del servidor al listar clientes.',
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getAll()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        try {
            $clientes = $this->clienteModel->getAll();
            echo json_encode(['success' => true, 'data' => $clientes]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener los clientes.']);
        }
    }

    public function getById()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
            return;
        }

        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $cliente = $this->clienteModel->getById($id);

        if ($cliente) {
            echo json_encode(['success' => true, 'data' => $cliente]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
        }
    }

    public function create()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El nombre del cliente es requerido.']);
            return;
        }

        $clientData = [
            'nombre' => $data['nombre'],
            'rfc' => $data['rfc'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'tiene_credito' => $data['tiene_credito'] ?? 0,
            'limite_credito' => $data['limite_credito'] ?? 0.00,
            'direcciones' => $data['direcciones'] ?? [],
            'precios' => $data['precios'] ?? [],
            'id_tipo' => isset($data['id_tipo']) ? (int)$data['id_tipo'] : 1,
            'obs_envio' => $data['obs_envio'] ?? null,

        ];

        try {
            $newClientId = $this->clienteModel->create($clientData);
            if ($newClientId) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Cliente creado exitosamente.', 'id' => $newClientId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el cliente.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
        }
    }

    public function update()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
            return;
        }

        $id = $data['id'];
        $clientData = [
            'nombre' => $data['nombre'],
            'rfc' => $data['rfc'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'tiene_credito' => $data['tiene_credito'] ?? 0,
            'limite_credito' => $data['limite_credito'] ?? 0.00,
            'direcciones' => $data['direcciones'] ?? [],
            'precios' => $data['precios'] ?? [],
            'id_tipo' => isset($data['id_tipo']) ? (int)$data['id_tipo'] : 1,
            'obs_envio' => $data['obs_envio'] ?? null,

        ];

        try {
            if ($this->clienteModel->update($id, $clientData)) {
                echo json_encode(['success' => true, 'message' => 'Cliente actualizado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el cliente.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function delete()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado.']);
            return;
        }

        $id = $data['id'];
        if ($this->clienteModel->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Cliente eliminado exitosamente.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el cliente.']);
        }
    }

    public function registrarAbono()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. Inicie sesión.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $idCliente = $data['id_cliente'] ?? null;
        $monto = $data['monto'] ?? null;
        $metodoPago = $data['metodo_pago'] ?? null;
        // === NUEVO ===
        $detalle = isset($data['detalle']) ? trim($data['detalle']) : '';
        $fechaRecibido = $data['fecha_recibido'] ?? '';

        $idUsuario = $_SESSION['user_id'];

        // Validaciones
        if (empty($idCliente) || !isset($monto) || !is_numeric($monto) || $monto <= 0 || empty($metodoPago)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos para registrar el abono.']);
            return;
        }
        if (strlen($detalle) > 300) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El detalle no debe exceder 300 caracteres.']);
            return;
        }
        if ($fechaRecibido !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaRecibido)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido (YYYY-MM-DD).']);
            return;
        }
        // Si no viene fecha válida, se normaliza a hoy en el modelo

        try {
            if ($this->clienteModel->registrarAbono($idCliente, $monto, $metodoPago, $idUsuario, $detalle, $fechaRecibido)) {
                echo json_encode(['success' => true, 'message' => 'Abono registrado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo registrar el abono.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    public function search()
    {
        header('Content-Type: application/json');
        $term = $_GET['term'] ?? '';
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['results' => [], 'message' => 'Acceso no autorizado.']);
            return;
        }
        if (empty($term)) {
            echo json_encode(['results' => [['id' => 1, 'text' => 'Público en General']]]);
            return;
        }
        $term = htmlspecialchars(strip_tags($term));
        $clientes = $this->clienteModel->search($term);
        $results = array_map(function ($cliente) {
            return ['id' => $cliente['id'], 'text' => $cliente['nombre']];
        }, $clientes);
        echo json_encode(['results' => $results]);
    }

    public function getProductosParaPreciosEspeciales()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        try {
            $productos = $this->productoModel->getAllSimple();
            echo json_encode(['success' => true, 'data' => $productos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener los productos.']);
        }
    }

    public function searchProductsSimple()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        $term = $_GET['term'] ?? '';
        // No buscar si el término es muy corto para evitar sobrecargar el servidor
        if (strlen($term) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            return;
        }
        try {
            // Se asume que existe un método 'searchSimpleByNameOrSku' en el modelo Producto.
            // Deberás crear este método. Ver instrucciones.
            $productos = $this->productoModel->searchSimpleByNameOrSku($term);
            echo json_encode(['success' => true, 'data' => $productos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al buscar productos.']);
        }
    }

    public function saveSpecialClientPrice()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $idCliente = $data['id_cliente'] ?? null;
        $idProducto = $data['id_producto'] ?? null;
        $precioEspecial = $data['precio_especial'] ?? null;
        if (empty($idCliente) || empty($idProducto) || !isset($precioEspecial)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos para guardar precio especial.']);
            return;
        }
        try {
            $this->clienteModel->setSpecialPrice($idCliente, $idProducto, $precioEspecial);
            echo json_encode(['success' => true, 'message' => 'Precio especial guardado exitosamente.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al guardar precio especial: ' . $e->getMessage()]);
        }
    }

    // ====================== VISTA ======================
    /*  public function ventasCreditoView()
     {
         if (!isset($_SESSION['user_id'])) {
             header('Location: /login');
             return;
         }
         // Puedes pasar variables si gustas; aquí solo renderizamos.
         require __DIR__ . '/../views/clientes/ventas_credito.php';
     } */

    // ===== DataTables Server-Side: ventas a crédito =====
    public function ventasCreditoData()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            return;
        }

        // DataTables params
        $draw = (int) ($_POST['draw'] ?? 0);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $search = trim((string) ($_POST['search']['value'] ?? ''));

        // Ordenamiento seguro
        $orderColIdx = (int) ($_POST['order'][0]['column'] ?? 0);
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'asc');
        $orderDir = ($orderDir === 'desc') ? 'DESC' : 'ASC';

        // Mapa de columnas ordenables (índices -> columnas SQL)
        $cols = [
            0 => 'c.nombre',
            1 => 'v.id',
            2 => 'v.fecha',
            3 => 'vc.monto_credito',
            4 => 'COALESCE(pa.total_abonado,0)',
            5 => 'vc.saldo_pendiente',
            6 => "CASE WHEN ROUND(vc.saldo_pendiente,2) <= 0 THEN 'Cerrada'
            WHEN ROUND(vc.saldo_pendiente,2)  < vc.monto_credito THEN 'Parcial'
            ELSE 'Abierta' END", // estatus_calc
            7 => 'COALESCE(det.num_items,0)'
        ];

        $orderBy = $cols[$orderColIdx] ?? 'v.fecha';

        // Filtros extra opcionales (ej. solo pendientes)
        $soloPendientes = isset($_POST['soloPendientes']) && $_POST['soloPendientes'] === '1';

        try {
            $result = $this->clienteModel->sspVentasCredito([
                'draw' => $draw,
                'start' => max(0, $start),
                'length' => ($length < 0 ? 10 : min(500, $length)),
                'search' => $search,
                'orderBy' => $orderBy,
                'orderDir' => $orderDir,
                'soloPendientes' => $soloPendientes
            ]);

            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Error del servidor']);
        }
    }

    // ===== Detalle de productos (modal) =====
    public function ventaDetalle()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'data' => []]);
            return;
        }
        $ventaId = (int) ($_GET['venta_id'] ?? 0);
        if ($ventaId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'venta_id inválido']);
            return;
        }

        try {
            $rows = $this->clienteModel->getDetalleProductosPorVenta($ventaId);
            echo json_encode(['success' => true, 'data' => $rows]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudo obtener el detalle']);
        }
    }

    public function pagosAplicadosData()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            return;
        }

        $draw = (int) ($_POST['draw'] ?? 0);
        $start = (int) ($_POST['start'] ?? 0);
        $length = (int) ($_POST['length'] ?? 10);
        $search = trim((string) ($_POST['search']['value'] ?? ''));

        $orderColIdx = (int) ($_POST['order'][0]['column'] ?? 0);
        $orderDir = strtolower($_POST['order'][0]['dir'] ?? 'asc');
        $orderDir = ($orderDir === 'desc') ? 'DESC' : 'ASC';

        // ¡Importante!: columnas del SELECT EXTERNO (alias t.*)
        $cols = [
            0 => 't.nro_pago',
            1 => 't.cliente',
            2 => 't.usuario_registro',
            3 => 't.fecha_recibido',
            4 => 't.monto_pago',
            5 => 't.metodo_pago',
            6 => 't.detalle',
            7 => 't.venta_id',
            8 => 't.fecha_venta',
            9 => 't.aplicado_en_esta_venta',
            10 => 't.monto_credito_venta',     // saldo ANTES del pago (alias calculado)
            11 => 't.saldo_pendiente_actual',
            12 => 't.fecha_sistema',
        ];
        $orderBy = $cols[$orderColIdx] ?? 't.fecha_recibido';

        // Filtros opcionales
        $idCliente = isset($_POST['id_cliente']) ? (int) $_POST['id_cliente'] : 0;
        $desde = $_POST['desde'] ?? null;   // 'YYYY-MM-DD'
        $hasta = $_POST['hasta'] ?? null;   // 'YYYY-MM-DD'

        try {
            $resp = $this->clienteModel->sspPagosAplicados([
                'draw' => $draw,
                'start' => max(0, $start),
                'length' => ($length < 0 ? 10 : min(500, $length)),
                'search' => $search,
                'orderBy' => $orderBy,
                'orderDir' => $orderDir,
                'idCliente' => $idCliente,
                'desde' => $desde,
                'hasta' => $hasta
            ]);
            echo json_encode($resp);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error del servidor'
            ]);
        }
    }

    public function getClientTypes()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }
        try {
            $tipos = $this->clienteModel->getTiposCliente();
            echo json_encode(['success' => true, 'data' => $tipos]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener tipos de cliente.']);
        }
    }



}
