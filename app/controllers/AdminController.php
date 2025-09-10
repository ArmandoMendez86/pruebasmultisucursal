<?php
// Archivo: /app/controllers/AdminController.php

require_once __DIR__ . '/../models/Sucursal.php';
require_once __DIR__ . '/../models/Usuario.php';

class AdminController {
    private $sucursalModel;
    private $usuarioModel;

    public function __construct() {
        $this->sucursalModel = new Sucursal();
        $this->usuarioModel = new Usuario();
        
        // Seguridad: solo administradores pueden usar este controlador
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Super') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            exit;
        }
    }

    // --- MÉTODOS PARA SUCURSALES ---
    public function getAllSucursales() {
        header('Content-Type: application/json');
        try {
            echo json_encode(['success' => true, 'data' => $this->sucursalModel->getAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function createSucursal() {
        header('Content-Type: application/json');
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
                throw new Exception("No se pudo crear la sucursal.");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function updateSucursal() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
         if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID es requerido.']);
            return;
        }
        try {
            if ($this->sucursalModel->update($data['id'], $data)) {
                echo json_encode(['success' => true, 'message' => 'Sucursal actualizada.']);
            } else {
                 throw new Exception("No se pudo actualizar la sucursal.");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteSucursal() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de sucursal no proporcionado.']);
            return;
        }
        try {
            if ($this->sucursalModel->delete($data['id'])) {
                echo json_encode(['success' => true, 'message' => 'Sucursal eliminada exitosamente.']);
            } else {
                throw new Exception("No se pudo eliminar la sucursal. Puede que tenga usuarios o inventario asociado.");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    // --- MÉTODOS PARA USUARIOS ---
    public function getAllUsuarios() {
        header('Content-Type: application/json');
        try {
            echo json_encode(['success' => true, 'data' => $this->usuarioModel->getAll()]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function createUsuario() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['nombre']) || empty($data['username']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nombre, username y contraseña son requeridos.']);
            return;
        }
        try {
            $newId = $this->usuarioModel->create($data);
            if ($newId) {
                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente.']);
            } else {
                 throw new Exception("No se pudo crear el usuario.");
            }
        } catch (Exception $e) {
             http_response_code(500);
             $message = strpos($e->getMessage(), 'Duplicate entry') !== false ? 'El username ya existe.' : $e->getMessage();
             echo json_encode(['success' => false, 'message' => $message]);
        }
    }
    
    public function updateUsuario() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID es requerido.']);
            return;
        }
        try {
            if ($this->usuarioModel->update($data)) {
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado.']);
            } else {
                 throw new Exception("No se pudo actualizar el usuario.");
            }
        } catch (Exception $e) {
             http_response_code(500);
             $message = strpos($e->getMessage(), 'Duplicate entry') !== false ? 'El username ya existe.' : $e->getMessage();
             echo json_encode(['success' => false, 'message' => $message]);
        }
    }

    public function deleteUsuario() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado.']);
            return;
        }
        try {
            if ($this->usuarioModel->delete($data['id'])) {
                echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente.']);
            } else {
                throw new Exception("No se pudo eliminar el usuario.");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
