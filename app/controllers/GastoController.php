<?php
// Archivo: /app/controllers/GastoController.php

require_once __DIR__ . '/../models/Gasto.php';

class GastoController {

    private $gastoModel;

    public function __construct() {
        $this->gastoModel = new Gasto();
    }

    /**
     * @deprecated Reemplazado por getGastosServerSide para la tabla principal.
     */
    public function getAll() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }
        try {
            $id_sucursal = $_SESSION['branch_id'];
            $gastos = $this->gastoModel->getAllBySucursal($id_sucursal);
            echo json_encode(['success' => true, 'data' => $gastos]);
        } catch (Exception $e) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Error al obtener los gastos.']); }
    }

    // --- INICIO: Nuevo método para Server-Side ---
    public function getGastosServerSide()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        $params = $_REQUEST;
        $id_sucursal = $_SESSION['branch_id'];

        try {
            $result = $this->gastoModel->getAllServerSide($id_sucursal, $params);

            $json_data = [
                "draw"            => intval($params['draw']),
                "recordsTotal"    => intval($result['recordsTotal']),
                "recordsFiltered" => intval($result['recordsFiltered']),
                "data"            => $result['data']
            ];

            echo json_encode($json_data);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }
    // --- FIN: Nuevo método para Server-Side ---

    public function create() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }
        $data = (array)json_decode(file_get_contents('php://input'));
        if (empty($data['categoria_gasto']) || empty($data['descripcion']) || empty($data['monto'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos.']); return; }
        $data['id_usuario'] = $_SESSION['user_id'];
        $data['id_sucursal'] = $_SESSION['branch_id'];
        try {
            $newExpenseId = $this->gastoModel->create($data);
            if ($newExpenseId) { http_response_code(201); echo json_encode(['success' => true, 'message' => 'Gasto registrado exitosamente.', 'id' => $newExpenseId]); } 
            else { http_response_code(500); echo json_encode(['success' => false, 'message' => 'No se pudo registrar el gasto.']); }
        } catch (Exception $e) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Error de base de datos.']); }
    }

    public function getById() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }
        if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de gasto no proporcionado.']); return; }
        
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $gasto = $this->gastoModel->getById($id);
        
        if ($gasto) { echo json_encode(['success' => true, 'data' => $gasto]); } 
        else { http_response_code(404); echo json_encode(['success' => false, 'message' => 'Gasto no encontrado.']); }
    }

    public function update() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }
        
        $data = (array)json_decode(file_get_contents('php://input'));
        if (empty($data['id'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de gasto no proporcionado.']); return; }

        $id = $data['id'];
        if ($this->gastoModel->update($id, $data)) { echo json_encode(['success' => true, 'message' => 'Gasto actualizado exitosamente.']); } 
        else { http_response_code(500); echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el gasto.']); }
    }

    public function delete() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); return; }
        
        $data = (array)json_decode(file_get_contents('php://input'));
        if (empty($data['id'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'ID de gasto no proporcionado.']); return; }

        $id = $data['id'];
        if ($this->gastoModel->delete($id)) { echo json_encode(['success' => true, 'message' => 'Gasto eliminado exitosamente.']); } 
        else { http_response_code(500); echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el gasto.']); }
    }
}
