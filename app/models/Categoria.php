<?php
// Archivo: /app/models/Categoria.php

require_once __DIR__ . '/../../config/Database.php';

class Categoria
{
    private $conn;
    private $table_name = "categorias";

    public function __construct()
    {
        $database = Database::getInstance();
        $this->conn = $database->getConnection();
    }

    /**
     * Obtiene todas las categorías de la base de datos.
     * @return array Array de objetos de categoría.
     */
    public function getAll()
    {
        $query = "SELECT id, nombre, descripcion FROM " . $this->table_name . " ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea una nueva categoría.
     * @param array $data Array asociativo con 'nombre' y 'descripcion'.
     * @return bool True si la creación fue exitosa, false en caso contrario.
     */
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " (nombre, descripcion) VALUES (:nombre, :descripcion)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        return $stmt->execute();
    }

    /**
     * Actualiza una categoría existente.
     * @param int $id ID de la categoría a actualizar.
     * @param array $data Array asociativo con 'nombre' y 'descripcion'.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " SET nombre = :nombre, descripcion = :descripcion WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nombre', $data['nombre']);
        $stmt->bindParam(':descripcion', $data['descripcion']);
        return $stmt->execute();
    }

    /**
     * Elimina una categoría.
     * @param int $id ID de la categoría a eliminar.
     * @return bool True si la eliminación fue exitosa, false en caso contrario.
     */
    public function delete($id)
    {
        // Considerar la lógica para manejar productos asociados a esta categoría
        // Por ahora, si la FK está configurada con ON DELETE CASCADE, la BD lo manejará.
        // Si no, necesitarás actualizar los productos a NULL o a otra categoría por defecto.
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
