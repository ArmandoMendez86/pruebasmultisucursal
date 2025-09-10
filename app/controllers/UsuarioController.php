<?php
// Archivo: /app/controllers/UsuarioController.php

require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController
{
    private $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }


    /**
     * Obtiene todos los usuarios con información de su sucursal.
     */
    public function getAll()
    {
        try {
            $usuarios = $this->usuarioModel->getAll();
            echo json_encode(['success' => true, 'data' => $usuarios]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtiene un usuario específico por su ID.
     */
    public function getById()
    {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado.']);
            return;
        }

        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $usuario = $this->usuarioModel->getById($id);

        if ($usuario) {
            echo json_encode(['success' => true, 'data' => $usuario]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado.']);
        }
    }

    /**
     * Crea un nuevo usuario.
     */
    public function create()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['nombre']) || empty($data['username']) || empty($data['password']) || empty($data['rol']) || empty($data['id_sucursal'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos para crear un usuario.']);
            return;
        }

        try {
            $newUserId = $this->usuarioModel->create($data);
            if ($newUserId) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente.', 'id' => $newUserId]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo crear el usuario.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            // Manejar error de username duplicado
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'message' => 'El nombre de usuario (username) ya existe.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear el usuario: ' . $e->getMessage()]);
            }
        }
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['id']) || empty($data['nombre']) || empty($data['username']) || empty($data['rol']) || empty($data['id_sucursal'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Datos incompletos para actualizar.']);
            return;
        }

        try {
            if ($this->usuarioModel->update($data)) {
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el usuario o no hubo cambios.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo json_encode(['success' => false, 'message' => 'El nombre de usuario (username) ya pertenece a otro usuario.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el usuario: ' . $e->getMessage()]);
            }
        }
    }

    /**
     * Elimina un usuario.
     */
    public function delete()
    {
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
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el usuario.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el usuario: ' . $e->getMessage()]);
        }
    }

    public function getUsersByCurrentBranch()
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['branch_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            return;
        }

        try {
            $usuarios = $this->usuarioModel->getUsersByBranch($_SESSION['branch_id']);
            echo json_encode(['success' => true, 'data' => $usuarios]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios de la sucursal: ' . $e->getMessage()]);
        }
    }
}
