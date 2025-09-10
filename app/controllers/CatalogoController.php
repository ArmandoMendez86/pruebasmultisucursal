<?php
// Archivo: /app/controllers/CatalogoController.php

require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/Marca.php'; 

class CatalogoController
{
    private $categoriaModel;
    private $marcaModel;

    public function __construct()
    {
        $this->categoriaModel = new Categoria();
        $this->marcaModel = new Marca(); 
    }

    /**
     * Obtiene todas las categorías.
     */
    public function getCategorias()
    {
        header('Content-Type: application/json');
        try {
            $categorias = $this->categoriaModel->getAll();
            echo json_encode(['success' => true, 'data' => $categorias]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener categorías: ' . $e->getMessage()]);
        }
    }

    /**
     * Crea una nueva categoría.
     */
    public function createCategoria()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El nombre de la categoría es obligatorio.']);
            return;
        }

        try {
            if ($this->categoriaModel->create($data)) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Categoría creada exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo crear la categoría.']);
            }
        } catch (PDOException $e) {
            // Manejar error de nombre duplicado
            if ($e->getCode() == '23000') { // Código SQLSTATE para violación de unicidad
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Ya existe una categoría con ese nombre.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error de base de datos al crear categoría: ' . $e->getMessage()]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al crear categoría: ' . $e->getMessage()]);
        }
    }

    /**
     * Actualiza una categoría existente.
     */
    public function updateCategoria()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id']) || empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID y nombre de categoría son obligatorios para actualizar.']);
            return;
        }

        try {
            if ($this->categoriaModel->update($data['id'], $data)) {
                echo json_encode(['success' => true, 'message' => 'Categoría actualizada exitosamente.']);
            } else {
                http_response_code(404); // Not Found si no se actualizó ninguna fila
                echo json_encode(['success' => false, 'message' => 'No se encontró la categoría o no hubo cambios.']);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { // Código SQLSTATE para violación de unicidad
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'Ya existe otra categoría con ese nombre.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error de base de datos al actualizar categoría: ' . $e->getMessage()]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al actualizar categoría: ' . $e->getMessage()]);
        }
    }

    /**
     * Elimina una categoría.
     */
    public function deleteCategoria()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de categoría no proporcionado para eliminar.']);
            return;
        }

        try {
            if ($this->categoriaModel->delete($data['id'])) {
                echo json_encode(['success' => true, 'message' => 'Categoría eliminada exitosamente.']);
            } else {
                http_response_code(404); // Not Found si no se eliminó ninguna fila
                echo json_encode(['success' => false, 'message' => 'No se encontró la categoría o no se pudo eliminar.']);
            }
        } catch (PDOException $e) {
            // Manejar error si hay productos asociados (Foreign Key constraint)
            if ($e->getCode() == '23000') {
                http_response_code(409); // Conflict
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar la categoría porque hay productos asociados a ella.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error de base de datos al eliminar categoría: ' . $e->getMessage()]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al eliminar categoría: ' . $e->getMessage()]);
        }
    }

    /**
     * Obtiene todas las marcas.
     */
    public function getMarcas()
    {
        header('Content-Type: application/json');
        try {
            $marcas = $this->marcaModel->getAll();
            echo json_encode(['success' => true, 'data' => $marcas]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al obtener marcas: ' . $e->getMessage()]);
        }
    }

    /**
     * Crea una nueva marca.
     */
    public function createMarca()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'El nombre de la marca es obligatorio.']);
            return;
        }

        try {
            if ($this->marcaModel->create($data)) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Marca creada exitosamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo crear la marca.']);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { 
                http_response_code(409); 
                echo json_encode(['success' => false, 'message' => 'Ya existe una marca con ese nombre.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error de base de datos al crear marca: ' . $e->getMessage()]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al crear marca: ' . $e->getMessage()]);
        }
    }

    /**
     * Actualiza una marca existente.
     */
    public function updateMarca()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id']) || empty($data['nombre'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID y nombre de marca son obligatorios para actualizar.']);
            return;
        }

        try {
            if ($this->marcaModel->update($data['id'], $data)) {
                echo json_encode(['success' => true, 'message' => 'Marca actualizada exitosamente.']);
            } else {
                http_response_code(404); 
                echo json_encode(['success' => false, 'message' => 'No se encontró la marca o no hubo cambios.']);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') { 
                http_response_code(409); 
                echo json_encode(['success' => false, 'message' => 'Ya existe otra marca con ese nombre.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error de base de datos al actualizar marca: ' . $e->getMessage()]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al actualizar marca: ' . $e->getMessage()]);
        }
    }

    /**
     * Elimina una marca.
     */
    public function deleteMarca()
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de marca no proporcionado para eliminar.']);
            return;
        }

        try {
            if ($this->marcaModel->delete($data['id'])) {
                echo json_encode(['success' => true, 'message' => 'Marca eliminada exitosamente.']);
            } else {
                http_response_code(404); 
                echo json_encode(['success' => false, 'message' => 'No se encontró la marca o no se pudo eliminar.']);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                http_response_code(409); 
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar la marca porque hay productos asociados a ella.']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error de base de datos al eliminar marca: ' . $e->getMessage()]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error del servidor al eliminar marca: ' . $e->getMessage()]);
        }
    }
}
