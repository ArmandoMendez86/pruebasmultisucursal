<?php
// Archivo: /app/controllers/SucursalController.php

require_once __DIR__ . '/../models/Sucursal.php';

class SucursalController {
    private $sucursalModel;

    public function __construct() {
        $this->sucursalModel = new Sucursal();
    }

    private function isAdmin() {
        return isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador';
    }

    private function unauthorized() {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
        exit;
    }

    public function getAll() {
        header('Content-Type: application/json');
        if (!$this->isAdmin()) { $this->unauthorized(); }
        
        try {
            $sucursales = $this->sucursalModel->getAll();
            echo json_encode(['success' => true, 'data' => $sucursales]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function getById() {
        header('Content-Type: application/json');
        if (!$this->isAdmin()) { $this->unauthorized(); }
        
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
            return;
        }
        
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $sucursal = $this->sucursalModel->getById($id);
        
        if ($sucursal) {
            echo json_encode(['success' => true, 'data' => $sucursal]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sucursal no encontrada.']);
        }
    }

    public function create() {
        header('Content-Type: application/json');
        if (!$this->isAdmin()) { $this->unauthorized(); }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['nombre']) || empty($data['direccion'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nombre y dirección son requeridos.']);
            return;
        }

        try {
            if ($this->sucursalModel->create($data)) {
                echo json_encode(['success' => true, 'message' => 'Sucursal creada exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo crear la sucursal.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

   /*  public function update() {
        header('Content-Type: application/json');
        if (!$this->isAdmin()) { $this->unauthorized(); }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id']) || empty($data['nombre']) || empty($data['direccion'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
            return;
        }

        try {
            if ($this->sucursalModel->update($data)) {
                echo json_encode(['success' => true, 'message' => 'Sucursal actualizada exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la sucursal.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } */

    public function delete() {
        header('Content-Type: application/json');
        if (!$this->isAdmin()) { $this->unauthorized(); }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
            return;
        }

        try {
            if ($this->sucursalModel->delete($data['id'])) {
                echo json_encode(['success' => true, 'message' => 'Sucursal eliminada exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la sucursal.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
